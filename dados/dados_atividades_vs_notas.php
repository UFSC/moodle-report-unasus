<?php

defined('MOODLE_INTERNAL') || die;

// Relatório de Atividades vs Notas Atribuídas

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @param $modulos
 * @param $tutores
 * @param $curso_ufsc
 * @param $curso_moodle
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_vs_notas($curso_ufsc, $curso_moodle, $modulos, $tutores)
{

    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade, grupo_id
                 FROM (
                      SELECT DISTINCT u.id, u.firstname, u.lastname, gt.id as grupo_id
                         FROM {user} u
                         JOIN {table_PessoasGruposTutoria} pg
                           ON (pg.matricula=u.username)
                         JOIN {table_GruposTutoria} gt
                           ON (gt.id=pg.grupo)
                        WHERE gt.curso=:curso_ufsc AND pg.grupo=:grupo_tutoria AND pg.tipo=:tipo_aluno
                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            ORDER BY grupo_id, u.firstname, u.lastname
    ";


    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($curso_ufsc, $tutores);


    $group_tutoria = array();

    // Executa Consulta
    foreach ($grupos_tutoria as $grupo) {
        $group_array_do_grupo = new GroupArray();
        foreach ($modulos as $modulo => $atividades) {
            foreach ($atividades as $atividade) {
                $params = array('courseid' => $modulo, 'assignmentid' => $atividade->assign_id,
                    'curso_ufsc' => $curso_ufsc, 'grupo_tutoria' => $grupo->id, 'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);
                $result = $middleware->get_records_sql($query, $params);

                foreach ($result as $r) {
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = (int)$atividade->duedate;
                    if (!is_null($r->grade)) {
                        $r->grade = (float)$r->grade;
                    }

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }


    //pega a hora atual para comparar se uma atividade esta atrasada ou nao
    $timenow = time();


    $dados = array();
    foreach ($group_tutoria as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);
            foreach ($aluno as $atividade) {

                $atraso = null;

                // Não entregou
                if (is_null($atividade->submission_date)) {
                    if ($atividade->duedate == 0) {
                        $tipo = dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA;
                    } elseif ($atividade->duedate > $timenow) {
                        $tipo = dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA;
                    } else {
                        $tipo = dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE;
                    }
                } // Entregou e ainda não foi avaliada
                elseif (is_null($atividade->grade) || $atividade->grade < 0) {
                    $tipo = dado_atividades_vs_notas::CORRECAO_ATRASADA;

                    // calculo do atraso
                    $submission_date = ((int)$atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
                    $datadiff = date_create()->diff(get_datetime_from_unixtime($submission_date));
                    $atraso = (int)$datadiff->format("%a");
                } // Atividade entregue e avaliada
                elseif ($atividade->grade > -1) {
                    $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA;
                } else {
                    print_error('unmatched_condition', 'report_unasus');
                }

                $lista_atividades[] = new dado_atividades_vs_notas($tipo, $atividade->assignid, $atividade->grade, $atraso);
            }
            $estudantes[] = $lista_atividades;
            $lista_atividades = null;
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] = $estudantes;
    }

    return $dados;
}

/**
 *  Cabeçalho de duas linhas para os relatórios
 *  Primeira linha módulo1, modulo2
 *  Segunda linha ativ1_mod1, ativ2_mod1, ativ1_mod2, ativ2_mod2
 *
 * @param array $modulos
 * @return array
 */
function get_table_header_atividades_vs_notas($modulos = array())
{
    $atividades_modulos = query_atividades_modulos($modulos);
    $foruns_modulos = query_forum_modulo($modulos);



    $group = new GroupArray();
    $modulos = array();
    $header = array();

    // Agrupa atividades por curso e cria um índice de cursos
    foreach ($atividades_modulos as $atividade) {
        $modulos[$atividade->course_id] = $atividade->course_name;
        $group->add($atividade->course_id, $atividade);
    }

    //Agrupa os foruns pelos seus respectivos modulos
    foreach ($foruns_modulos as $forum) {
        $group->add($forum->course_id, $forum);
    }

    $group_assoc = $group->get_assoc();

    foreach ($group_assoc as $course_id => $atividades) {
        $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
        $course_link = html_writer::link($course_url, $modulos[$course_id]);
        $dados = array();

        foreach ($atividades as $atividade) {
            if (array_key_exists('assign_id', $atividade)) {
                $cm = get_coursemodule_from_instance('assign', $atividade->assign_id, $course_id, null, MUST_EXIST);

                $atividade_url = new moodle_url('/mod/assign/view.php', array('id' => $cm->id));
                $dados[] = html_writer::link($atividade_url, $atividade->assign_name);
            } else {
                $dados[] = $atividade->itemname;
            }
        }
        $header[$course_link] = $dados;
    }

    return $header;
}


function get_dados_grafico_atividades_vs_notas($curso_ufsc, $modulos, $tutores)
{
    global $CFG;
    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade, grupo_id
                 FROM (
                      SELECT DISTINCT u.id, u.firstname, u.lastname, gt.id as grupo_id
                         FROM {user} u
                         JOIN {table_PessoasGruposTutoria} pg
                           ON (pg.matricula=u.username)
                         JOIN {table_GruposTutoria} gt
                           ON (gt.id=pg.grupo)
                        WHERE gt.curso=:curso_ufsc AND pg.grupo=:grupo_tutoria AND pg.tipo=:tipo_aluno
                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            ORDER BY grupo_id, u.firstname, u.lastname
    ";


    // Recupera dados auxiliares
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($curso_ufsc, $tutores);


    $grupos = array();

    // Executa Consulta
    foreach ($grupos_tutoria as $grupo) {
        $group_array_do_grupo = new GroupArray();

        foreach ($modulos as $modulo => $atividades) {
            foreach ($atividades as $atividade) {
                $params = array('courseid' => $modulo, 'assignmentid' => $atividade->assign_id,
                    'curso_ufsc' => $curso_ufsc, 'grupo_tutoria' => $grupo->id, 'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);
                $result = $middleware->get_records_sql($query, $params);

                foreach ($result as $r) {
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;
                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }

        $grupos[$grupo->id] = $group_array_do_grupo->get_assoc();
    }

    //pega a hora atual para comparar se uma atividade esta atrasada ou nao
    $timenow = time();


//  Ordem dos dados nos gráficos
//        'nota_atribuida'
//        'pouco_atraso'
//        'muito_atraso'
//        'nao_entregue'
//        'nao_realizada'
//        'sem_prazo'

    $dados = array();
    foreach ($grupos as $grupo_id => $array_dados) {
        //variáveis soltas para melhor entendimento
        $count_nota_atribuida = 0;
        $count_pouco_atraso = 0;
        $count_muito_atraso = 0;
        $count_nao_entregue = 0;
        $count_nao_realizada = 0;
        $count_sem_prazo = 0;

        foreach ($array_dados as $id_aluno => $aluno) {
            foreach ($aluno as $atividade) {

                $atraso = null;

                // Não entregou
                if (is_null($atividade->submission_date)) {
                    if ((int)$atividade->duedate == 0) {
                        $count_sem_prazo++;
                    } elseif ($atividade->duedate > $timenow) {
                        $count_nao_realizada++;
                    } else {
                        $count_nao_entregue++;
                    }
                } // Entregou e ainda não foi avaliada
                elseif (is_null($atividade->grade) || (float)$atividade->grade < 0) {
                    $tipo = dado_atividades_vs_notas::CORRECAO_ATRASADA;
                    $submission_date = ((int)$atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
                    $datadiff = date_create()->diff(get_datetime_from_unixtime($submission_date));
                    $atraso = $datadiff->format("%a");
                    ($atraso > $CFG->report_unasus_prazo_maximo_avaliacao) ? $count_muito_atraso++ : $count_pouco_atraso++;
                } // Atividade entregue e avaliada
                elseif ((float)$atividade->grade > -1) {
                    $count_nota_atribuida++;
                } else {
                    print_error('unmatched_condition', 'report_unasus');
                }
            }
        }

        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] =
            array($count_nota_atribuida,
                $count_pouco_atraso,
                $count_muito_atraso,
                $count_nao_entregue,
                $count_nao_realizada,
                $count_sem_prazo);


    }
    return $dados;
}
