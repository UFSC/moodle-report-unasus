<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Loop para a criação do array associativo com as atividades e foruns de um dado aluno fazendo as queries SQLs
 *
 * @param $query_atividades
 * @param $query_forum
 * @param $query_quiz
 * @param $query_db
 * @param $query_scorm
 * @param null $query_nota_final
 * @param bool $is_activity
 * @param bool $is_orientacao
 * @throws Exception
 * @throws dml_read_exception
 * @return array 'associativo_atividade' => array( 'modulo' => array( 'id_aluno' => array( 'report_unasus_data', 'report_unasus_data' ...)))
 */
function loop_atividades_e_foruns_de_um_modulo($query_atividades, $query_forum, $query_quiz, $query_lti,
                                               $query_db, $query_scorm, $query_nota_final = null, $is_activity = false, $is_orientacao = false) {

    global $DB;

    /** @var $report report_unasus_factory */
    $report = report_unasus_factory::singleton();

    $grupos = ($is_orientacao)
              ?  local_tutores_grupo_orientacao::get_grupos_orientacao($report->get_categoria_turma_ufsc(), $report->orientadores_selecionados)
              :  local_tutores_grupos_tutoria::get_grupos_tutoria($report->get_categoria_turma_ufsc(), $report->tutores_selecionados);

    $relationship = local_tutores_grupos_tutoria::get_relationship_tutoria($report->get_categoria_turma_ufsc());
    $cohort_estudantes = local_tutores_grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

    // Estrutura auxiliar de consulta ao LTI do Portfólio
    $lti_query_object = new LtiPortfolioQuery();

    /* Array associativo que irá armazenar para cada grupo de tutoria as atividades e foruns de um aluno num dado modulo
     * A atividade pode ser tanto uma avaliação de um dado módulo ou um fórum com sistema de avaliação
     *
     * associativo_atividades[modulo][id_aluno][atividade]  */
    $associativo_atividades = array();

    // Para cada grupo de tutoria
    foreach ($grupos as $grupo) {

        $group_array_do_grupo = new report_unasus_GroupArray();

        // Para cada modulo e suas atividades
        foreach ($report->atividades_cursos as $courseid => $atividades) {

            // Num módulo existem várias atividades, numa dada atividade ele irá pesquisar todas as notas dos alunos daquele
            // grupo de tutoria
            foreach ($atividades as $atividade) {

                // atividade de envio (assign)
                if (is_a($atividade, 'report_unasus_assign_activity') && !empty($query_atividades)) {
                    $params = array(
                        'courseid' => $courseid,
                        'enrol_courseid' => $courseid,
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id);

                    $result = $DB->get_records_sql($query_atividades, $params);

                    // Para cada resultado da query de atividades
                    foreach ($result as $r) {

                        if($is_activity){
                            if ((!$r->enrol) || (!empty($atividade->grouping) &&
                                            !$report->is_member_of($atividade->grouping, $courseid, $r->userid))) {
                                $data = new report_unasus_data_empty($atividade, $r);
                            } else {
                                $data = new report_unasus_data_activity($atividade, $r);
                            }
                        }else {
                            if (!empty($atividade->grouping) &&
                                    !$report->is_member_of($atividade->grouping, $courseid, $r->userid)) {
                                $data = new report_unasus_data_empty($atividade, $r);
                            } else {
                                $data = new report_unasus_data_activity($atividade, $r);
                            }
                        }


                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($r->userid, $data);
                    }
                // atividade de forum
                } elseif (is_a($atividade, 'report_unasus_forum_activity') && !empty($query_forum)) {

                    $params = array(
                        'courseid' => $courseid,
                        'enrol_courseid' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $DB->get_records_sql($query_forum, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $f) {

                        if($is_activity){
                            if ((!$f->enrol) || (!empty($atividade->grouping) &&
                                            !$report->is_member_of($atividade->grouping, $courseid, $f->userid))) {
                                $data = new report_unasus_data_empty($atividade, $f);
                            } else {
                                $data = new report_unasus_data_forum($atividade, $f);
                            }
                        } else {
                            if (!empty($atividade->grouping) &&
                                    !$report->is_member_of($atividade->grouping, $courseid, $f->userid)) {
                                $data = new report_unasus_data_empty($atividade, $f);
                            } else {
                                $data = new report_unasus_data_forum($atividade, $f);
                            }
                        }
                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($f->userid, $data);
                    }
                // atividade de quiz
                } elseif (is_a($atividade, 'report_unasus_quiz_activity') && !empty($query_quiz)) {

                    $params = array(
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'courseid' => $courseid,
                        'enrol_courseid' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $DB->get_records_sql($query_quiz, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $q) {

                        if($is_activity){
                            if ((!$q->enrol) || (!empty($atividade->grouping) &&
                                            !$report->is_member_of($atividade->grouping, $courseid, $q->userid))) {
                                $data = new report_unasus_data_empty($atividade, $q);
                            } else {
                                $data = new report_unasus_data_quiz($atividade, $q);
                            }
                        } else {
                            if (!empty($atividade->grouping) &&
                                    !$report->is_member_of($atividade->grouping, $courseid, $q->userid)) {
                                $data = new report_unasus_data_empty($atividade, $q);
                            } else {
                                $data = new report_unasus_data_quiz($atividade, $q);
                            }
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($q->userid, $data);
                    }
                // atividade de database
                } elseif (is_a($atividade, 'report_unasus_db_activity') && !empty($query_db)) {

                    $params = array(
                        'courseid' => $courseid,
                        'id_activity' => $atividade->id,
                        //'enrol_courseid' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id
                    );

                    $result = $DB->get_records_sql($query_db, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $q) {

                        if($is_activity){
                            if ((!$q->enrol) || (!empty($atividade->grouping) &&
                                    !$report->is_member_of($atividade->grouping, $courseid, $q->userid))) {
                                $data = new report_unasus_data_empty($atividade, $q);
                            } else {
                                $data = new report_unasus_data_db($atividade, $q);
                            }
                        } else {
                            if (!empty($atividade->grouping) &&
                                !$report->is_member_of($atividade->grouping, $courseid, $q->userid)) {
                                $data = new report_unasus_data_empty($atividade, $q);
                            } else {
                                $data = new report_unasus_data_db($atividade, $q);
                            }
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($q->userid, $data);
                    }
                  // todo: colocar de volta as linhas abaixo após testes do TCC
                } elseif (is_a($atividade, 'report_unasus_lti_activity') && !empty($query_lti)) {
                    $params = array(
                        'courseid' => $courseid,
                        'id_activity' => $atividade->id,
                    //    'enrol_courseid' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id
                    );

                    $result = $DB->get_records_sql($query_lti, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $f) {

                        if($is_activity){
                            if ((!$f->enrol) || (!empty($atividade->grouping) &&
                                    !$report->is_member_of($atividade->grouping, $courseid, $f->userid))) {
                                $data = new report_unasus_data_empty($atividade, $f);
                            } else {
                                $data = new report_unasus_data_forum($atividade, $f);
                            }
                        } else {
                            if (!empty($atividade->grouping) &&
                                !$report->is_member_of($atividade->grouping, $courseid, $f->userid)) {
                                $data = new report_unasus_data_empty($atividade, $f);
                            } else {
                                $data = new report_unasus_data_lti($atividade, $f);
                            }
                        }
                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($f->userid, $data);
                    }

                } elseif (is_a($atividade, 'report_unasus_lti_activity_tcc')) {
                    // AKI 1
                    // todo: pegar atividade correta para testes
                    $result = $lti_query_object->get_report_data($atividade, $grupo->id, $is_orientacao);

                    foreach ($result as $l) {
                        $data = new report_unasus_data_lti_TCC($l);
                        $group_array_do_grupo->add_exclusive($l->userid, $data);
                    }
                }
            }

            // Query de notas finais, somente para o relatório Boletim
            if (!empty($query_nota_final)) {
                $params = array(
                    'courseid' => $courseid,
                    'enrol_courseid' => $courseid,
                    'relationship_id' => $relationship->id,
                    'cohort_relationship_id' => $cohort_estudantes->id,
                    'grupo' => $grupo->id);

                $result = $DB->get_records_sql($query_nota_final, $params);
                if ($result != false) {
                    foreach ($result as $nf) {
                        /** @var report_unasus_data_nota_final $data */
                        $data = new report_unasus_data_nota_final($nf);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($nf->userid, $data);
                    }
                }
            }
        }
        $associativo_atividades[$grupo->id] = $group_array_do_grupo->get_assoc();
    }

    return $associativo_atividades;
}

/**
 * @param $query_atividades
 * @param $query_forum
 * @param $query_quiz
 * @param null $loop
 * @param bool $is_orientacao
 * @throws Exception
 * @throws dml_read_exception
 * @return array (
 *
 *      'total_alunos' => array( 'polo' => total_alunos_no_polo ),
 *      'total_atividades' => int numero total de atividades,
 *      'lista_atividade' => array( 'modulo' => array( 'dado_atividade_nota_atribuida', 'dado_atividade_nota_atribuida' )),
 *      'associativo_atividade' => array( 'modulo' => array( 'id_aluno' => array( 'report_unasus_data', 'report_unasus_data' ...)))
 *
 * )
 */
function loop_atividades_e_foruns_sintese($query_atividades, $query_forum, $query_quiz, $query_lti, $loop = null, $is_orientacao = false, $query_database = null, $query_scorm = null, $config = array()) {

    global $DB;

    /** @var $report report_unasus_factory */
    $report = report_unasus_factory::singleton();

    $relationship = local_tutores_grupos_tutoria::get_relationship_tutoria($report->get_categoria_turma_ufsc());
    $cohort_estudantes = local_tutores_grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

    // Recupera dados auxiliares
    $grupos = ($is_orientacao)
            ?  local_tutores_grupo_orientacao::get_grupos_orientacao($report->get_categoria_turma_ufsc(), $report->orientadores_selecionados)
            :  local_tutores_grupos_tutoria::get_grupos_tutoria($report->get_categoria_turma_ufsc(), $report->tutores_selecionados);

    // Estrutura auxiliar de consulta ao LTI do Portfólio
    $lti_query_object = new LtiPortfolioQuery();

    // FIXME: reescrever o código para não necessitar duas passadas no loop para esse caso
    if ((is_null($loop) && $report->get_relatorio() == 'atividades_nota_atribuida') ||
        (is_null($loop) && $report->get_relatorio() == 'atividades_concluidas_agrupadas')) {
        $loop = loop_atividades_e_foruns_sintese($query_atividades, $query_forum, $query_quiz, $query_lti, true);
        $atividades_alunos_grupos = $report->get_dados_alunos_atividades_concluidas($loop['associativo_atividade'])->somatorio_modulos;
    }

    $associativo_atividade = array();
    $lista_atividade = array();

    if($is_orientacao){
        $total_alunos = report_unasus_get_count_estudantes_orientacao($report->get_categoria_turma_ufsc());
    } else {
        $total_alunos = report_unasus_get_count_estudantes($report->get_categoria_turma_ufsc());
    }

    $total_atividades = 0;

    foreach ($report->atividades_cursos as $atividades) {
        $total_atividades += count($atividades);
    }

    // Executa Consulta
    foreach ($grupos as $grupo) {
        $group_array_do_grupo = new report_unasus_GroupArray();
        $array_das_atividades = array();

        foreach ($report->atividades_cursos as $modulo => $atividades) {
            foreach ($atividades as $atividade) {
                if (is_a($atividade, 'report_unasus_assign_activity') && !empty($query_atividades) && array_search($atividade->id, $config)) {

                    // para cada assign um novo dado de avaliacao em atraso
                    $array_das_atividades['atividade_' . $atividade->id] = new report_unasus_dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array('courseid' => $modulo,
                        'enrol_courseid' => $modulo,
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'assignmentid3' => $atividade->id,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id);

                    $result = $DB->get_records_sql($query_atividades, $params);

                    foreach ($result as $r) {

                        if (!empty($atividade->grouping) &&
                            !$report->is_member_of($atividade->grouping, $atividade->course_id, $r->userid)) {
                            $data = new report_unasus_data_empty($atividade, $r);
                        } else {
                            $data = new report_unasus_data_activity($atividade, $r);
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($data->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_forum_activity') && !empty($query_forum) && array_search($atividade->id, $config)) {

                    $array_das_atividades['forum_' . $atividade->id] = new report_unasus_dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'courseid' => $modulo,
                        'enrol_courseid' => $modulo,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $DB->get_records_sql($query_forum, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $f) {

                        if (!empty($atividade->grouping) &&
                            !$report->is_member_of($atividade->grouping, $atividade->course_id, $f->userid)) {
                            $data = new report_unasus_data_empty($atividade, $f);
                        } else {
                            $data = new report_unasus_data_forum($atividade, $f);
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($f->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_quiz_activity') && !empty($query_quiz) && array_search($atividade->id, $config)) {

                    $array_das_atividades['quiz_' . $atividade->id] = new report_unasus_dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'courseid' => $modulo,
                        'enrol_courseid'=> $modulo,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $DB->get_records_sql($query_quiz, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $q) {

                        if (!empty($atividade->grouping) &&
                            !$report->is_member_of($atividade->grouping, $atividade->course_id, $q->userid)) {
                            $data = new report_unasus_data_empty($atividade, $q);
                        } else {
                            $data = new report_unasus_data_quiz($atividade, $q);
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($q->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_db_activity') && !empty($query_database) && array_search($atividade->id, $config)) {

                    $array_das_atividades['database_'.$atividade->id] = new report_unasus_dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'id_activity' => $atividade->id,
                        'courseid' => $modulo,
                        'enrol_courseid' => $modulo,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'coursemoduleid' => $atividade->coursemoduleid
                    );

                    $result = $DB->get_records_sql($query_database, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $d) {

                        if (!empty($atividade->grouping) &&
                            !$report->is_member_of($atividade->grouping, $atividade->course_id, $d->userid)
                        ) {
                            $data = new report_unasus_data_empty($atividade, $d);
                        } else {
                            $data = new report_unasus_data_db($atividade, $d);
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($d->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_scorm_activity') && !empty($query_scorm) && array_search($atividade->id, $config)) {

                    $array_das_atividades['scorm_'.$atividade->id] = new report_unasus_dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'id_activity' => $atividade->id,
                        'courseid' => $modulo,
                        'enrol_courseid' => $modulo,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                    );

                    $result = $DB->get_records_sql($query_scorm, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $s) {

                        if (!empty($atividade->grouping) &&
                            !$report->is_member_of($atividade->grouping, $atividade->course_id, $s->userid)
                        ) {
                            $data = new report_unasus_data_empty($atividade, $s);
                        } else {
                            $data = new report_unasus_data_scorm($atividade, $s);
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($s->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_lti_activity') && !empty($query_lti) && array_search($atividade->id, $config)) {
                    $array_das_atividades['lti_'.$atividade->id] = new report_unasus_dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'id_activity' => $atividade->id,
                        'courseid' => $modulo,
                        'enrol_courseid' => $modulo,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                    );

                    $result = $DB->get_records_sql($query_lti, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $s) {

                        if (!empty($atividade->grouping) &&
                            !$report->is_member_of($atividade->grouping, $atividade->course_id, $s->userid)
                        ) {
                            $data = new report_unasus_data_empty($atividade, $s);
                        } else {
                            $data = new report_unasus_data_lti($atividade, $s);
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($s->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_lti_activity_TCC')) {

                    // Criar o array caso ainda não tenha sido definido.
                    if (!isset($array_das_atividades[$atividade->id][$atividade->position])) {
                        $array_das_atividades[$atividade->id][$atividade->position] = array();
                    }

                    if(isset($total_alunos[$grupo->id])){
                        $array_das_atividades[$atividade->id][$atividade->position] = new report_unasus_dado_atividades_alunos_render(0);
                    }

                    $result = $lti_query_object->get_report_data($atividade, $grupo->id, $is_orientacao);

                    // $total_alunos = Calcula total por atividade LTI, $array_das_atividades = para cada grupo de tutoria preenche com 'dado_atividades_alunos'
                    $lti_query_object->count_lti_report($array_das_atividades, $total_alunos, $atividade, $grupo->id, $is_orientacao);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $l) {

                        if (!isset($l->not_found)) {
                            $data = new report_unasus_data_lti_TCC($l);

                            // Agrupa os dados por usuário
                            $group_array_do_grupo->add_exclusive($l->userid, $data);
                        }
                    }
                }
            }

            if (isset($atividades_alunos_grupos)) {
                $count_atividades = isset($atividades_alunos_grupos[$grupo->id][$modulo]) ? $atividades_alunos_grupos[$grupo->id][$modulo] : 0;
                $array_das_atividades['modulo_' . $modulo] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo->id], $count_atividades);
            }
        }

        $lista_atividade[$grupo->id] = $array_das_atividades;

        $associativo_atividade[$grupo->id] = $group_array_do_grupo->get_assoc();
    }

    return array('total_alunos' => $total_alunos,
        'total_atividades' => $total_atividades,
        'lista_atividade' => $lista_atividade,
        'associativo_atividade' => $associativo_atividade);
}
