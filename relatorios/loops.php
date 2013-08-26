<?php

/*
 * Loop para a criação do array associativo com as atividades e foruns de um dado aluno fazendo as queries SQLs
 *
 * @return array 'associativo_atividade' => array( 'modulo' => array( 'id_aluno' => array( 'report_unasus_data', 'report_unasus_data' ...)))
 */

function loop_atividades_e_foruns_de_um_modulo($query_conjunto_alunos, $query_forum, $query_quiz, $query_course = true, $query_nota_final = null) {
    // Middleware para as queries sql
    $middleware = Middleware::singleton();

    /** @var $factory Factory */
    $factory = Factory::singleton();


    // Recupera dados auxiliares
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($factory->get_curso_ufsc(), $factory->tutores_selecionados);

    /* Array associativo que irá armazenar para cada grupo de tutoria as atividades e foruns de um aluno num dado modulo
     * A atividade pode ser tanto uma avaliação de um dado módulo ou um fórum com sistema de avaliação
     *
     * associativo_atividades[modulo][id_aluno][atividade]  */
    $associativo_atividades = array();

    // Para cada grupo de tutoria
    foreach ($grupos_tutoria as $grupo) {

        $group_array_do_grupo = new GroupArray();

        // Para cada modulo e suas atividades
        foreach ($factory->modulos_selecionados as $courseid => $atividades) {

            // Num módulo existem várias atividades, numa dada atividade ele irá pesquisar todas as notas dos alunos daquele
            // grupo de tutoria
            foreach ($atividades as $atividade) {

                if (is_a($atividade, 'report_unasus_assign_activity')) {
                    $params = array('assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'curso_ufsc' => $factory->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);
                    if ($query_course) {
                        $params['courseid'] = $courseid;
                    }

                    $result = $middleware->get_records_sql($query_conjunto_alunos, $params);

                    // Para cada resultado da query de atividades
                    foreach ($result as $r) {
                        /** @var report_unasus_data_activity $data */
                        $data = new report_unasus_data_activity($atividade, $r);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($r->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_forum_activity')) {

                    $params = array(
                        'courseid' => $courseid,
                        'curso_ufsc' => $factory->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_forum, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $f) {
                        /** @var report_unasus_data_forum $data */
                        $data = new report_unasus_data_forum($atividade, $f);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($f->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_quiz_activity')) {

                    $params = array(
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'courseid' => $courseid,
                        'curso_ufsc' => $factory->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_quiz, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $q) {

                        /** @var report_unasus_data_forum $data */
                        $data = new report_unasus_data_quiz($atividade, $q);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($q->userid, $data);
                    }
                }
            }

            $lit_activities = get_lti_activities($courseid, $query_conjunto_alunos, $grupo->id, $group_array_do_grupo);
            $lit_activities = $lit_activities['lista_grupos'];

            // Query de notas finais, somente para o relatório Boletim
            if (!is_null($query_nota_final)) {
                $params = array(
                    'courseid' => $courseid,
                    'curso_ufsc' => $factory->get_curso_ufsc(),
                    'grupo_tutoria' => $grupo->id,
                    'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

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
 * @param $query_conjunto_alunos
 * @param $query_forum
 * @param $query_quiz
 * @return array (
 *
 *      'total_alunos' => array( 'polo' => total_alunos_no_polo ),
 *      'total_atividades' => int numero total de atividades,
 *      'lista_atividade' => array( 'modulo' => array( 'dado_atividade_nota_atribuida', 'dado_atividade_nota_atribuida' )),
 *      'associativo_atividade' => array( 'modulo' => array( 'id_aluno' => array( 'report_unasus_data', 'report_unasus_data' ...)))
 *
 * )
 */
function loop_atividades_e_foruns_sintese($query_conjunto_alunos, $query_forum, $query_quiz, $loop = null) {
    $middleware = Middleware::singleton();

    /** @var $factory Factory */
    $factory = Factory::singleton();

    // Recupera dados auxiliares
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($factory->get_curso_ufsc(), $factory->tutores_selecionados);

    if (is_null($loop) && $factory->get_relatorio() == 'atividades_nota_atribuida') {
        $loop = loop_atividades_e_foruns_sintese($query_conjunto_alunos, $query_forum, $query_quiz, true);
        $atividades_alunos_grupos = atividades_alunos_grupos($loop['associativo_atividade'])->somatorio_modulos;
    }

    $associativo_atividade = array();
    $lista_atividade = array();
    // Listagem da atividades por tutor
    $total_alunos = get_count_estudantes($factory->get_curso_ufsc());
    $total_atividades = 0;

    foreach ($factory->modulos_selecionados as $atividades) {
        $total_atividades += count($atividades);
    }

    // Executa Consulta
    foreach ($grupos_tutoria as $grupo) {
        $group_array_do_grupo = new GroupArray();
        $array_das_atividades = array();

        foreach ($factory->modulos_selecionados as $modulo => $atividades) {
            foreach ($atividades as $atividade) {

                if (is_a($atividade, 'report_unasus_assign_activity')) {

                    // para cada assign um novo dado de avaliacao em atraso
                    $array_das_atividades['atividade_' . $atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array('courseid' => $modulo,
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'assignmentid3' => $atividade->id,
                        'curso_ufsc' => $factory->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_conjunto_alunos, $params);

                    foreach ($result as $r) {
                        /** @var report_unasus_data_activity $data */
                        $data = new report_unasus_data_activity($atividade, $r);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($data->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_forum_activity')) {

                    $array_das_atividades['forum_' . $atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'courseid' => $modulo,
                        'curso_ufsc' => $factory->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_forum, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $f) {
                        /** @var report_unasus_data_forum $data */
                        $data = new report_unasus_data_forum($atividade, $f);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($f->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_quiz_activity')) {

                    $array_das_atividades['quiz_' . $atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'courseid' => $modulo,
                        'curso_ufsc' => $factory->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_quiz, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach ($result as $q) {

                        /** @var report_unasus_data_forum $data */
                        $data = new report_unasus_data_quiz($atividade, $q);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($q->userid, $data);
                    }
                }
            }

            $lit_activities = get_lti_activities($modulo, $query_conjunto_alunos, $grupo->id, $group_array_do_grupo, $array_das_atividades);
            $array_das_atividades = $lit_activities['lista_atividades'];

            if (isset($atividades_alunos_grupos)) {
                $total = isset($atividades_alunos_grupos[$grupo->id][$modulo]) ? $atividades_alunos_grupos[$grupo->id][$modulo] : 0;
                $array_das_atividades['modulo_' . $modulo] = new dado_atividades_alunos($total, $total_alunos[$grupo->id]);
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

/**
 * Atividades lit - sistema de tcc
 * @global type $DB
 * @param type $query_conjunto_alunos
 * @param type $grupo_tutoria
 * @param type $group_array_do_grupo
 * @param dado_atividades_nota_atribuida $array_das_atividades
 * @return type
 */
function get_lti_activities($courseid, $query_conjunto_alunos, $grupo_tutoria, $group_array_do_grupo, $array_das_atividades = null) {
    global $DB;

    // Middleware para as queries sql
    $middleware = Middleware::singleton();

    /** @var $factory Factory */
    $factory = Factory::singleton();

    /* Lti records */
    $lti = $DB->get_records_sql(query_lti(), array('course' => $courseid));

    /* Query alunos */
    $query_alunos = query_alunos_grupo_tutoria();
    $params = array('curso_ufsc' => $factory->get_curso_ufsc(),
        'grupo_tutoria' => $grupo_tutoria,
        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

    $alunos = $middleware->get_records_sql($query_alunos, $params);
    $user_ids = array();
    foreach ($alunos as $aluno) {
        array_push($user_ids, $aluno->id);
    }

    foreach ($lti as $lti_atividade) {
        //config lti
        $config = $DB->get_records_sql_menu(query_lti_config(), array('typeid' => $lti_atividade->typeid));
        $consumer_key = $config['resourcekey'];
        
        // WS Client
        $client = new SistemaTccClient($lti_atividade->baseurl, $consumer_key);
        $params = array($consumer_key => $consumer_key, 'user_ids' => $user_ids);

        $json = $client->post('reportingservice', $params);
        $result = json_decode($json);
        $total_alunos = array();
        
        $tcc_definition = get_tcc_definition($config['customparameters']);
        $prefix = $tcc_definition['type'] == 'portfolio' ? get_string('portfolio_prefix', 'report_unasus') : get_string('tcc_prefix', 'report_unasus');

        if (!is_null($result)) {
            foreach ($result as $r) {
                $userid = $r->tcc->user_id;

                //hubs
                foreach ($r->tcc->hubs as $hub) {
                    if(isset($hub->hub)) {
                        $hub = $hub->hub;
                    }
                    if (!array_key_exists($hub->position, $total_alunos)) {
                        $total_alunos[$hub->position] = 0;
                    }
                    $total_alunos[$hub->position]++;
                    
                    //criar atividade
                    $db_model = new stdClass();
                    $db_model->id = $lti_atividade->id;
                    $db_model->name = $prefix . $hub->position;
                    $db_model->deadline = $lti_atividade->completionexpected;
                    $db_model->course_id = $lti_atividade->course;
                    $db_model->course_name = $lti_atividade->course; //todo: selecionar nome do curso sql do lti
                    $atividade = new report_unasus_lti_activity($db_model);

                    $aluno = $alunos[$userid];

                    //criar user
                    $user = new stdClass();
                    $user->userid = $userid;
                    $user->name = $aluno->firstname;
                    $user->grade = $hub->grade;
                    $grade_date = new DateTime($hub->grade_date);
                    $user->grade_date = $grade_date->getTimestamp();

                    $user->status = $hub->state;
                    $submission_date = new DateTime($hub->state_date);
                    $user->submission_date = $submission_date->getTimestamp();
                    $user->cohort = $aluno->cohort;
                    $user->polo = $aluno->polo;

                    $data = new report_unasus_data_lti($atividade, $user);

                    //Agrupar dados por usuario
                    $group_array_do_grupo->add($user->userid, $data);
                }
            }
        }
        if (!is_null($array_das_atividades)) {
            foreach ($total_alunos as $key => $total) {
                $array_das_atividades['lti_' . $key] = new dado_atividades_nota_atribuida($total);
            }
        }
    }

    return array(
        'lista_grupos' => $group_array_do_grupo,
        'lista_atividades' => $array_das_atividades
    );
}