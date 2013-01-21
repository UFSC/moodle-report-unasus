<?php

/* ---------------------------------------
 * QUERIES UTILIZADAS EM VÁRIOS RELATÓRIOS
 * ---------------------------------------
 */


/* Query para retornar os alunos pertencentes a um grupo de tutoria
 * Utilizada em diversos relatórios, necessita do middleware para rodar.
 *
 * Colunas  user_id
 *          firstname
 *          lastname
 *          grupo_id
 *
 * @return string
 */
function query_alunos_grupo_tutoria(){
    return "SELECT DISTINCT u.id, u.firstname, u.lastname, gt.id as grupo_id
                         FROM {user} u
                         JOIN {table_PessoasGruposTutoria} pg
                           ON (pg.matricula=u.username)
                         JOIN {table_GruposTutoria} gt
                           ON (gt.id=pg.grupo)
                        WHERE gt.curso=:curso_ufsc AND pg.grupo=:grupo_tutoria AND pg.tipo=:tipo_aluno";
}


/*
 * Query para retornar se um dado aluno possui postagens num forum de um dado módulo
 * se o tiver retorna a nota e outros dados da postagem.
 *
 * Colunas: id -> id do usuario
 *          firstname -> nome do usuario
 *          lastname -> sobrenome do usuario
 *          grupo_id -> grupo de tutoria que o aluno pertence
 *          userid_posts -> booleano se o aluno possui ou nao postagens naquele forum
 *          submission_date -> data de envio da primeira postagem do aluno naquele forum
 *          rawgrade-> nota final do aluno naquele forum, nota que já é a média estipulada nas configuraçoes do forum
 *          timemodified -> data de alteração da postagem
 *          itemid -> id do forum
 *
 * A primeira parte da query seleciona todos os alunos de um grupo de tutoria
 *
 * A segunda parte da query pega num dado course_module o forum informado em :forumid e verifica em todas as
 * discussões todos as postagens para ver se o dado usuário postou alguma coisa, caso positivo ele pega a data de
 * envio da primeira postagem
 *
 * A tarceira parte da query se o aluno tiver uma postagem ele verifica no forum (grade_item) as notas do respectivo
 * aluno em grade_grades.
 *
 */
function query_postagens_forum(){
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

    return " SELECT *,  userid_posts IS NOT NULL as has_post
                     FROM (

                        {$alunos_grupo_tutoria}

                     ) u
                     LEFT JOIN
                    (
                        SELECT fp.userid as userid_posts, fp.created as submission_date, fd.name as forum_name
                        FROM {course_modules} cm
                        JOIN {forum} f
                        ON (f.id=cm.instance AND cm.id=:forumid)
                        JOIN {forum_discussions} fd
                        ON (fd.forum=f.id)
                        JOIN {forum_posts} fp
                        ON (fd.id = fp.discussion)
                        GROUP BY fp.userid
                        ORDER BY fp.created ASC
                    ) forum_posts
                    ON (forum_posts.userid_posts=u.id)
                    LEFT JOIN
                    (
                        SELECT gg.userid, gg.rawgrade as grade, gg.timemodified, gg.itemid
                        FROM {grade_grades} gg
                        JOIN {grade_items} gi
                        ON ( gi.courseid=:courseid AND gg.itemid=:idforumitem AND gi.id = gg.itemid AND gi.itemmodule LIKE 'forum' AND rawgrade IS NOT NULL)
                    ) gg
                    ON (gg.userid = u.id)
                    ORDER BY grupo_id, u.firstname, u.lastname
    ";
}


/* ---------------------------------------
 * QUERIES ESPECÍFICAS DE UM RELATÓRIO
 * ---------------------------------------
 */


/*
 * Query para o relatório atividades vs notas
 * Colunas: user_id
 *          submission_date -> unixtime de envio da atividade,
 *          timemodified -> unixtime da alteração da atividade, algumas atividades não possuem submission_date
 *          grade -> nota
 *          grupo_id -> grupo de tutoria a qual o usuário pertence
 *          nosubmissions -> atividade offline
 *
 * @return string
 *
 */
function query_atividades_vs_notas()
{
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

    return "SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade, grupo_id,
                      ass.nosubmissions
                 FROM (

                    {$alunos_grupo_tutoria}

                 ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid AND sub.status LIKE 'submitted')
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=:assignmentid2 AND gr.userid=u.id)
            LEFT JOIN {assign} ass
            ON (ass.id=:assignmentid3)
            ORDER BY grupo_id, u.firstname, u.lastname
    ";
}


/*
 * Query para o relatório entrega de atividades
 * Colunas: user_id
 *          submission_date -> unixtime de envio da atividade,
 *          timemodified -> unixtime da alteração da atividade, algumas atividades não possuem submission_date
 *          grade -> nota
 *          status -> estado da avaliaçao
 *
 * @return string
 *
 */
function query_entrega_de_atividades()
{
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();
    return " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade,
                      sub.status
                 FROM (

                      {$alunos_grupo_tutoria}

                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            ORDER BY u.firstname, u.lastname
    ";
}

/*
 * Query para o relatório histrorico atribuicao de notas
 * Colunas: user_id
 *          submission_date -> unixtime de envio da atividade,
 *          submission_modified -> unixtime da data de alteracao da atividade
 *          grade_modified -> unixtime da alteração da atividade, algumas atividades não possuem submission_date
 *          grade_created -> unixtime da data que a nota foi atribuuda
 *          grade -> nota
 *          status -> estado da avaliaçao
 *
 * @return string
 *
 */
function query_historico_atribuicao_notas(){
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();
    return " SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      sub.timemodified as submission_modified,
                      gr.timemodified as grade_modified,
                      gr.timecreated as grade_created,
                      gr.grade,
                      sub.status
                 FROM (

                      {$alunos_grupo_tutoria}

                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            ORDER BY u.firstname, u.lastname
    ";
}

/*
 * Query para o relatório estudante sem atividade postada
 * Colunas: user_id
 *          grade -> nota
 *          status -> estado da avaliaçao
 *
 * @return string
 */
function query_estudante_sem_atividade_postada(){
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();
    return " SELECT u.id as user_id,
                      gr.grade,
                      sub.status
                 FROM (

                      {$alunos_grupo_tutoria}

                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id)
            WHERE sub.status IS NULL
            ORDER BY u.firstname, u.lastname
    ";
}

/*
 * Query para o relatório estudante sem atividade avaliada
 * Colunas: user_id
 *          grade -> nota
 *          status -> estado da avaliaçao
 *
 * @return string
 */
function query_estudante_sem_atividade_avaliada(){
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();
    return " SELECT u.id as user_id,
                      gr.grade,
                      sub.status
                 FROM (

                      {$alunos_grupo_tutoria}

                      ) u
            JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            WHERE gr.grade IS NULL OR gr.grade = -1
            ORDER BY u.firstname, u.lastname
    ";
}

/*
 * Query para o relatorio atividade nao avaliadas
 * Colunas: user_id
 *          grade -> nota
 *          status -> estado da avaliaçao
 *          submission_modfied -> data de envio da avaliacao
 *
 * @return string
 */
function query_atividades_nao_avaliadas(){
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();
    return " SELECT u.id as user_id,
                      gr.grade,
                      sub.timemodified as submission_modified
                 FROM (

                      {$alunos_grupo_tutoria}

                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id)
            WHERE gr.grade IS NULL
            ORDER BY u.firstname, u.lastname
    ";
}

/*
 * Query para o relatorio atividade nota atribuida
 * Colunas: user_id
 *          grade -> nota
 *          status -> estado da avaliaçao
 *
 * @return string
 */
function query_atividades_nota_atribuida(){
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();
    return " SELECT u.id as user_id,
                      gr.grade,
                      sub.status
                 FROM (

                      {$alunos_grupo_tutoria}

                      ) u
            JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            WHERE gr.grade IS NOT NULL OR gr.grade != -1
            ORDER BY u.firstname, u.lastname
    ";
}

/*
 * Query para o relatorio de acesso tutor
 */
function query_acesso_tutor(){
    return " SELECT year(from_unixtime(sud.`timeend`)) AS calendar_year,
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
}

/*
 * Query para o relatorio de potenciais evasões
 */
function query_potenciais_evasoes(){
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();
    return "SELECT u.id as user_id,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade
                 FROM (

                      {$alunos_grupo_tutoria}

                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id)
            ORDER BY u.firstname, u.lastname
    ";
}