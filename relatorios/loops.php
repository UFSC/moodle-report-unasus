<?php

/*
 * Loop para a criação do array associativo com as atividades e foruns de um dado aluno fazendo as queries SQLs
 *
 */
function loop_atividades_e_foruns_de_um_modulo($query_alunos_grupo_tutoria, $query_forum, $query_quiz, $query_course = true, $query_nota_final = null) {
    // Middleware para as queries sql
    $middleware = Middleware::singleton();

    /** @var $FACTORY Factory */
    $FACTORY = Factory::singleton();


    // Recupera dados auxiliares
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($FACTORY->get_curso_ufsc(), $FACTORY->tutores_selecionados);

    /* Array associativo que irá armazenar para cada grupo de tutoria as atividades e foruns de um aluno num dado modulo
     * A atividade pode ser tanto uma avaliação de um dado módulo ou um fórum com sistema de avaliação
     *
     * associativo_atividades[modulo][id_aluno][atividade]  */
    $associativo_atividades = array();

    // Para cada grupo de tutoria
    foreach ($grupos_tutoria as $grupo) {

        $group_array_do_grupo = new GroupArray();

        // Para cada modulo e suas atividades
        foreach ($FACTORY->modulos_selecionados as $courseid => $atividades) {

            // Num módulo existem várias atividades, numa dada atividade ele irá pesquisar todas as notas dos alunos daquele
            // grupo de tutoria
            foreach ($atividades as $atividade) {

                if (is_a($atividade, 'report_unasus_assign_activity')) {
                    $params = array('assignmentid' => $atividade->id,
                                    'assignmentid2' => $atividade->id,
                                    'curso_ufsc' => $FACTORY->get_curso_ufsc(),
                                    'grupo_tutoria' => $grupo->id,
                                    'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);
                    if($query_course){
                        $params['courseid'] = $courseid;
                    }

                    $result = $middleware->get_records_sql($query_alunos_grupo_tutoria, $params);

                    // Para cada resultado da query de atividades
                    foreach ($result as $r) {
                        /** @var report_unasus_data_activity $data  */
                        $data = new report_unasus_data_activity($atividade, $r);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($r->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_forum_activity')) {

                    $params =  array(
                        'courseid' => $courseid,
                        'curso_ufsc' => $FACTORY->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_forum, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach($result as $f) {
                        /** @var report_unasus_data_forum $data  */
                        $data = new report_unasus_data_forum($atividade, $f);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($f->userid, $data);
                    }

                } elseif (is_a($atividade, 'report_unasus_quiz_activity')){

                    $params =  array(
                        'assignmentid' => $atividade->id,
                        'courseid' => $courseid,
                        'curso_ufsc' => $FACTORY->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_quiz, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach($result as $q){

                        /** @var report_unasus_data_forum $data  */
                        $data = new report_unasus_data_quiz($atividade, $q);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($q->userid, $data);
                    }
                }
            }

            // Query de notas finais, somente para o relatório Boletim
            if(!is_null($query_nota_final)){
                $params =  array(
                    'courseid' => $courseid,
                    'curso_ufsc' => $FACTORY->get_curso_ufsc(),
                    'grupo_tutoria' => $grupo->id,
                    'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                $result = $middleware->get_records_sql($query_nota_final, $params);
                if($result != false){
                    foreach ($result as $nf){
                        /** @var report_unasus_data_nota_final $data  */
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

// TODO: alterar este loop para utilizar a nova estrutura de dados
function loop_atividades_e_foruns_sintese($query_alunos_grupo_tutoria, $query_forum, $query_quiz)
{
    $middleware = Middleware::singleton();

    /** @var $FACTORY Factory */
    $FACTORY = Factory::singleton();

    // Recupera dados auxiliares
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($FACTORY->get_curso_ufsc(), $FACTORY->tutores_selecionados);


    $associativo_atividade = array();
    $lista_atividade = array();
    // Listagem da atividades por tutor
    $total_alunos = get_count_estudantes($FACTORY->get_curso_ufsc());
    $total_atividades = 0;

    foreach ($FACTORY->modulos_selecionados as $atividades) {
        $total_atividades += count($atividades);
    }

    // Executa Consulta
    foreach ($grupos_tutoria as $grupo) {
        $group_array_do_grupo = new GroupArray();
        $array_das_atividades = array();

        foreach ($modulos as $modulo => $atividades) {
            foreach ($atividades as $atividade) {

                if (is_a($atividade, 'report_unasus_assign_activity')) {

                    // para cada assign um novo dado de avaliacao em atraso
                    $array_das_atividades['atividade_' . $atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array('courseid' => $modulo,
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'assignmentid3' => $atividade->id,
                        'curso_ufsc' => $FACTORY->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_alunos_grupo_tutoria, $params);

                    foreach ($result as $r) {
                        /** @var report_unasus_data_activity $data */
                        $data = new report_unasus_data_activity($atividade, $r);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($data->userid, $data);
                    }
                } elseif (is_a($atividade, 'report_unasus_forum_activity')) {

                    $array_das_atividades['forum_'.$atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params = array(
                        'courseid' => $modulo,
                        'curso_ufsc' => $FACTORY->get_curso_ufsc(),
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
                } elseif (is_a($atividade, 'report_unasus_quiz_activity')){

                    $array_das_atividades['quiz_'.$atividade->id] = new dado_atividades_nota_atribuida($total_alunos[$grupo->id]);

                    $params =  array(
                        'assignmentid' => $atividade->id,
                        'courseid' => $modulo,
                        'curso_ufsc' => $FACTORY->get_curso_ufsc(),
                        'grupo_tutoria' => $grupo->id,
                        'forumid' => $atividade->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                    $result = $middleware->get_records_sql($query_quiz, $params);

                    // para cada aluno adiciona a listagem de atividades
                    foreach($result as $q){

                        /** @var report_unasus_data_forum $data  */
                        $data = new report_unasus_data_quiz($atividade, $q);

                        // Agrupa os dados por usuário
                        $group_array_do_grupo->add($q->userid, $data);
                    }
                }
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

