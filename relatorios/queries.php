<?php

/* ---------------------------------------
 * QUERIES UTILIZADAS EM VÁRIOS RELATÓRIOS
 * ---------------------------------------
 */

/**
 * Query para retornar os alunos pertencentes a um grupo de tutoria
 *
 *
 * Utilizada em diversos relatórios, necessita do middleware para rodar.
 *
 * Colunas:
 *
 * - user_id
 * - firstname
 * - lastname
 * - grupo_id
 *
 * @return string
 */
function query_alunos_grupo_tutoria() {

    /** @var $factory Factory */
    $factory = Factory::singleton();
    $query_polo = ' ';

    $cohorts = int_array_to_sql($factory->cohorts_selecionados);
    $polos = int_array_to_sql($factory->polos_selecionados);

    if (!is_null($cohorts)) {
        $query_cohort = " JOIN {cohort_members} cm
                            ON (cm.userid=u.id)
                          JOIN {cohort} co
                            ON (cm.cohortid=co.id AND co.id IN ({$cohorts})) ";
    } else {
        $query_cohort = " LEFT JOIN {cohort_members} cm
                            ON (u.id = cm.userid)
                          LEFT JOIN {cohort} co
                            ON (cm.cohortid=co.id)";
    }

    if (!is_null($polos)) {
        $query_polo = "  AND vga.polo IN ({$polos}) ";
    }

    return "SELECT DISTINCT u.id, u.firstname, u.lastname, gt.id AS grupo_id, vga.polo, co.id as cohort
                         FROM {user} u
                         JOIN {table_PessoasGruposTutoria} pg
                           ON (pg.matricula=u.username)
                         JOIN {table_GruposTutoria} gt
                           ON (gt.id=pg.grupo)
                         JOIN {Geral_Alunos_Ativos} vga
                           ON (vga.matricula = u.username {$query_polo})
                         {$query_cohort}
                        WHERE gt.curso=:curso_ufsc AND pg.grupo=:grupo_tutoria AND pg.tipo=:tipo_aluno";
}

/**
 * Query para retornar se um dado aluno possui postagens num forum de um dado módulo
 * se o tiver retorna a nota e outros dados da postagem.
 *
 * @polos array(int) polos para filtrar os alunos
 *
 * Colunas:
 *
 * - id -> id do usuario
 * - firstname -> nome do usuario
 * - lastname -> sobrenome do usuario
 * - grupo_id -> grupo de tutoria que o aluno pertence
 * - userid_posts -> booleano se o aluno possui ou nao postagens naquele forum
 * - submission_date -> data de envio da primeira postagem do aluno naquele forum
 * - rawgrade-> nota final do aluno naquele forum, nota que já é a média estipulada nas configuraçoes do forum
 * - timemodified -> data de alteração da postagem
 * - itemid -> id do forum
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
 * @return string
 */
function query_postagens_forum() {
    /** @var $factory Factory */
    $factory = Factory::singleton();
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria($factory->polos_selecionados);

    return " SELECT u.id AS userid,
                    u.polo,
                    u.cohort,
                    fp.submission_date,
                    fp.forum_name,
                    gg.grade,
                    gg.timemodified,
                    gg.itemid,
                    userid_posts IS NOT NULL AS has_post
                     FROM (

                        {$alunos_grupo_tutoria}

                     ) u
                     LEFT JOIN
                     (
                        SELECT fp.userid AS userid_posts, fp.created AS submission_date, fd.name AS forum_name
                          FROM {forum} f
                          JOIN {forum_discussions} fd
                            ON (fd.forum=f.id)
                          JOIN {forum_posts} fp
                            ON (fd.id = fp.discussion)
                         WHERE f.id=:forumid
                      GROUP BY fp.userid
                      ORDER BY fp.created ASC
                     ) fp
                    ON (fp.userid_posts=u.id)
                    LEFT JOIN
                    (
                        SELECT gg.userid, gg.rawgrade AS grade, gg.timemodified, gg.itemid
                        FROM {forum} f
                        JOIN {grade_items} gi
                          ON (gi.courseid=:courseid AND gi.itemtype = 'mod' AND
                              gi.itemmodule = 'forum'  AND gi.iteminstance=f.id)
                        JOIN {grade_grades} gg
                          ON (gg.itemid=gi.id)
                    GROUP BY gg.userid, gg.itemid
                    ) gg
                    ON (gg.userid = u.id)
                    ORDER BY grupo_id, u.firstname, u.lastname

    ";
}

/* ---------------------------------------
 * QUERIES ESPECÍFICAS DE UM RELATÓRIO
 * ---------------------------------------
 */

/**
 * Query para o relatorio de acesso tutor
 *
 * @return string
 */
function query_acesso_tutor() {
    /** @var $factory Factory */
    $factory = Factory::singleton();

    $filtro_tutor = '';
    if (!is_null($factory->tutores_selecionados)) {
        $tutores = int_array_to_sql($factory->tutores_selecionados);
        $filtro_tutor = "AND u.id IN ({$tutores}) ";
    }

    return " SELECT year(from_unixtime(sud.`timeend`)) AS calendar_year,
                      month(from_unixtime(sud.`timeend`)) AS calendar_month,
                      day(from_unixtime(sud.`timeend`)) AS calendar_day,
                      sud.userid
                 FROM {stats_user_daily} sud
           INNER JOIN {user} u
                   ON (u.id=sud.userid {$filtro_tutor} )
           INNER JOIN {table_PessoasGruposTutoria} pgt
                   ON (pgt.matricula=u.username AND pgt.tipo=:tipo_tutor)
                 JOIN {table_GruposTutoria} gt
                   ON (gt.id=pgt.grupo AND gt.curso=:curso_ufsc)
             GROUP BY calendar_year, calendar_month, calendar_day, sud.userid
             ORDER BY calendar_year, calendar_month, calendar_day";
}

function query_lti() {

    return "SELECT l.id,l.course, l.name, l.timecreated,
                   l.timemodified, l.grade, l.typeid,
                   t.name AS typename, t.baseurl,
                   cm.id as cmid, cm.completionexpected
              FROM {lti} l
              JOIN {lti_types} t
                ON (l.typeid=t.id )
              JOIN {course_modules} cm
                ON (l.course=cm.course AND cm.instance=l.id)
             WHERE l.course =:course";
}

function query_lti_config() {
    
    return "SELECT 
                   c.name AS name, c.value as value
              FROM {lti_types_config} c
             WHERE c.typeid =:typeid";
}

/**
 * Query para o relatorio de uso do sistema horas
 *
 * @return string
 */
function query_uso_sistema_tutor() {

    return "SELECT userid, dia , count(*) /2  AS horas
              FROM (
                   
                    SELECT date_format( (FROM_UNIXTIME(time))  , '%d/%m/%Y') AS dia,
                           date_format( (FROM_UNIXTIME(time))  , '%H') AS hora,
                           ROUND (date_format( (FROM_UNIXTIME(time))  , '%i') / 30) *30 AS min,
                           userid
                      FROM {log}

                     WHERE time > :tempominimo
                           AND time < UNIX_TIMESTAMP(DATE_SUB(:tempomaximo,INTERVAL 30 MINUTE)) AND userid=:userid
                           AND action != 'login' AND action != 'logout'
                  GROUP BY dia, hora, min

                   )AS report
          GROUP BY report.dia";
}

/**
 * Query para o relatorio de potenciais evasões
 *
 * @polos array(int) polos para filtrar os alunos
 *
 * @return string
 */
function query_potenciais_evasoes() {
    /** @var $factory Factory */
    $factory = Factory::singleton();
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria($factory->polos_selecionados);

    return "SELECT u.id AS user_id,
                   u.polo,
                   u.cohort,
                   sub.timecreated AS submission_date,
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

/**
 * Query para os relatórios
 *
 * @polos array(int) polos para filtrar os alunos
 *
 * Colunas:
 *
 * - user_id
 * - grade -> nota
 * - submission_date -> unixtime de envio da atividade,
 * - submission_modified -> unixtime da data de alteracao da atividade
 * - grade_modified -> unixtime da alteração da atividade, algumas atividades não possuem submission_date
 * - grade_created -> unixtime da data que a nota foi atribuuda
 * - status -> estado da avaliaçao
 *
 * @return string
 *
 */
function query_atividades() {

    /** @var $factory Factory */
    $factory = Factory::singleton();
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria($factory->polos_selecionados);

    return "SELECT u.id AS userid,
                   u.polo,
                   u.cohort,
                   u.polo,
                   gr.grade,
                   sub.timecreated AS submission_date,
                   sub.timemodified AS submission_modified,
                   gr.timemodified AS grade_modified,
                   gr.timecreated AS grade_created,
                   sub.status
              FROM (

                      {$alunos_grupo_tutoria}

                   ) u
         LEFT JOIN {assign_submission} sub
                ON (u.id=sub.userid AND sub.assignment=:assignmentid)
         LEFT JOIN {assign_grades} gr
                ON (gr.assignment=:assignmentid2 AND gr.userid=u.id)
          ORDER BY grupo_id, u.firstname, u.lastname
    ";
}

/**
 * Query para a nota final dos alunos em um dado modulo
 *
 * @polos array(int) polos para filtrar os alunos
 *
 * Colunas:
 *
 * - user_id
 * - grade -> nota
 * @return string
 *
 */
function query_nota_final() {
    /** @var $factory Factory */
    $factory = Factory::singleton();
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria($factory->polos_selecionados);

    return "SELECT u.id AS userid,
                   u.polo,
                   u.cohort,
                   gradeitemid AS gradeitemid,
                   courseid,
                   finalgrade AS grade
              FROM (

                    {$alunos_grupo_tutoria}

                   ) u

         LEFT JOIN

                  (

                      SELECT gi.id AS gradeitemid,
                              gi.courseid,
                              gg.userid AS userid,
                              gg.id AS gradegradeid,
                              gg.finalgrade
                        FROM {grade_items} gi
                        JOIN {grade_grades} gg
                          ON (gi.id = gg.itemid AND gi.itemtype LIKE 'course' AND itemmodule IS NULL)
                       WHERE (gi.courseid=:courseid)

                  ) grade
                ON (grade.userid=u.id)
          ORDER BY grupo_id, u.firstname, u.lastname
    ";
}

/**
 * Query para os relatórios
 *
 * @polos array(int) polos para filtrar os alunos
 *
 * Colunas:
 *
 * - user_id
 * - grade -> nota
 * - submission_date -> unixtime de envio da atividade,
 *
 * @return string
 *
 */
function query_quiz() {
    /** @var $factory Factory */
    $factory = Factory::singleton();
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria($factory->polos_selecionados);

    return "SELECT u.id AS userid,
                   u.polo,
                   u.cohort,
                   qg.grade,
                   qg.timemodified AS grade_date,
                   qa.timefinish AS submission_date
              FROM (

                    {$alunos_grupo_tutoria}

                   ) u
         LEFT JOIN (
                        SELECT qa.*
                          FROM (
                                SELECT *
                                  FROM {quiz_attempts}
                                 WHERE (quiz=:assignmentid AND timefinish != 0)
                              ORDER BY attempt DESC
                                ) qa
                      GROUP BY qa.userid, qa.quiz
                    ) qa
                ON (qa.userid = u.id)
         LEFT JOIN {quiz_grades} qg
                ON (u.id = qg.userid AND qg.quiz=qa.quiz)
         LEFT JOIN {quiz} q
                ON (q.course=:courseid AND q.id =:assignmentid2 AND qa.quiz = q.id AND qg.quiz = q.id)
          ORDER BY grupo_id, u.firstname, u.lastname
     ";
}

function query_alunos_modulos($modulo) {

    /** @var $factory Factory */
    $factory = Factory::singleton();
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria($factory->polos_selecionados);

    return "SELECT u.id AS userid,
                   u.polo,
                   gr.grade,
                   sub.timecreated AS submission_date,
                   sub.timemodified AS submission_modified,
                   gr.timemodified AS grade_modified,
                   gr.timecreated AS grade_created,
                   sub.status
             FROM (

                    {$alunos_grupo_tutoria}

                  ) u
        LEFT JOIN {assign_submission} sub
               ON (u.id=sub.userid AND sub.assignment=:assignmentid)
        LEFT JOIN {assign_grades} gr
               ON (gr.assignment=:assignmentid2 AND gr.userid=u.id)
         ORDER BY grupo_id, u.firstname, u.lastname
    ";
}