<?php

defined('MOODLE_INTERNAL') || die;

//
// Relatório de Acompanhamento de Entrega de Atividades
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @param array $modulos
 * @param string $curso_ufsc
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_entrega_de_atividades($modulos, $tutores, $curso_ufsc, $curso_moodle) {
    global $CFG;
    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade,
                      sub.status
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
            ORDER BY u.firstname, u.lastname
    ";

    // Recupera dados auxiliares

    $modulos = get_atividades_modulos(get_modulos_validos($modulos));
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

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }


    $dados = array();


    foreach ($group_tutoria as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

            foreach ($aluno as $atividade) {
                $atraso = null;

                // Não enviou a atividade
                if (is_null($atividade->submission_date)) {
                    if ((int) $atividade->duedate == 0) {
                        // Não entregou e Atividade sem prazo de entrega
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA;
                    } else {
                        // Não entregou e fora do prazo
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE;
                    }
                }
                // Entregou antes ou na data de entrega esperada que é a data de entrega com uma tolencia de $CFG->report_unasus_prazo_entrega dias
                elseif ((int) $atividade->submission_date <= (int) ($atividade->duedate + (3600 * 24 * $CFG->report_unasus_prazo_entrega))) {
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO;
                }
                // Entregou após a data esperada
                else {
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                    $submission_date = ((int) $atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
                    $datadiff = get_datetime_from_unixtime($submission_date)->diff(get_datetime_from_unixtime($atividade->duedate));
                    $atraso = $datadiff->format("%a");
                }
                $lista_atividades[] = new dado_entrega_de_atividades($tipo, $atividade->assignid, $atraso);
            }
            $estudantes[] = $lista_atividades;
            $lista_atividades = null;
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] = $estudantes;
    }

    return($dados);
}

function get_table_header_entrega_de_atividades($modulos) {
    return get_table_header_atividades_vs_notas($modulos);
}

function get_dados_grafico_entrega_de_atividades($modulos, $tutores, $curso_ufsc) {

    global $CFG;
    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade,
                      sub.status
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
            ORDER BY u.firstname, u.lastname
    ";

    // Recupera dados auxiliares

    $modulos = get_atividades_modulos(get_modulos_validos($modulos));
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

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }


    $dados = array();


    foreach ($group_tutoria as $grupo_id => $array_dados) {
        //variáveis soltas para melhor entendimento
        $count_entregue_no_prazo = 0;
        $count_pouco_atraso = 0;
        $count_muito_atraso = 0;
        $count_nao_entregue = 0;
        $count_sem_prazo = 0;


        foreach ($array_dados as $id_aluno => $aluno) {

            foreach ($aluno as $atividade) {
                $atraso = null;

                // Não enviou a atividade
                if (is_null($atividade->submission_date)) {
                    if ((int) $atividade->duedate == 0) {
                        // Não entregou e Atividade sem prazo de entrega
                        $count_sem_prazo++;
                    } else {
                        // Não entregou e fora do prazo
                        $count_nao_entregue++;
                    }
                }
                // Entregou antes ou na data de entrega esperada que é a data de entrega com uma tolencia de $CFG->report_unasus_prazo_entrega dias
                elseif ((int) $atividade->submission_date <= (int) ($atividade->duedate + (3600 * 24 * $CFG->report_unasus_prazo_entrega))) {
                    $count_entregue_no_prazo++;
                }
                // Entregou após a data esperada
                else {
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                    $submission_date = ((int) $atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
                    $datadiff = get_datetime_from_unixtime($submission_date)->diff(get_datetime_from_unixtime($atividade->duedate));
                    $atraso = $datadiff->format("%a");
                    ($atraso > $CFG->report_unasus_prazo_maximo_avaliacao) ? $count_muito_atraso++ : $count_pouco_atraso++;
                }
            }
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] =
              array($count_nao_entregue,
                  $count_sem_prazo,
                  $count_entregue_no_prazo,
                  $count_pouco_atraso,
                  $count_muito_atraso,
        );
        ;
    }

    return($dados);
}

//
// Relatório de Histórico de Atribuição de Notas
//


/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @param array $modulos
 * @param string $curso_ufsc
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_historico_atribuicao_notas($modulos, $tutores, $curso_ufsc, $curso_moodle) {
    global $CFG;

    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      sub.timemodified as submission_modified,
                      gr.timemodified as grade_modified,
                      gr.timecreated as grade_created,
                      gr.grade,
                      sub.status
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
            ORDER BY u.firstname, u.lastname
    ";


    // Recupera dados auxiliares

    $modulos = get_atividades_modulos($modulos);
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

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }

    $dados = array();
    $timenow = time();
    foreach ($group_tutoria as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

            foreach ($aluno as $atividade) {
                $atraso = null;
                // Não enviou a atividade
                if (is_null($atividade->submission_date)) {
                    $tipo = dado_historico_atribuicao_notas::ATIVIDADE_NAO_ENTREGUE;
                } //Atividade entregue e não avaliada
                elseif (is_null($atividade->grade) || $atividade->grade < 0) {

                    $tipo = dado_historico_atribuicao_notas::ATIVIDADE_ENTREGUE_NAO_AVALIADA;
                    $data_envio = ((int) $atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;
                    $datadiff = get_datetime_from_unixtime($timenow)->diff(get_datetime_from_unixtime($data_envio));
                    $atraso = (int) $datadiff->format("%a");
                } //Atividade entregue e avalidada
                elseif ((int) $atividade->grade >= 0) {

                    //quanto tempo desde a entrega até a correção
                    $data_correcao = ((int) $atividade->grade_created != 0) ? $atividade->grade_created : $atividade->grade_modified;
                    $data_envio = ((int) $atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;
                    $datadiff = get_datetime_from_unixtime($data_correcao)->diff(get_datetime_from_unixtime($data_envio));
                    $atraso = (int) $datadiff->format("%a");

                    //Correção no prazo esperado
                    if ($atraso <= $CFG->report_unasus_prazo_avaliacao) {
                        $tipo = dado_historico_atribuicao_notas::CORRECAO_NO_PRAZO;
                    } //Correção com pouco atraso
                    elseif ($atraso <= $CFG->report_unasus_prazo_maximo_avaliacao) {
                        $tipo = dado_historico_atribuicao_notas::CORRECAO_POUCO_ATRASO;
                    } //Correção com muito atraso
                    else {
                        $tipo = dado_historico_atribuicao_notas::CORRECAO_MUITO_ATRASO;
                    }
                } else {
                    print_error('unmatched_condition', 'report_unasus');
                    return false;
                }

                $lista_atividades[] = new dado_historico_atribuicao_notas($tipo, $atividade->assignid, $atraso);
            }
            $estudantes[] = $lista_atividades;
            $lista_atividades = null;
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] = $estudantes;
    }

    return $dados;
}

function get_table_header_historico_atribuicao_notas($modulos) {
    return get_table_header_atividades_vs_notas($modulos);
}

function get_dados_grafico_historico_atribuicao_notas($modulos, $tutores, $curso_ufsc) {
    global $CFG;

    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      sub.timemodified as submission_modified,
                      gr.timemodified as grade_modified,
                      gr.timecreated as grade_created,
                      gr.grade,
                      sub.status
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
            ORDER BY u.firstname, u.lastname
    ";


    // Recupera dados auxiliares

    $modulos = get_atividades_modulos($modulos);
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

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }

    $dados = array();
    $timenow = time();
    foreach ($group_tutoria as $grupo_id => $array_dados) {


        $count_nao_entregue = 0;
        $count_nao_avaliada = 0;
        $count_no_prazo = 0;
        $count_pouco_atraso = 0;
        $count_muito_atraso = 0;

        foreach ($array_dados as $id_aluno => $aluno) {

            foreach ($aluno as $atividade) {
                $atraso = null;
                // Não enviou a atividade
                if (is_null($atividade->submission_date)) {
                    $count_nao_entregue++;
                } //Atividade entregue e não avaliada
                elseif (is_null($atividade->grade) || $atividade->grade < 0) {
                    $count_nao_avaliada++;
                } //Atividade entregue e avalidada
                elseif ((int) $atividade->grade >= 0) {

                    //quanto tempo desde a entrega até a correção
                    $data_correcao = ((int) $atividade->grade_created != 0) ? $atividade->grade_created : $atividade->grade_modified;
                    $data_envio = ((int) $atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;
                    $datadiff = get_datetime_from_unixtime($data_correcao)->diff(get_datetime_from_unixtime($data_envio));
                    $atraso = (int) $datadiff->format("%a");

                    //Correção no prazo esperado
                    if ($atraso <= $CFG->report_unasus_prazo_avaliacao) {
                        $count_no_prazo++;
                    } //Correção com pouco atraso
                    elseif ($atraso <= $CFG->report_unasus_prazo_maximo_avaliacao) {
                        $count_pouco_atraso++;
                    } //Correção com muito atraso
                    else {
                        $count_muito_atraso++;
                    }
                } else {
                    print_error('unmatched_condition', 'report_unasus');
                    return false;
                }
            }
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] = array(
            $count_nao_entregue,
            $count_nao_avaliada,
            $count_no_prazo,
            $count_pouco_atraso,
            $count_muito_atraso);
    }

    return $dados;
}

//
// Lista: Atividades Não Postadas
//

function get_dados_estudante_sem_atividade_postada($modulos, $tutores, $curso_ufsc, $curso_moodle) {

    // Consulta
    $query = " SELECT u.id as user_id,
                      gr.grade,
                      sub.status
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
            ON (gr.assignment=sub.assignment AND gr.userid=u.id)
            WHERE sub.status IS NULL
            ORDER BY u.firstname, u.lastname
    ";

    return get_todo_list_data($modulos, $tutores, $query, $curso_ufsc, $curso_moodle);
}

function get_header_estudante_sem_atividade_postada($size) {
    $content = array();
    for ($index = 0; $index < $size - 1; $index++) {
        $content[] = '';
    }
    $header['Atividades não resolvidas'] = $content;
    return $header;
}

function get_todo_list_data($modulos, $tutores, $query, $curso_ufsc, $curso_moodle) {
    $middleware = Middleware::singleton();

    // Recupera dados auxiliares
    // Recupera dados auxiliares
    $modulos = get_atividades_modulos(get_modulos_validos($modulos));
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

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }



    $id_nome_modulos = get_id_nome_modulos();
    $id_nome_atividades = get_id_nome_atividades();


    $dados = array();
    foreach ($group_tutoria as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

            $atividades_modulos = new GroupArray();

            foreach ($aluno as $atividade) {
                $atividades_modulos->add($atividade->courseid, $atividade->assignid);
            }


            $ativ_mod = $atividades_modulos->get_assoc();
            foreach ($ativ_mod as $key => $modulo) {
                $lista_atividades[] = new dado_modulo($key, $id_nome_modulos[$key]);
                foreach ($modulo as $atividade) {
                    $lista_atividades[] = new dado_atividade($atividade, $id_nome_atividades[$atividade], $key);
                }
            }


            $estudantes[] = $lista_atividades;
            $lista_atividades = null;
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] = $estudantes;
    }
    return $dados;
}

//
// Lista: Atividades não Avaliadas
//

function get_dados_estudante_sem_atividade_avaliada($modulos, $tutores, $curso_ufsc, $curso_moodle) {

    // Consulta
    $query = " SELECT u.id as user_id,
                      gr.grade,
                      sub.status
                 FROM (
                      SELECT DISTINCT u.id, u.firstname, u.lastname, gt.id as grupo_id
                         FROM {user} u
                         JOIN {table_PessoasGruposTutoria} pg
                           ON (pg.matricula=u.username)
                         JOIN {table_GruposTutoria} gt
                           ON (gt.id=pg.grupo)
                        WHERE gt.curso=:curso_ufsc AND pg.grupo=:grupo_tutoria AND pg.tipo=:tipo_aluno
                      ) u
            JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            WHERE gr.grade IS NULL OR gr.grade = -1
            ORDER BY u.firstname, u.lastname
    ";

    return get_todo_list_data($modulos, $tutores, $query, $curso_ufsc, $curso_moodle);
}

//
// Síntese: Avaliações em Atraso
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @param array $modulos
 * @param string $curso_ufsc
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_nao_avaliadas($modulos, $tutores, $curso_ufsc, $curso_moodle) {

    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      gr.grade,
                      sub.status
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
            ON (gr.assignment=sub.assignment AND gr.userid=u.id)
            WHERE sub.status IS NULL
            ORDER BY u.firstname, u.lastname
    ";

    // Recupera dados auxiliares
    $modulos = get_atividades_modulos(get_modulos_validos($modulos));
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($curso_ufsc, $tutores);



    $group_tutoria = array();
    $lista_atividade = array();
    // Listagem da atividades por tutor
    $total_alunos = get_count_estudantes($curso_ufsc);
    $total_atividades = 0;

    foreach ($modulos as $atividades) {
        $total_atividades += count($atividades);
    }


    // Executa Consulta
    foreach ($grupos_tutoria as $grupo) {
        $group_array_do_grupo = new GroupArray();
        $group_array_das_atividades = new GroupArray();
        foreach ($modulos as $modulo => $atividades) {
            foreach ($atividades as $atividade) {
                $params = array('courseid' => $modulo, 'assignmentid' => $atividade->assign_id, 'curso_ufsc' => $curso_ufsc,
                    'grupo_tutoria' => $grupo->id, 'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);
                $result = $middleware->get_records_sql($query, $params);

                // para cada assign um novo dado de avaliacao em atraso
                $group_array_das_atividades->add($atividade->assign_id, new dado_atividades_nota_atribuida($total_alunos[$grupo->id]));

                foreach ($result as $r) {

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $lista_atividade[$grupo->id] = $group_array_das_atividades->get_assoc();
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }






    $somatorio_total_atrasos = array();
    foreach ($group_tutoria as $grupo_id => $array_dados) {
        foreach ($array_dados as $id_aluno => $aluno) {
            foreach ($aluno as $atividade) {
                $lista_atividade[$grupo_id][$atividade->assignid][0]->incrementar_atraso();
                if (!key_exists($grupo_id, $somatorio_total_atrasos)) {
                    $somatorio_total_atrasos[$grupo_id] = 0;
                }
                $somatorio_total_atrasos[$grupo_id]++;
            }
        }
    }


    $dados = array();
    foreach ($lista_atividade as $grupo_id => $grupo) {
        $data = array();
        $data[] = grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id);
        foreach ($grupo as $atividades) {
            $data[] = $atividades[0];
        }
        $data[] = new dado_media(($somatorio_total_atrasos[$grupo_id] * 100) / ($total_alunos[$grupo_id] * $total_atividades));
        $dados[] = $data;
    }

    return $dados;
}

//
// Síntese: atividades concluídas
//

function get_dados_atividades_nota_atribuida($modulos, $tutores, $curso_ufsc, $curso_moodle) {
    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      gr.grade,
                      sub.status
                 FROM (
                      SELECT DISTINCT u.id, u.firstname, u.lastname, gt.id as grupo_id
                         FROM {user} u
                         JOIN {table_PessoasGruposTutoria} pg
                           ON (pg.matricula=u.username)
                         JOIN {table_GruposTutoria} gt
                           ON (gt.id=pg.grupo)
                        WHERE gt.curso=:curso_ufsc AND pg.grupo=:grupo_tutoria AND pg.tipo=:tipo_aluno
                      ) u
            JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            WHERE gr.grade IS NOT NULL OR gr.grade != -1
            ORDER BY u.firstname, u.lastname
    ";

    // Recupera dados auxiliares
    $modulos = get_atividades_modulos(get_modulos_validos($modulos));
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($curso_ufsc, $tutores);



    $group_tutoria = array();
    $lista_atividade = array();
    // Listagem da atividades por tutor
    $total_alunos = get_count_estudantes($curso_ufsc);
    $total_atividades = 0;

    foreach ($modulos as $atividades) {
        $total_atividades += count($atividades);
    }


    // Executa Consulta
    foreach ($grupos_tutoria as $grupo) {
        $group_array_do_grupo = new GroupArray();
        $group_array_das_atividades = new GroupArray();
        foreach ($modulos as $modulo => $atividades) {
            foreach ($atividades as $atividade) {
                $params = array('courseid' => $modulo, 'assignmentid' => $atividade->assign_id, 'curso_ufsc' => $curso_ufsc,
                    'grupo_tutoria' => $grupo->id, 'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);
                $result = $middleware->get_records_sql($query, $params);

                // para cada assign um novo dado de avaliacao em atraso
                $group_array_das_atividades->add($atividade->assign_id, new dado_atividades_nota_atribuida($total_alunos[$grupo->id]));

                foreach ($result as $r) {

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

                    // Agrupa os dados por usuário
                    $group_array_do_grupo->add($r->user_id, $r);
                }
            }
        }
        $lista_atividade[$grupo->id] = $group_array_das_atividades->get_assoc();
        $group_tutoria[$grupo->id] = $group_array_do_grupo->get_assoc();
    }






    $somatorio_total_atrasos = array();
    foreach ($group_tutoria as $grupo_id => $array_dados) {
        foreach ($array_dados as $id_aluno => $aluno) {
            foreach ($aluno as $atividade) {
                $lista_atividade[$grupo_id][$atividade->assignid][0]->incrementar_atraso();
                if (!key_exists($grupo_id, $somatorio_total_atrasos)) {
                    $somatorio_total_atrasos[$grupo_id] = 0;
                }
                $somatorio_total_atrasos[$grupo_id]++;
            }
        }
    }


    $dados = array();
    foreach ($lista_atividade as $grupo_id => $grupo) {
        $data = array();
        $data[] = grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id);
        foreach ($grupo as $atividades) {
            $data[] = $atividades[0];
        }
        $data[] = new dado_media(($somatorio_total_atrasos[$grupo_id] * 100) / ($total_alunos[$grupo_id] * $total_atividades));
        $dados[] = $data;
    }

    return $dados;
}

//
// Uso do Sistema pelo Tutor (horas)
//

/**
 * @TODO arrumar media
 */
function get_dados_uso_sistema_tutor($modulos, $tutores, $curso_ufsc, $curso_moodle) {
    $lista_tutores = get_tutores_menu($curso_ufsc);
    $dados = array();
    $tutores = array();
    foreach ($lista_tutores as $tutor_id => $tutor) {
        $media = new dado_media(rand(0, 20));

        $tutores[] = array(new tutor($tutor, $tutor_id, $curso_moodle),
            new dado_uso_sistema_tutor(rand(5, 20)),
            new dado_uso_sistema_tutor(rand(5, 20)),
            new dado_uso_sistema_tutor(rand(5, 20)),
            new dado_uso_sistema_tutor(rand(5, 20)),
            new dado_uso_sistema_tutor(rand(5, 20)),
            new dado_uso_sistema_tutor(rand(5, 20)),
            $media->value(),
            new dado_somatorio(rand(10, 20) + rand(10, 20) + rand(10, 20) + rand(10, 20) + rand(10, 20) + rand(10, 20)));
    }
    $dados["Tutores"] = $tutores;

    return $dados;
}

function get_table_header_uso_sistema_tutor() {
    return array('Tutor', 'Jun/Q4', 'Jul/Q1', 'Jul/Q2', 'Jul/Q3', 'Jul/Q4', 'Ago/Q1', 'Media', 'Total');
}

function get_dados_grafico_uso_sistema_tutor($modulo, $tutores, $curso_ufsc) {
    $tutores = get_tutores_menu($curso_ufsc);

    $dados = array();
    foreach ($tutores as $tutor) {
        $dados[$tutor] = array('Jun/Q4' => rand(5, 20), 'Jul/Q1' => rand(5, 20), 'Jul/Q2' => rand(5, 20), 'Jul/Q3' => rand(5, 20), 'Jul/Q4' => rand(5, 20), 'Ago/Q1' => rand(5, 20));
    }

    return $dados;
}

//
// Uso do Sistema pelo Tutor (acesso)
//

function get_dados_acesso_tutor($modulos, $tutores, $curso_ufsc, $curso_moodle) {
    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT year(from_unixtime(sud.`timeend`)) AS calendar_year,
                      month(from_unixtime(sud.`timeend`)) AS calendar_month,
                      day(from_unixtime(sud.`timeend`)) AS calendar_day,
                      sud.userid
                 FROM {stats_user_daily} sud
           INNER JOIN {user} u
                   ON (u.id=sud.userid)
           INNER JOIN {table_PessoasGruposTutoria} pgt
                   -- ON (pgt.matricula=u.username)
                   ON (pgt.matricula=u.username AND pgt.tipo=:tipo_tutor)
             GROUP BY calendar_year, calendar_month, calendar_day, sud.userid
             ORDER BY calendar_year, calendar_month, calendar_day";


    $params = array('tipo_tutor' => GRUPO_TUTORIA_TIPO_TUTOR);
    $result = $middleware->get_recordset_sql($query, $params);

    //Para cada linha da query ele cria um ['pessoa']=>['data_entrada1','data_entrada2]
    $group_array = new GroupArray();
    foreach ($result as $r) {
        $dia = $r['calendar_day'];
        $mes = $r['calendar_month'];
        if ($dia < 10)
            $dia = '0' . $dia;
        if ($mes < 10)
            $mes = '0' . $mes;
        $group_array->add($r['userid'], $dia . '/' . $mes);
    }
    $dados = $group_array->get_assoc();

    // Intervalo de dias no formato d/m
    $end = new DateTime();
    $interval = new DateInterval('P60D');

    $begin = clone $end;
    $begin->sub($interval);

    $increment = new DateInterval('P1D');
    $daterange = new DatePeriod($begin, $increment, $end);

    $dias_meses = array();
    foreach ($daterange as $date) {
        $dias_meses[] = $date->format('d/m');
    }


    //para cada resultado da busca ele verifica se esse dado bate no "calendario" criado com o
    //date interval acima
    $result = new GroupArray();
    foreach ($dados as $id => $datas) {
        foreach($dias_meses as $dia){
           (in_array($dia, $datas)) ?
                        $result->add($id, new dado_acesso_tutor(true)) :
                        $result->add($id, new dado_acesso_tutor(false));
        }

    }
    $result = $result->get_assoc();

    $nomes_tutores = grupos_tutoria::get_tutores_curso_ufsc($curso_ufsc);

    //para cada resultado que estava no formato [id]=>[dados_acesso]
    // ele transforma para [tutor,dado_acesso1,dado_acesso2]
    $retorno = array();
    foreach($result as $id => $values){
        $dados = array();
        $nome = (array_key_exists($id, $nomes_tutores)) ? $nomes_tutores[$id] : $id;
        array_push($dados, new tutor($nome,$id,$curso_moodle));
        foreach($values as $value){
            array_push($dados, $value);
        }
        $retorno[] = $dados;
    }
    

    return array('Tutores'=>$retorno);
}

function get_table_header_acesso_tutor() {
    $end = new DateTime();
    $interval = new DateInterval('P60D');

    $begin = clone $end;
    $begin->sub($interval);

    $increment = new DateInterval('P1D');
    $daterange = new DatePeriod($begin, $increment, $end);

    $meses = array();
    foreach ($daterange as $date) {
        $mes = strftime("%B", $date->format('U'));
        if (!array_key_exists($mes, $meses)) {
            $meses[$mes] = null;
        }
        $meses[$mes][] = $date->format('d/m');
    }

    return $meses;
}

//
// Potenciais Evasões
//

function get_dados_potenciais_evasoes($modulos, $tutores, $curso_ufsc, $curso_moodle) {
    global $CFG;
    $middleware = Middleware::singleton();

    // Consulta
    $query = " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade
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
            ON (gr.assignment=sub.assignment AND gr.userid=u.id)
            ORDER BY u.firstname, u.lastname
    ";


    // Recupera dados auxiliares



    $modulos = get_atividades_modulos(get_modulos_validos($modulos));
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

                    // Adiciona campos extras
                    $r->courseid = $modulo;
                    $r->assignid = $atividade->assign_id;
                    $r->duedate = $atividade->duedate;

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
            $dados_modulos = array();
            $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);
            foreach ($aluno as $atividade) {

                //para cada novo modulo ele cria uma entrada de dado_potenciais_evasoes com o maximo de atividades daquele modulo
                if (!array_key_exists($atividade->courseid, $dados_modulos)) {
                    $dados_modulos[$atividade->courseid] = new dado_potenciais_evasoes(sizeof($modulos[$atividade->courseid]));
                }

                //para cada atividade nao feita ele adiciona uma nova atividade nao realizada naquele modulo
                if (is_null($atividade->submission_date) && $atividade->duedate <= $timenow) {
                    $dados_modulos[$atividade->courseid]->add_atividade_nao_realizada();
                }
            }

            $atividades_nao_realizadas_do_estudante = 0;
            foreach ($dados_modulos as $key => $modulo) {
                $lista_atividades[] = $modulo;
                $atividades_nao_realizadas_do_estudante += $modulo->get_total_atividades_nao_realizadas();
            }

            if ($atividades_nao_realizadas_do_estudante > $CFG->report_unasus_tolerancia_potencial_evasao) {
                $estudantes[] = $lista_atividades;
            }
            $lista_atividades = null;
        }

        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] = $estudantes;
    }
    return $dados;
}

function get_table_header_potenciais_evasoes($modulos) {
    $nome_modulos = get_id_nome_modulos();
    if (is_null($modulos)) {
        $modulos = get_id_modulos();
    }

    $header = array();
    $header[] = 'Estudantes';
    foreach ($modulos as $modulo) {
        $header[] = new dado_modulo($modulo, $nome_modulos[$modulo]);
    }
    return $header;
}

//
// Outros ??
//

function get_table_header_atividades_nota_atribuida($modulos) {
    return get_table_header_atividades_nao_avaliadas($modulos);
}

function get_table_header_atividades_nao_avaliadas($modulos) {
    $header = get_table_header_atividades_vs_notas($modulos);
    $header[''] = array('Média');
    return $header;
}

