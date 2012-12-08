<?php

/*
 * Loop para a criação do array associativo com as atividades e foruns de um dado aluno fazendo as queries SQLs
 *
 */
function loop_atividades_e_foruns_de_um_modulo($curso_ufsc,
                                               $modulos, $tutores,
                                               $query_alunos_grupo_tutoria, $query_forum){
    // Middleware para as queries sql
    $middleware = Middleware::singleton();

    // Recupera dados auxiliares
    $grupos_tutoria = grupos_tutoria::get_grupos_tutoria($curso_ufsc, $tutores);

    /* Array associativo que irá armazenar para cada grupo de tutoria as atividades e foruns de um aluno num dado modulo
     * A atividade pode ser tanto uma avaliação de um dado módulo ou um fórum com sistema de avaliação
     *
     * associativo_atividades[modulo][id_aluno][atividade]  */
    $associativo_atividades = array();

    // Para cada grupo de tutoria
    foreach ($grupos_tutoria as $grupo) {

        $group_array_do_grupo = new GroupArray();

        // Para cada modulo e suas atividades
        foreach ($modulos as $modulo => $atividades) {

            // Num módulo existem várias atividades, numa dada atividade ele irá pesquisar todas as notas dos alunos daquele
            // grupo de tutoria
            foreach ($atividades as $atividade) {

                $params = array('courseid' => $modulo, 'assignmentid' => $atividade->assign_id,
                    'curso_ufsc' => $curso_ufsc, 'grupo_tutoria' => $grupo->id, 'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

                $result = $middleware->get_records_sql($query_alunos_grupo_tutoria, $params);

                // Para cada resultado da query de atividades
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


            //Pega quais e quantos foruns existem dentro de um modulo
            $foruns_modulos = query_forum_modulo($modulo);
            $group_foruns_modulos = new GroupArray();

            //Agrupa os foruns pelos seus respectivos modulos
            foreach ($foruns_modulos as $forum) {
                $group_foruns_modulos->add($forum->course_id, $forum);
            }
            $group_foruns_modulos = $group_foruns_modulos->get_assoc();

            if(!empty($group_foruns_modulos)){
                //Para cada forum dentro de um módulo ele faz a querry das respectivas avaliacoes
                foreach($group_foruns_modulos[$modulo] as $forum){

                    $params_forum =  array('courseid' => $modulo, 'curso_ufsc' => $curso_ufsc, 'grupo_tutoria' => $grupo->id,
                        'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE, 'forumid' => $forum->idnumber, 'idforumitem' => $forum->id);

                    $result_forum = $middleware->get_records_sql($query_forum, $params_forum);
                    $forum_duedate = query_forum_duedate($forum->idnumber);
                    // para cada aluno adiciona a listagem de atividades
                    foreach($result_forum as $f){
                        $f->assignid = $f->id;
                        $group_array_do_grupo->add($f->id, $f);
                        $f->duedate = isset($forum_duedate->completionexpected) ? $forum_duedate->completionexpected : null;
                    }

                }
            }


        }
        $associativo_atividades[$grupo->id] = $group_array_do_grupo->get_assoc();
    }

    return $associativo_atividades;

}