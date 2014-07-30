<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Loop para a criação do array associativo com as atividades e foruns de um dado aluno fazendo as queries SQLs
 *
 * @param $query_atividades
 * @param $query_forum
 * @param $query_quiz
 * @param bool $query_course
 * @param null $query_nota_final
 * @param bool $is_activity
 * @param bool $is_orientacao
 * @throws Exception
 * @throws dml_read_exception
 * @return array 'associativo_atividade' => array( 'modulo' => array( 'id_aluno' => array( 'report_unasus_data', 'report_unasus_data' ...)))
 */
function loop_atividades_e_foruns_de_um_modulo($query_atividades, $query_forum, $query_quiz, $query_course = true, $query_nota_final = null, $is_activity = false, $is_orientacao = false) {
    // Middleware para as queries sql
    $middleware = Middleware::singleton();

    /** @var $report Factory */
    $report = Factory::singleton();

    $grupos = ($is_orientacao)
              ?  grupos_tutoria::get_grupos_orientacao($report->get_curso_ufsc(), $report->orientadores_selecionados)
              :  tutoria::get_grupos_tutoria($report->get_curso_ufsc(), $report->tutores_selecionados);

    $relationship = tutoria::get_relationship_tutoria($report->get_curso_ufsc());

    $cohort_estudantes = tutoria::get_relationship_cohort_estudantes($relationship->id);

    // Estrutura auxiliar de consulta ao LTI do Portfólio
    $lti_query_object = new LtiPortfolioQuery();

    /* Array associativo que irá armazenar para cada grupo de tutoria as atividades e foruns de um aluno num dado modulo
     * A atividade pode ser tanto uma avaliação de um dado módulo ou um fórum com sistema de avaliação
     *
     * associativo_atividades[modulo][id_aluno][atividade]  */
    $associativo_atividades = array();

    // Para cada grupo de tutoria
    foreach ($grupos as $grupo) {

        $group_array_do_grupo = new GroupArray();

        // Para cada modulo e suas atividades
        foreach ($report->atividades_cursos as $courseid => $atividades) {

            // Num módulo existem várias atividades, numa dada atividade ele irá pesquisar todas as notas dos alunos daquele
            // grupo de tutoria
            foreach ($atividades as $atividade) {

                if (is_a($atividade, 'report_unasus_assign_activity') && !empty($query_atividades)) {
                    $params = array('assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo_tutoria' => $grupo->id);
                    if ($query_course) {
                        $params['courseid'] = $courseid;
                        $params['enrol_courseid'] = $courseid;
                    }

                    $result = $middleware->get_records_sql($query_atividades, $params);

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
                } elseif (is_a($atividade, 'report_unasus_forum_activity') && !empty($query_forum)) {

                    $params = array(
                        'courseid' => $courseid,
                        'enrol_courseid' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $middleware->get_records_sql($query_forum, $params);

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
                } elseif (is_a($atividade, 'report_unasus_quiz_activity') && !empty($query_quiz)) {

                    $params = array(
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'courseid' => $courseid,
                        'enrol_courseid' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $middleware->get_records_sql($query_quiz, $params);

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
                } elseif (is_a($atividade, 'report_unasus_lti_activity')) {

                    $result = ($is_orientacao) ? $lti_query_object->get_report_data($atividade, $grupo->username_orientador, true)
                                               : $lti_query_object->get_report_data($atividade, $grupo->id);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $l) {

                        if (isset($l->not_found)) {
                            $data = new report_unasus_data_empty($atividade, $l);
                        } else {
                            $data = new report_unasus_data_lti($atividade, $l);
                        }

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($l->userid, $data);
                    }
                }
            }

            // Query de notas finais, somente para o relatório Boletim
            if (!is_null($query_nota_final)) {
                $params = array(
                    'courseid' => $courseid,
                    'enrol_courseid' => $courseid,
                    'relationship_id' => $relationship->id,
                    'cohort_relationship_id' => $cohort_estudantes->id,
                    'grupo_tutoria' => $grupo->id);

                $result = $middleware->get_records_sql($query_nota_final, $params);
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
function loop_atividades_e_foruns_sintese($query_atividades, $query_forum, $query_quiz, $loop = null, $is_orientacao = false) {
    $middleware = Middleware::singleton();

    /** @var $report Factory */
    $report = Factory::singleton();

    $relationship = tutoria::get_relationship_tutoria($report->get_curso_ufsc());
    $cohort_estudantes = tutoria::get_relationship_cohort_estudantes($relationship->id);

    // Recupera dados auxiliares
    $grupos = ($is_orientacao)
            ?  grupos_tutoria::get_grupos_orientacao($report->get_curso_ufsc(), $report->orientadores_selecionados)
            :  tutoria::get_grupos_tutoria($report->get_curso_ufsc(), $report->tutores_selecionados);

    // Estrutura auxiliar de consulta ao LTI do Portfólio
    $lti_query_object = new LtiPortfolioQuery();

    // FIXME: reescrever o código para não necessitar duas passadas no loop para esse caso
    if (is_null($loop) && $report->get_relatorio() == 'atividades_nota_atribuida') {
        $loop = loop_atividades_e_foruns_sintese($query_atividades, $query_forum, $query_quiz, true);
        $atividades_alunos_grupos = $report->get_dados_alunos_atividades_concluidas($loop['associativo_atividade'])->somatorio_modulos;
    }

    $associativo_atividade = array();
    $lista_atividade = array();
    $count = 0;

    // Listagem da atividades por tutor ou orientador
    if($is_orientacao){
        $ids_orientadores = '(';
        foreach($grupos as $grupo_or){
            $count++;
            $ids_orientadores .= (count($grupos) != $count) ? $grupo_or->id.','
                                                         : $grupo_or->id;
        }
        $ids_orientadores .= ')';

        $total_alunos = get_count_estudantes_orientacao($ids_orientadores, $report->get_curso_ufsc());
    }else{
        $total_alunos = get_count_estudantes($report->get_curso_ufsc());
    }

    $total_atividades = 0;

    foreach ($report->atividades_cursos as $atividades) {
        $total_atividades += count($atividades);
    }

    // Executa Consulta
    foreach ($grupos as $grupo) {
        $group_array_do_grupo = new GroupArray();
        $array_das_atividades = array();

        foreach ($report->atividades_cursos as $modulo => $atividades) {
            foreach ($atividades as $atividade) {

                if (is_a($atividade, 'report_unasus_assign_activity') && !empty($query_atividades)) {

                    // para cada assign um novo dado de avaliacao em atraso
                    $array_das_atividades['atividade_' . $atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array('courseid' => $modulo,
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'assignmentid3' => $atividade->id,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo_tutoria' => $grupo->id);

                    $result = $middleware->get_records_sql($query_atividades, $params);

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
                } elseif (is_a($atividade, 'report_unasus_forum_activity') && !empty($query_forum)) {

                    $array_das_atividades['forum_' . $atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'courseid' => $modulo,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $middleware->get_records_sql($query_forum, $params);

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
                } elseif (is_a($atividade, 'report_unasus_quiz_activity') && !empty($query_quiz)) {

                    $array_das_atividades['quiz_' . $atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'courseid' => $modulo,
                        'enrol_courseid'=> $modulo,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id);

                    $result = $middleware->get_records_sql($query_quiz, $params);

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
                } elseif (is_a($atividade, 'report_unasus_lti_activity')) {

                    // Criar o array caso ainda não tenha sido definido.
                    if (!isset($array_das_atividades[$atividade->id][$atividade->position])) {
                        $array_das_atividades[$atividade->id][$atividade->position] = array();
                    }

                    if(isset($total_alunos[$grupo->id])){
                        $array_das_atividades[$atividade->id][$atividade->position] = new dado_atividades_alunos(0);
                    }

                    $result = ($is_orientacao) ? $lti_query_object->get_report_data($atividade, $grupo->username_orientador, true)
                                               : $lti_query_object->get_report_data($atividade, $grupo->id);

                    if($is_orientacao){
                        $lti_query_object->count_lti_report($array_das_atividades, $total_alunos, $atividade, $grupo->username_orientador, $is_orientacao);
                    }else{
                        // $total_alunos = Calcula total por atividade LTI, $array_das_atividades = para cada grupo de tutoria preenche com 'dado_atividades_alunos'
                        $lti_query_object->count_lti_report($array_das_atividades, $total_alunos, $atividade, $grupo->id);
                    }

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $l) {

                        if (!isset($l->not_found)) {
                            $data = new report_unasus_data_lti($atividade, $l);

                            // Agrupa os dados por usuário
                            $group_array_do_grupo->add($l->userid, $data);
                        }
                    }
                }
            }

            if (isset($atividades_alunos_grupos)) {
                $count_atividades = isset($atividades_alunos_grupos[$grupo->id][$modulo]) ? $atividades_alunos_grupos[$grupo->id][$modulo] : 0;
                $array_das_atividades['modulo_' . $modulo] = new dado_atividades_alunos($total_alunos[$grupo->id], $count_atividades);
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
