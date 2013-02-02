<?php

require_once($CFG->dirroot . '/report/unasus/relatorios/queries.php');
require_once($CFG->dirroot . '/report/unasus/relatorios/loops.php');

defined('MOODLE_INTERNAL') || die;


/* -----------------
 * ---------------------------------------
 * Relatório de Atividades vs Notas Atribuídas
 * ---------------------------------------
 * -----------------
 */


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
    // Dado Auxiliar
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    // Consultas
    $query_alunos_grupo_tutoria = query_atividades_vs_notas();
    $query_forum = query_postagens_forum();


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum);


    //pega a hora atual para comparar se uma atividade esta atrasada ou nao
    $timenow = time();


    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);


            foreach ($aluno as $atividade) {

                $atraso = null;

                // Atividade offline, não necessita de envio nem de arquivo ou texto mas tem uma data de entrega
                // aonde o tutor deveria dar a nota da avalicao offline
                $atividade_offline = array_key_exists('nosubmissions', $atividade) && $atividade->nosubmissions == 1;

                // Se for uma ativade online e o aluno nao enviou
                if (!$atividade_offline && is_null($atividade->submission_date)) {
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

                    //atividade online o calculo do atraso eh feito com a data de envio ou edicao
                    $submission_date = ((int)$atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;

                    //atividade offline o calculo do atraso eh feito com a data da tarefa, duedate
                    if ($atividade_offline) {
                        $submission_date = (int)$atividade->duedate;
                    }

                    // calculo do atraso
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
                $forum_url = new moodle_url('/mod/forum/view.php', array('id' => $atividade->idnumber));
                $dados[] = html_writer::link($forum_url, $atividade->itemname);
            }
        }
        $header[$course_link] = $dados;
    }
    return $header;
}


function get_dados_grafico_atividades_vs_notas($curso_ufsc, $modulos, $tutores)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_atividades_vs_notas();
    $query_forum = query_postagens_forum();


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum);

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
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
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

                // Atividade offline, não necessita de envio nem de arquivo ou texto mas tem uma data de entrega
                // aonde o tutor deveria dar a nota da avalicao offline
                $atividade_offline = array_key_exists('nosubmissions', $atividade) && $atividade->nosubmissions == 1;


                // Não entregou
                if (!$atividade_offline && is_null($atividade->submission_date)) {
                    if ((int)$atividade->duedate == 0) {
                        $count_sem_prazo++;
                    } elseif ($atividade->duedate > $timenow) {
                        $count_nao_realizada++;
                    } else {
                        $count_nao_entregue++;
                    }
                } // Entregou e ainda não foi avaliada
                elseif (is_null($atividade->grade) || (float)$atividade->grade < 0) {
                    //atividade online o calculo do atraso eh feito com a data de envio ou edicao
                    $submission_date = ((int)$atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;

                    //atividade offline o calculo do atraso eh feito com a data da tarefa, duedate
                    if ($atividade_offline) {
                        $submission_date = (int)$atividade->duedate;
                    }

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

/* -----------------
 * ---------------------------------------
 * Relatório de Acompanhamento de Entrega de Atividades
 * ---------------------------------------
 * -----------------
 */


/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @param string $curso_ufsc
 * @param string $curso_moodle
 * @param array $modulos
 * @param array $tutores
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_entrega_de_atividades($curso_ufsc, $curso_moodle, $modulos, $tutores)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_entrega_de_atividades();
    $query_forum = query_postagens_forum();

    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum);

    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

            foreach ($aluno as $atividade) {
                $atraso = null;

                // Não enviou a atividade
                if (is_null($atividade->submission_date)) {
                    if ((int)$atividade->duedate == 0) {
                        // Não entregou e Atividade sem prazo de entrega
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA;
                    } else {
                        // Não entregou e fora do prazo
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE;
                    }
                } // Entregou antes ou na data de entrega esperada que é a data de entrega com uma tolencia de $CFG->report_unasus_prazo_entrega dias
                elseif ((int)$atividade->submission_date <= (int)($atividade->duedate + (3600 * 24 * $CFG->report_unasus_prazo_entrega))) {
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO;
                } // Entregou após a data esperada
                else {
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                    $submission_date = ((int)$atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
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

    return ($dados);
}

/*
 * Cabeçalho da tabela
 */
function get_table_header_entrega_de_atividades($modulos)
{
    return get_table_header_atividades_vs_notas($modulos);
}

/*
 * Dados para o gráfico do relatorio entrega de atividadas
 */
function get_dados_grafico_entrega_de_atividades($curso_ufsc, $modulos, $tutores)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_entrega_de_atividades();
    $query_forum = query_postagens_forum();


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum);


    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        //variáveis soltas para melhor entendimento
        $count_entregue_no_prazo = 0;
        $count_pouco_atraso = 0;
        $count_muito_atraso = 0;
        $count_nao_entregue = 0;
        $count_sem_prazo = 0;


        foreach ($array_dados as $id_aluno => $aluno) {

            foreach ($aluno as $atividade) {
                $atraso = null;

                // Atividade offline, não necessita de envio nem de arquivo ou texto mas tem uma data de entrega
                // aonde o tutor deveria dar a nota da avalicao offline
                $atividade_offline = array_key_exists('nosubmissions', $atividade) && $atividade->nosubmissions == 1;


                // Não enviou a atividade
                if (!$atividade_offline && is_null($atividade->submission_date)) {
                    if ((int)$atividade->duedate == 0) {
                        // Não entregou e Atividade sem prazo de entrega
                        $count_sem_prazo++;
                    } else {
                        // Não entregou e fora do prazo
                        $count_nao_entregue++;
                    }
                } // Entregou antes ou na data de entrega esperada que é a data de entrega com uma tolencia de $CFG->report_unasus_prazo_entrega dias
                elseif ((int)$atividade->submission_date <= (int)($atividade->duedate + (3600 * 24 * $CFG->report_unasus_prazo_entrega))) {
                    $count_entregue_no_prazo++;
                } // Entregou após a data esperada
                else {
                    //atividade online o calculo do atraso eh feito com a data de envio ou edicao
                    $submission_date = ((int)$atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;

                    //atividade offline o calculo do atraso eh feito com a data da tarefa, duedate
                    if ($atividade_offline) {
                        $submission_date = (int)$atividade->duedate;
                    }

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

    return ($dados);
}

/* -----------------
 * ---------------------------------------
 * Relatório de Histórico de Atribuição de Notas
 * ---------------------------------------
 * -----------------
 */

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @param string $curso_ufsc
 * @param string $curso_moodle
 * @param array $modulos
 * @param array $tutores
 * @return array|bool Array[tutores][aluno][unasus_data]
 */
function get_dados_historico_atribuicao_notas($curso_ufsc, $curso_moodle, $modulos, $tutores)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_historico_atribuicao_notas();
    $query_forum = query_postagens_forum();

    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, false);

    $dados = array();
    $timenow = time();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

            foreach ($aluno as $atividade) {

                $atraso = null;

                // Atividade offline, não necessita de envio nem de arquivo ou texto mas tem uma data de entrega
                // aonde o tutor deveria dar a nota da avalicao offline
                $atividade_offline = array_key_exists('nosubmissions', $atividade) && $atividade->nosubmissions == 1;

                // Se for uma ativade online e o aluno nao enviou
                if (!$atividade_offline && is_null($atividade->submission_date)) {
                    $tipo = dado_historico_atribuicao_notas::ATIVIDADE_NAO_ENTREGUE;
                } //Atividade entregue e não avaliada
                elseif (is_null($atividade->grade) || $atividade->grade < 0) {

                    $tipo = dado_historico_atribuicao_notas::ATIVIDADE_ENTREGUE_NAO_AVALIADA;


                    //atividade online o calculo do atraso eh feito com a data de envio ou edicao
                    $submission_date = ((int)$atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;

                    //atividade offline o calculo do atraso eh feito com a data da tarefa, duedate
                    if ($atividade_offline) {
                        $submission_date = (int)$atividade->duedate;
                    }

                    $datadiff = get_datetime_from_unixtime($timenow)->diff(get_datetime_from_unixtime($submission_date));
                    $atraso = (int)$datadiff->format("%a");
                } //Atividade entregue e avalidada
                elseif ((int)$atividade->grade >= 0) {
                    if (!array_key_exists('grade_created', $atividade)) {
                        $atividade->grade_created = $atividade->timemodified;
                    }

                    //quanto tempo desde a entrega até a correção
                    $data_correcao = ((int)$atividade->grade_created != 0) ? $atividade->grade_created : $atividade->grade_modified;
                    $data_envio = ((int)$atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;

                    //atividade offline nao tem data de envio, logo a data de envio é a data de correcao
                    if ($atividade_offline) {
                        $data_envio = (int)$atividade->duedate;
                    }

                    $datadiff = get_datetime_from_unixtime($data_correcao)->diff(get_datetime_from_unixtime($data_envio));
                    $atraso = (int)$datadiff->format("%a");

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

/*
 * Cabeçalho do relatorio historico atribuicao de notas
 */
function get_table_header_historico_atribuicao_notas($modulos)
{
    return get_table_header_atividades_vs_notas($modulos);
}

/*
 * Dados para o gráfico de historico atribuicao de notas
 */
function get_dados_grafico_historico_atribuicao_notas($curso_ufsc, $modulos, $tutores)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_historico_atribuicao_notas();
    $query_forum = query_postagens_forum();


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum);

    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {

        $count_nao_entregue = 0;
        $count_nao_avaliada = 0;
        $count_no_prazo = 0;
        $count_pouco_atraso = 0;
        $count_muito_atraso = 0;

        foreach ($array_dados as $id_aluno => $aluno) {

            foreach ($aluno as $atividade) {

                $atraso = null;

                // Atividade offline, não necessita de envio nem de arquivo ou texto mas tem uma data de entrega
                // aonde o tutor deveria dar a nota da avalicao offline
                $atividade_offline = array_key_exists('nosubmissions', $atividade) && $atividade->nosubmissions == 1;

                // Não enviou a atividade
                if (!$atividade_offline && is_null($atividade->submission_date)) {
                    $count_nao_entregue++;
                } //Atividade entregue e não avaliada
                elseif (is_null($atividade->grade) || $atividade->grade < 0) {
                    $count_nao_avaliada++;
                } //Atividade entregue e avalidada
                elseif ((int)$atividade->grade >= 0) {

                    if (!array_key_exists('grade_created', $atividade)) {
                        $atividade->grade_created = $atividade->timemodified;
                    }

                    //quanto tempo desde a entrega até a correção
                    $data_correcao = ((int)$atividade->grade_created != 0) ? $atividade->grade_created : $atividade->grade_modified;
                    $data_envio = ((int)$atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;

                    //atividade offline nao tem data de envio, logo a data de envio é a data de correcao
                    if ($atividade_offline) {
                        $data_envio = (int)$atividade->duedate;
                    }

                    $datadiff = get_datetime_from_unixtime($data_correcao)->diff(get_datetime_from_unixtime($data_envio));
                    $atraso = (int)$datadiff->format("%a");

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

/* -----------------
 * ---------------------------------------
 * Relatório de Lista: Atividades Não Postadas
 * ---------------------------------------
 * -----------------
 */

/**
 * Geração de dados para Lista: Atividades Não Postadas
 *
 * @param $curso_ufsc
 * @param $curso_moodle
 * @param $modulos
 * @param $tutores
 * @return array
 */
function get_dados_estudante_sem_atividade_postada($curso_ufsc, $curso_moodle, $modulos, $tutores)
{

    // Consulta
    $query_alunos_grupo_tutoria = query_estudante_sem_atividade_postada();

    return get_todo_list_data($curso_ufsc, $curso_moodle, $modulos, $tutores, $query_alunos_grupo_tutoria, 'estudante_sem_atividade_postada');
}

/* -----------------
 * ---------------------------------------
 * Lista: Atividades não Avaliadas
 * ---------------------------------------
 * -----------------
 */

/**
 * Geração de dados para Lista: Atividades não Avaliadas
 *
 * @param string $curso_ufsc
 * @param string $curso_moodle
 * @param array $modulos
 * @param array $tutores
 * @return array
 */
function get_dados_estudante_sem_atividade_avaliada($curso_ufsc, $curso_moodle, $modulos, $tutores)
{

    // Consulta
    $query_alunos_grupo_tutoria = query_estudante_sem_atividade_avaliada();
    return get_todo_list_data($curso_ufsc, $curso_moodle, $modulos, $tutores, $query_alunos_grupo_tutoria, 'estudante_sem_atividade_avaliada');
}

/* -----------------
 * ---------------------------------------
 * Síntese: Avaliações em Atraso
 * ---------------------------------------
 * -----------------
 */

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @TODO ver se necessita colocar os foruns tambem e retirar o loop da query
 * @param array $modulos
 * @param string $curso_ufsc
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_nao_avaliadas($curso_ufsc, $curso_moodle, $modulos, $tutores)
{

    // Consulta
    $query_alunos_grupo_tutoria = query_atividades_nao_avaliadas();

    $query_forum = query_postagens_forum();

    $result_array = loop_atividades_e_foruns_sintese($curso_ufsc, $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum);

    $total_alunos = $result_array['total_alunos'];
    $total_atividades = $result_array['total_atividades'];
    $lista_atividade = $result_array['lista_atividade'];
    $associativo_atividade = $result_array['associativo_atividade'];


    $timenow = time();
    $prazo_avaliacao = (get_prazo_avaliacao() * 60 * 60 * 24);


    $somatorio_total_atrasos = array();
    foreach ($associativo_atividade as $grupo_id => $array_dados) {
        foreach ($array_dados as $results) {

            foreach ($results as $atividade) {

                if (!array_key_exists($grupo_id, $somatorio_total_atrasos)) {
                    $somatorio_total_atrasos[$grupo_id] = 0;
                }


                $atividade_nao_corrigida = false;
                if (array_key_exists('status', $atividade))
                    $atividade_nao_corrigida = $atividade->status == 'draft' && $atividade->submission_modified + $prazo_avaliacao < $timenow;

                $forum_nao_corrigido = array_key_exists('firstname', $atividade) && $atividade->submission_date != null && $atividade->grade == null;

                // Atividade offline, não necessita de envio nem de arquivo ou texto mas tem uma data de entrega
                // aonde o tutor deveria dar a nota da avalicao offline
                $offline_nao_corrigido = false;
                $atividade_offline = array_key_exists('nosubmissions', $atividade) && $atividade->nosubmissions == 1;
                if ($atividade_offline)
                    $offline_nao_corrigido = ($atividade->duedate + $prazo_avaliacao < $timenow) && $atividade->grade == null;

                if ($atividade_nao_corrigida || $forum_nao_corrigido || $offline_nao_corrigido) {

                    $lista_atividade[$grupo_id][$atividade->assignid]->incrementar_atraso();
                    $somatorio_total_atrasos[$grupo_id]++;
                }


            }
        }
    }


    $dados = array();
    foreach ($lista_atividade as $grupo_id => $grupo) {
        $data = array();
        $data[] = grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id);
        foreach ($grupo as $atividades) {
            $data[] = $atividades;
        }

        $data[] = new dado_media(($somatorio_total_atrasos[$grupo_id] * 100) / ($total_alunos[$grupo_id] * $total_atividades));
        $dados[] = $data;
    }

    return $dados;
}

/*
 * Cabeçalho para o sintese: avaliacoes em atraso
 */
function get_table_header_atividades_nao_avaliadas($modulos)
{
    $header = get_table_header_atividades_vs_notas($modulos);
    $header[''] = array('Média');
    return $header;
}


/* -----------------
 * ---------------------------------------
 * Síntese: atividades concluídas
 * ---------------------------------------
 * -----------------
 */

function get_dados_atividades_nota_atribuida($curso_ufsc, $curso_moodle, $modulos, $tutores)
{

    // Consulta
    $query_alunos_grupo_tutoria = query_atividades_nota_atribuida();
    $query_forum = query_postagens_forum();

    $result_array = loop_atividades_e_foruns_sintese($curso_ufsc, $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum);

    $total_alunos = $result_array['total_alunos'];
    $total_atividades = $result_array['total_atividades'];
    $lista_atividade = $result_array['lista_atividade'];
    $associativo_atividade = $result_array['associativo_atividade'];


    $somatorio_total_atrasos = array();
    foreach ($associativo_atividade as $grupo_id => $array_dados) {
        foreach ($array_dados as $id_aluno => $aluno) {
            foreach ($aluno as $atividade) {
                if (!is_null($atividade->grade))
                    $lista_atividade[$grupo_id][$atividade->assignid]->incrementar_atraso();
                if (!array_key_exists($grupo_id, $somatorio_total_atrasos)) {
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
            $data[] = $atividades;
        }
        $data[] = new dado_media(($somatorio_total_atrasos[$grupo_id] * 100) / ($total_alunos[$grupo_id] * $total_atividades));
        $dados[] = $data;
    }

    return $dados;
}

/*
 * Cabeçalho para o sintese: atividades concluidas
 */
function get_table_header_atividades_nota_atribuida($modulos)
{
    return get_table_header_atividades_nao_avaliadas($modulos);
}

/* -----------------
 * ---------------------------------------
 * Uso do Sistema pelo Tutor (horas)
 * ---------------------------------------
 * -----------------
 */

/**
 * @TODO arrumar media
 */
function get_dados_uso_sistema_tutor($curso_ufsc, $curso_moodle, $tutores)
{
    $middleware = Middleware::singleton();
    $lista_tutores = get_tutores_menu($curso_ufsc);

    $query = query_uso_sistema_tutor();

    //Query
    $dados = array();

    $timenow = time();
    $tempo_pesquisa = strtotime('-120 day', $timenow);

    foreach ($lista_tutores as $id => $tutor) {
        $result = $middleware->get_recordset_sql($query, array('userid' => $id, 'tempominimo' => $tempo_pesquisa));

        foreach ($result as $r) {
            $dados[$id][$r['dia']] = $r;
        }
    }


    // Intervalo de dias no formato d/m
    $intervalo_tempo = 120;
    $dias_meses = get_time_interval("P{$intervalo_tempo}D",'P1D','Y/m/d');

    //para cada resultado da busca ele verifica se esse dado bate no "calendario" criado com o
    //date interval acima
    $result = new GroupArray();


    foreach ($dados as $id_user => $datas) {

        //quanto tempo ele ficou logado
        $total_tempo = 0;

        foreach ($dias_meses as $dia) {
            if (array_key_exists($dia, $dados[$id_user])) {
                $horas = (float)$dados[$id_user][$dia]['horas'];
                $result->add($id_user, new dado_uso_sistema_tutor($horas));
                $total_tempo += $horas;

            } else {
                $result->add($id_user, new dado_uso_sistema_tutor('0'));
            }
        }

        $result->add($id_user, format_float($total_tempo/$intervalo_tempo,3,''));
        $result->add($id_user, $total_tempo);

    }
    $result = $result->get_assoc();


    $nomes_tutores = grupos_tutoria::get_tutores_curso_ufsc($curso_ufsc);

    //para cada resultado que estava no formato [id]=>[dados_acesso]
    // ele transforma para [tutor,dado_acesso1,dado_acesso2]
    $retorno = array();
    foreach ($result as $id => $values) {
        $dados = array();
        $nome = (array_key_exists($id, $nomes_tutores)) ? $nomes_tutores[$id] : $id;
        array_push($dados, new pessoa($nome, $id, $curso_moodle));
        foreach ($values as $value) {
            array_push($dados, $value);
        }
        $retorno[] = $dados;
    }

    return array('Tutores'=>$retorno);
}


function get_table_header_uso_sistema_tutor()
{
    $double_header = get_time_interval_com_meses('P120D', 'P1D','m/d');
    $double_header[''] = array('Media');
    $double_header[' '] = array('Total');
    return $double_header;
}

/**
 * @FIXME a data adicionada é do tipo Mes/dia, num futuro caso exiba mais de um ano tem de modificar para mostrar ano/mes/dia
 */
function get_dados_grafico_uso_sistema_tutor($modulo, $tutores, $curso_ufsc)
{
    $tutores = get_tutores_menu($curso_ufsc);
    $tempo_intervalo = 120;
    $dia_mes = get_time_interval("P{$tempo_intervalo}D", 'P1D','d/m');

    $dados = get_dados_uso_sistema_tutor($curso_ufsc, $curso_moodle = 0, $tutores);

    $dados_grafico = array();
    foreach($dados['Tutores'] as $tutor){
        $dados_tutor = array();
        $count_dias = 1;
        foreach ($dia_mes as $dia) {
                $dados_tutor[$dia] = $tutor[$count_dias]->__toString();
                $count_dias++;


        }
        $dados_grafico[$tutor[0]->get_name()] = $dados_tutor;
    }



    return $dados_grafico;
}

/* -----------------
 * ---------------------------------------
 * Uso do Sistema pelo Tutor (acesso)
 * ---------------------------------------
 * -----------------
 */

function get_dados_acesso_tutor($curso_ufsc, $curso_moodle, $tutores)
{
    $middleware = Middleware::singleton();

    // Consulta
    $query = query_acesso_tutor();

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
    $dias_meses = get_time_interval('P120D','P1D','d/m');


    //para cada resultado da busca ele verifica se esse dado bate no "calendario" criado com o
    //date interval acima
    $result = new GroupArray();
    foreach ($dados as $id => $datas) {
        foreach ($dias_meses as $dia) {
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
    foreach ($result as $id => $values) {
        $dados = array();
        $nome = (array_key_exists($id, $nomes_tutores)) ? $nomes_tutores[$id] : $id;
        array_push($dados, new pessoa($nome, $id, $curso_moodle));
        foreach ($values as $value) {
            array_push($dados, $value);
        }
        $retorno[] = $dados;
    }


    return array('Tutores' => $retorno);
}

/*
 * Cabeçalho para o relatorio de uso do sistema do tutor, cria um intervalo de tempo de 60 dias atras
 */
function get_table_header_acesso_tutor()
{
    return get_time_interval_com_meses('P120D', 'P1D', 'd/m');
}

/* -----------------
 * ---------------------------------------
 * Potenciais Evasões
 * ---------------------------------------
 * -----------------
 */

function get_dados_potenciais_evasoes($curso_ufsc, $curso_moodle, $modulos, $tutores)
{
    global $CFG;

    // Consulta
    $query_alunos_atividades = query_potenciais_evasoes();
    $query_forum = query_postagens_forum();


    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc, $modulos,
        $tutores, $query_alunos_atividades, $query_forum);

    //pega a hora atual para comparar se uma atividade esta atrasada ou nao
    $timenow = time();
    $dados = array();

    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $dados_modulos = array();
            $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);
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

function get_table_header_potenciais_evasoes($modulos)
{
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

/* /\/\/\/\/\/\/\/\/\/\/\/\/\/\/\
 * ISOLADOS
 * /\/\/\/\/\/\/\/\/\/\/\/\/\/\/\
 */

function get_table_header_modulos_atividades($modulos = array())
{


    $atividades_modulos = query_atividades_modulos($modulos);

    $group = new GroupArray();
    $modulos = array();
    $header = array();

    // Agrupa atividades por curso e cria um índice de cursos
    foreach ($atividades_modulos as $atividade) {
        $modulos[$atividade->course_id] = $atividade->course_name;
        $group->add($atividade->course_id, $atividade);
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
                $forum_url = new moodle_url('/mod/forum/view.php', array('id' => $atividade->idnumber));
                $dados[] = html_writer::link($forum_url, $atividade->itemname);
            }
        }
        $header[$course_link] = $dados;
    }
    return $header;
}

function get_header_estudante_sem_atividade_postada($size)
{
    $content = array();
    for ($index = 0; $index < $size - 1; $index++) {
        $content[] = '';
    }
    $header['Atividades não resolvidas'] = $content;
    return $header;
}

/**
 * @param $curso_ufsc
 * @param $curso_moodle
 * @param $modulos
 * @param $tutores
 * @param $query_alunos_atividades
 * @param $relatorio
 * @return array
 *
 * Dados para os relatórios Lista: Atividades não postadas e Lista: Atividades não avaliadas
 */
function get_todo_list_data($curso_ufsc, $curso_moodle, $modulos, $tutores, $query_alunos_atividades, $relatorio)
{
    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);
    $foruns_modulo = query_forum_modulo(array_keys($modulos));

    $listagem_forum = new GroupArray();
    foreach ($foruns_modulo as $forum) {
        $listagem_forum->add($forum->course_id, $forum);
    }
    $listagem_forum = $listagem_forum->get_assoc();


    $query_forum = query_postagens_forum();
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc, $modulos,
        $tutores, $query_alunos_atividades, $query_forum);


    $id_nome_modulos = get_id_nome_modulos();
    $id_nome_atividades = get_id_nome_atividades();

    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {

            $atividades_modulos = new GroupArray();

            //workaround time
            $count_foruns = -1;

            foreach ($aluno as $atividade) {

                $tipo_avaliacao = 'atividade';
                $nome_atividade = null;
                $atividade_sera_listada = true;
                $idnumber = null;

                // Atividade offline, não necessita de envio nem de arquivo ou texto mas tem uma data de entrega
                // aonde o tutor deveria dar a nota da avalicao offline
                $atividade_offline = array_key_exists('nosubmissions', $atividade) && $atividade->nosubmissions == 1;

                //workaround time
                if (array_key_exists('has_post', $atividade)) {
                    $count_foruns++;
                    $tipo_avaliacao = 'forum';

                    if (!array_key_exists($count_foruns, $listagem_forum[$atividade->courseid]))
                        $count_foruns = 0;

                    $nome_atividade = $listagem_forum[$atividade->courseid][$count_foruns]->itemname;
                    $idnumber = $listagem_forum[$atividade->courseid][$count_foruns]->idnumber;

                    if ($relatorio == 'estudante_sem_atividade_postada' && $atividade->has_post == 1) {

                        $atividade_sera_listada = false;
                    } elseif ($relatorio == 'estudante_sem_atividade_avaliada' &&
                        ($atividade->has_post == 1 && !is_null($atividade->grade) || $atividade->has_post == 0)
                    ) {
                        $atividade_sera_listada = false;
                    }


                } else {
                    $count_foruns = -1;

                    //logica para atividades offlines, para estudante_sem_atividade_postada atividades offlines nao devem ser listadas
                    //ja para estudante_sem_atividade_avaliada so se ele nao tiver nota
                    if ($atividade_offline &&
                        ($relatorio == 'estudante_sem_atividade_postada' ||
                            ($relatorio == 'estudante_sem_atividade_avaliada' && !is_null($atividade->grade)))
                    ) {
                        $atividade_sera_listada = false;

                    }

                    $nome_atividade = $id_nome_atividades[$atividade->assignid];
                }

                if ($atividade_sera_listada) {
                    $atividades_modulos->add($atividade->courseid, array('nome' => $nome_atividade, 'course_id' => $atividade->courseid,
                        'assign_id' => $atividade->assignid, 'tipo' => $tipo_avaliacao, 'idnumber' => $idnumber));
                }
            }


            $ativ_mod = $atividades_modulos->get_assoc();

            if (!empty($ativ_mod)) {

                $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

                foreach ($ativ_mod as $key => $modulo) {
                    $lista_atividades[] = new dado_modulo($key, $id_nome_modulos[$key]);
                    foreach ($modulo as $atividade) {
                        $lista_atividades[] = new dado_atividade($atividade['assign_id'], $atividade['course_id'],
                            $atividade['nome'], $atividade['tipo'], $atividade['idnumber']);
                    }
                }


                $estudantes[] = $lista_atividades;
            }
            $lista_atividades = null;
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] = $estudantes;
    }
    return $dados;
}



