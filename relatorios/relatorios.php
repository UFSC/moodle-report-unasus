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
function get_dados_atividades_vs_notas($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{
    // Dado Auxiliar
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    // Consultas
    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_forum = query_postagens_forum($polos);
    $query_quiz = query_quiz($polos);


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);


            foreach ($aluno as $atividade) {
                /** @var report_unasus_data $atividade */
                $atraso = null;

                //Se atividade não tem data de entrega, não tem entrega e nem nota
                if (!$atividade->source_activity->has_deadline() && !$atividade->has_submitted() && !$atividade->has_grade()) {
                    $tipo = dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA;
                } else {

                    //Atividade pro futuro
                    if ($atividade->is_a_future_due()) {
                        $tipo = dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA;
                    }

                    //Entrega atrasada
                    if ($atividade->is_submission_due()) {
                        $tipo = dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE;
                    }

                    //Atividade entregue e necessita de nota
                    if ($atividade->is_grade_needed()) {
                        $atraso = $atividade->grade_due_days();
                        $tipo = dado_atividades_vs_notas::CORRECAO_ATRASADA;
                    }

                    //Atividade tem nota
                    if ($atividade->has_grade()) {
                        $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA;
                    }


                }

                $lista_atividades[] = new dado_atividades_vs_notas($tipo, $atividade->source_activity->id, $atividade->grade, $atraso);
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
    return get_table_header_modulos_atividades($modulos);
}


function get_dados_grafico_atividades_vs_notas($curso_ufsc, $modulos, $tutores, $polos)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

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

                //Se atividade não tem data de entrega e nem nota
                if (!$atividade->source_activity->has_deadline() && !$atividade->has_grade()) {
                    $count_sem_prazo++;
                } else {

                    //Atividade pro futuro
                    if ($atividade->is_a_future_due()) {
                        $count_nao_realizada++;
                    }

                    //Entrega atrasada
                    if ($atividade->is_submission_due()) {
                        $count_nao_entregue++;
                    }

                    //Atividade entregue e necessita de nota
                    if ($atividade->is_grade_needed()) {
                        $atraso = $atividade->grade_due_days();
                        ($atraso > $CFG->report_unasus_prazo_maximo_avaliacao) ? $count_muito_atraso++ : $count_pouco_atraso++;
                    }

                    //Atividade tem nota
                    if ($atividade->has_grade()) {
                        $count_nota_atribuida++;
                    }


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
function get_dados_entrega_de_atividades($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);

    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

            foreach ($aluno as $atividade) {
                /** @var report_unasus_data $atividade */
                $atraso = null;

                // Se a atividade não foi entregue
                if (!$atividade->has_submitted()) {

                    if (!$atividade->source_activity->has_deadline()) {
                        // E não tem entrega prazo
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA;

                    } elseif ($atividade->is_a_future_due()) {
                        //atividade com data de entrega no futuro, nao entregue mas dentro do prazo
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_MAS_NO_PRAZO;
                    } else {
                        // Atividade nao entregue e atrasada
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_FORA_DO_PRAZO;
                    }
                } else {

                    // Entrega atrasada
                    if ($atividade->is_submission_due()) {
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                    } else {
                        $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO;
                    }

                    $atraso = $atividade->submission_due_days();
                }
                $lista_atividades[] = new dado_entrega_de_atividades($tipo, $atividade->source_activity->id, $atraso);
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
    return get_table_header_modulos_atividades($modulos);
}

/*
 * Dados para o gráfico do relatorio entrega de atividadas
 */
function get_dados_grafico_entrega_de_atividades($curso_ufsc, $modulos, $tutores, $polos)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);


    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        //variáveis soltas para melhor entendimento
        $count_entregue_no_prazo = 0;
        $count_pouco_atraso = 0;
        $count_muito_atraso = 0;
        $count_nao_entregue_mas_no_prazo = 0;
        $count_sem_prazo = 0;
        $count_nao_entregue_fora_prazo = 0;


        foreach ($array_dados as $id_aluno => $aluno) {

            foreach ($aluno as $atividade) {
                $atraso = null;

                //Se atividade não tem data de entrega e nem nota
                if (!$atividade->source_activity->has_deadline() && !$atividade->has_grade()) {
                    $count_sem_prazo++;
                } else {

                    //Entrega atrasada
                    if ($atividade->is_submission_due()) {

                        if ($atividade->is_a_future_due()) {
                            //atividade com data de entrega no futuro, nao entregue mas dentro do prazo
                            $count_nao_entregue_mas_no_prazo++;
                        } else {
                            // Atividade nao entregue e atrasada
                            $count_nao_entregue_fora_prazo++;
                        }
                    }

                    $atraso = $atividade->submission_due_days();
                    if ($atraso) {
                        ($atraso > $CFG->report_unasus_prazo_maximo_avaliacao) ? $count_muito_atraso++ : $count_pouco_atraso++;

                    } else {
                        $count_entregue_no_prazo++;
                    }

                    //Offlines nao precisam de entrega
                    if (!$atividade->source_activity->has_submission()) {
                        $count_nao_entregue_mas_no_prazo++;
                    }
                }
            }
        }
        $dados[grupos_tutoria::grupo_tutoria_to_string($curso_ufsc, $grupo_id)] =
            array($count_nao_entregue_mas_no_prazo,
                $count_nao_entregue_fora_prazo,
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
function get_dados_historico_atribuicao_notas($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);

    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {
            $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

            foreach ($aluno as $atividade) {

                /** @var report_unasus_data_activity $atividade */
                $atraso = null;
                $tipo = null;

                if (!$atividade->has_submitted()) {
                    $tipo = dado_historico_atribuicao_notas::ATIVIDADE_NAO_ENTREGUE;
                }

                //Atividade entregue e necessita de nota
                if ($atividade->is_grade_needed()) {
                    $atraso = $atividade->grade_due_days();
                    $tipo = dado_historico_atribuicao_notas::ATIVIDADE_ENTREGUE_NAO_AVALIADA;
                }


                //Atividade tem nota
                if ($atividade->has_grade()) {
                    $atraso = $atividade->grade_due_days();
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

                }

                $lista_atividades[] = new dado_historico_atribuicao_notas($tipo, $atividade->source_activity->id, $atraso);
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
    return get_table_header_modulos_atividades($modulos);
}

/*
 * Dados para o gráfico de historico atribuicao de notas
 */
function get_dados_grafico_historico_atribuicao_notas($curso_ufsc, $modulos, $tutores, $polos)
{
    global $CFG;

    // Consultas
    $query_alunos_grupo_tutoria = query_historico_atribuicao_notas($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);


    /*  associativo_atividades[modulo][id_aluno][atividade]
     *
     * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
     */
    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
        $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

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
function get_dados_estudante_sem_atividade_postada($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{
    return get_todo_list_data($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos, 'estudante_sem_atividade_postada');
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
function get_dados_estudante_sem_atividade_avaliada($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{
    return get_todo_list_data($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos, 'estudante_sem_atividade_avaliada');
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
function get_dados_atividades_nao_avaliadas($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{

    // Consulta
    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);

    $result_array = loop_atividades_e_foruns_sintese($curso_ufsc, $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

    $total_alunos = $result_array['total_alunos'];
    $total_atividades = $result_array['total_atividades'];
    $lista_atividade = $result_array['lista_atividade'];
    $associativo_atividade = $result_array['associativo_atividade'];

    // TODO: incluir esse prazo na estrutura de dados
    $timenow = time();
    $prazo_avaliacao = (get_prazo_avaliacao() * 60 * 60 * 24);

    $somatorio_total_atrasos = array();
    foreach ($associativo_atividade as $grupo_id => $array_dados) {
        foreach ($array_dados as $results) {

            foreach ($results as $atividade) {
                /** @var report_unasus_data $atividade */

                if (!array_key_exists($grupo_id, $somatorio_total_atrasos)) {
                    $somatorio_total_atrasos[$grupo_id] = 0;
                }

                if (!$atividade->has_grade() && $atividade->is_grade_needed()) {
                    if (is_a($atividade, 'report_unasus_data_activity')) {
                        $lista_atividade[$grupo_id]['atividade_' . $atividade->source_activity->id]->incrementar_atraso();
                    } elseif (is_a($atividade, 'report_unasus_data_forum')) {
                        $lista_atividade[$grupo_id]['forum_' . $atividade->source_activity->id]->incrementar_atraso();
                    } elseif (is_a($atividade, 'report_unasus_data_quiz')) {
                        $lista_atividade[$grupo_id]['quiz_' . $atividade->source_activity->id]->incrementar_atraso();
                    }

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
    $header = get_table_header_modulos_atividades($modulos);
    $header[''] = array('Média');
    return $header;
}


/* -----------------
 * ---------------------------------------
 * Síntese: atividades concluídas
 * ---------------------------------------
 * -----------------
 */

function get_dados_atividades_nota_atribuida($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{

    // Consulta
    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);

    $result_array = loop_atividades_e_foruns_sintese($curso_ufsc, $modulos, $tutores,
        $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

    $total_alunos = $result_array['total_alunos'];
    $total_atividades = $result_array['total_atividades'];
    $lista_atividade = $result_array['lista_atividade'];
    $associativo_atividade = $result_array['associativo_atividade'];


    $somatorio_total_atrasos = array();
    foreach ($associativo_atividade as $grupo_id => $array_dados) {
        foreach ($array_dados as $aluno) {
            foreach ($aluno as $atividade) {
                /** @var report_unasus_data $atividade */

                if (!array_key_exists($grupo_id, $somatorio_total_atrasos)) {
                    $somatorio_total_atrasos[$grupo_id] = 0;
                }


                if ($atividade->has_grade() && $atividade->is_grade_needed()) {

                    if (is_a($atividade, 'report_unasus_data_activity')) {
                        $lista_atividade[$grupo_id]['atividade_' . $atividade->source_activity->id]->incrementar_atraso();
                    } elseif (is_a($atividade, 'report_unasus_data_forum')) {
                        $lista_atividade[$grupo_id]['forum_' . $atividade->source_activity->id]->incrementar_atraso();
                    } elseif (is_a($atividade, 'report_unasus_data_quiz')) {
                        $lista_atividade[$grupo_id]['quiz_' . $atividade->source_activity->id]->incrementar_atraso();
                    }

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
function get_dados_uso_sistema_tutor($curso_ufsc, $curso_moodle, $tutores, $polos)
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
    $dias_meses = get_time_interval("P{$intervalo_tempo}D", 'P1D', 'Y/m/d');

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

        $result->add($id_user, format_float($total_tempo / $intervalo_tempo, 3, ''));
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

    return array('Tutores' => $retorno);
}


function get_table_header_uso_sistema_tutor()
{
    $double_header = get_time_interval_com_meses('P120D', 'P1D', 'm/d');
    $double_header[''] = array('Media');
    $double_header[' '] = array('Total');
    return $double_header;
}

/**
 * @FIXME a data adicionada é do tipo Mes/dia, num futuro caso exiba mais de um ano tem de modificar para mostrar ano/mes/dia
 */
function get_dados_grafico_uso_sistema_tutor($modulo, $tutores, $curso_ufsc, $polos)
{
    $tutores = get_tutores_menu($curso_ufsc);
    $tempo_intervalo = 120;
    $dia_mes = get_time_interval("P{$tempo_intervalo}D", 'P1D', 'd/m');

    $dados = get_dados_uso_sistema_tutor($curso_ufsc, $curso_moodle = 0, $tutores, $polos);

    $dados_grafico = array();
    foreach ($dados['Tutores'] as $tutor) {
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

function get_dados_acesso_tutor($curso_ufsc, $curso_moodle, $tutores, $polos)
{
    $middleware = Middleware::singleton();

    // Consulta
    $query = query_acesso_tutor();

    $params = array('tipo_tutor' => GRUPO_TUTORIA_TIPO_TUTOR, 'curso_ufsc' => get_curso_ufsc_id());
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
    $dias_meses = get_time_interval('P120D', 'P1D', 'd/m');


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

function get_dados_potenciais_evasoes($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos)
{
    global $CFG;

    // Consulta
    $query_alunos_atividades = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);


    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);

    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc, $modulos,
        $tutores, $query_alunos_atividades, $query_forum, $query_quiz);

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
                if (!array_key_exists($atividade->source_activity->course_id, $dados_modulos)) {
                    $dados_modulos[$atividade->source_activity->course_id] = new dado_potenciais_evasoes(sizeof($modulos[$atividade->source_activity->course_id]));
                }

                //para cada atividade nao feita ele adiciona uma nova atividade nao realizada naquele modulo
                if ($atividade->source_activity->has_submission() && is_null($atividade->submission_date) && !$atividade->is_a_future_due()) {
                    $dados_modulos[$atividade->source_activity->course_id]->add_atividade_nao_realizada();
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
    $nome_modulos = get_id_nome_modulos(get_curso_ufsc_id());
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
    $atividades_cursos = get_atividades_cursos($modulos);

    $header = array();

    foreach ($atividades_cursos as $course_id => $atividades) {
        $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
        $course_link = html_writer::link($course_url, $atividades[0]->course_name);

        $header[$course_link] = $atividades;
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
function get_todo_list_data($curso_ufsc, $curso_moodle, $modulos, $tutores, $polos, $relatorio)
{
    // Recupera dados auxiliares
    $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($curso_ufsc);
    $foruns_modulo = query_forum_courses(array_keys($modulos));

    $listagem_forum = new GroupArray();
    foreach ($foruns_modulo as $forum) {
        $listagem_forum->add($forum->course_id, $forum);
    }

    $query_alunos_grupo_tutoria = query_atividades($polos);
    $query_quiz = query_quiz($polos);
    $query_forum = query_postagens_forum($polos);

    $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($curso_ufsc, $modulos,
        $tutores, $query_alunos_grupo_tutoria, $query_forum, $query_quiz);


    $dados = array();
    foreach ($associativo_atividades as $grupo_id => $array_dados) {
        $estudantes = array();
        foreach ($array_dados as $id_aluno => $aluno) {

            $atividades_modulos = new GroupArray();


            foreach ($aluno as $atividade) {
                /** @var report_unasus_data $atividade */
                $tipo_avaliacao = 'atividade';
                $nome_atividade = null;
                $atividade_sera_listada = false;

                if ($relatorio == 'estudante_sem_atividade_postada' && !$atividade->has_submitted()) {
                    $atividade_sera_listada = true;
                }

                if ($relatorio == 'estudante_sem_atividade_avaliada' && !$atividade->has_grade() && $atividade->is_grade_needed()) {
                    $atividade_sera_listada = true;
                }

                if (is_a($atividade, 'report_unasus_data_forum')) {
                    $tipo_avaliacao = 'forum';
                }


                if ($atividade_sera_listada) {
                    $atividades_modulos->add($atividade->source_activity->course_id,
                        array('atividade' => $atividade, 'tipo' => $tipo_avaliacao));
                }
            }


            $ativ_mod = $atividades_modulos->get_assoc();

            if (!empty($ativ_mod)) {

                $lista_atividades[] = new pessoa($nomes_estudantes[$id_aluno], $id_aluno, $curso_moodle);

                foreach ($ativ_mod as $key => $modulo) {
                    $lista_atividades[] = new dado_modulo($key, $modulo[0]['atividade']->source_activity->course_name);
                    foreach ($modulo as $atividade) {
                        $lista_atividades[] = new dado_atividade($atividade['atividade']);
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



