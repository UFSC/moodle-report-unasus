<?php

class tutoria {

    /**
     * Listagem de estudantes que participam de um curso UFSC e estão relacionados a um tutor
     *
     * @param $curso_ufsc
     * @return array [id][fullname]
     */
    static function get_estudantes_curso_ufsc($curso_ufsc) {
        global $DB;

        $relationship = self::get_relationship_tutoria($curso_ufsc);
        $cohort_estudantes = self::get_relationship_cohort_estudantes($relationship->id);

        $sql = "SELECT DISTINCT u.id, CONCAT(firstname,' ',lastname) AS fullname
                  FROM {user} u
                  JOIN {relationship_members} rm
                    ON (rm.userid=u.id AND rm.relationshipcohortid=:cohort_id)
                  JOIN {relationship_groups} rg
                    ON (rg.relationshipid=:relationship_id AND rg.id=rm.relationshipgroupid)
              ORDER BY u.firstname";

        $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_estudantes->id);
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Retorna lista de grupos de tutoria de um determinado curso ufsc
     *
     * @param string $curso_ufsc
     * @param array $tutores
     * @return array
     */
    static function get_grupos_tutoria($curso_ufsc, $tutores = null) {
        global $DB;
        $relationship = self::get_relationship_tutoria($curso_ufsc);

        if (is_null($tutores)) {
            $sql = "SELECT rg.*
                      FROM {relationship_groups} rg
                     WHERE rg.relationshipid = :relationshipid
                  GROUP BY rg.id
                  ORDER BY name";
            $params = array('relationshipid'=>$relationship->id);
        } else {
            $tutores_sql = int_array_to_sql($tutores);
            $cohort_tutores = self::get_relationship_cohort_tutores($relationship->id);

            $sql = "SELECT rg.*
                      FROM {relationship_groups} rg
                      JOIN {relationship_members} rm
                        ON (rg.id=rm.relationshipgroupid AND rm.relationshipcohortid=:cohort_id)
                     WHERE rg.relationshipid = :relationshipid
                       AND rm.userid IN ({$tutores_sql})
                  GROUP BY rg.id
                  ORDER BY name";
            $params = array('relationshipid'=>$relationship->id, 'cohort_id' => $cohort_tutores->id);
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Retorna a string que é utilizada no agrupamento por grupos de tutoria
     *
     * O padrão é $nome_do_grupo - Tutor(es) responsaveis
     * @param $curso_ufsc
     * @param $id
     * @return string
     * @throws dml_read_exception
     */
    static function grupo_tutoria_to_string($curso_ufsc, $id) {
        global $DB;

        $relationship = self::get_relationship_tutoria($curso_ufsc);
        $cohort_tutores = self::get_relationship_cohort_tutores($relationship->id);

        $params = array('relationshipid' => $relationship->id, 'cohort_id' => $cohort_tutores->id, 'grupo_id' => $id);

        $sql = "SELECT rg.*
                  FROM {relationship_groups} rg
             LEFT JOIN {relationship_members} rm
                    ON (rg.id=rm.relationshipgroupid AND rm.relationshipcohortid=:cohort_id)
                 WHERE rg.relationshipid = :relationshipid
                   AND rg.id=:grupo_id
              GROUP BY rg.id
              ORDER BY name";

        $grupos_tutoria = $DB->get_records_sql($sql, $params);

        $sql = "SELECT u.id as user_id, CONCAT(u.firstname,' ',u.lastname) as fullname
                  FROM {relationship_groups} rg
             LEFT JOIN {relationship_members} rm
                    ON (rg.id=rm.relationshipgroupid AND rm.relationshipcohortid=:cohort_id)
             LEFT JOIN {user} u
                    ON (u.id=rm.userid)
                 WHERE rg.relationshipid = :relationshipid
                   AND rg.id=:grupo_id
              GROUP BY rg.id
              ORDER BY name";

        $tutores = $DB->get_records_sql($sql, $params);

        $string = '<strong>' . $grupos_tutoria[$id]->name . '</strong>';
        if (empty($tutores)) {
            return $string . " - Sem Tutor Responsável";
        } else {
            foreach ($tutores as $tutor) {
                $string.= ' - ' . $tutor->fullname . ' ';
            }
        }
        return $string;
    }

    /**
     * Retorna o relationship_cohort dos estudantes de um determinado relationship
     * @param $relationship_id
     * @return mixed
     */
    static function get_relationship_cohort_estudantes($relationship_id) {
        global $DB;

        $sql = "SELECT rc.*
              FROM {relationship_cohorts} rc
              JOIN {role} r
                ON (r.id=rc.roleid)
             WHERE relationshipid=:relationship_id
               AND r.shortname IN ('student')";

        $cohort = $DB->get_record_sql($sql, array('relationship_id' => $relationship_id));

        if (!$cohort) {
            print_error('relationship_cohort_estudantes_not_available_error', 'report_unasus', '', null, "Relationship: {$relationship_id}");
        }

        return $cohort;
    }

    /**
     * Retorna o relationship_cohort dos tutores de um determinado relationship
     * @param $relationship_id
     * @return mixed
     */
    static function get_relationship_cohort_tutores($relationship_id) {
        global $DB;

        $sql = "SELECT rc.*
              FROM {relationship_cohorts} rc
              JOIN {role} r
                ON (r.id=rc.roleid)
             WHERE relationshipid=:relationship_id
               AND r.shortname IN ('tutor')";

        $cohort = $DB->get_record_sql($sql, array('relationship_id' => $relationship_id));

        if (!$cohort) {
            print_error('relationship_cohort_tutores_not_available_error', 'report_unasus', '', null, "Relationship: {$relationship_id}");
        }

        return $cohort;
    }

    /**
     * Retorna o relationship que designa os grupos de tutoria de um determinado curso UFSC
     * @param $curso_ufsc
     * @return mixed
     */
    static function get_relationship_tutoria($curso_ufsc) {
        global $DB;

        $ufsc_category = get_category_from_curso_ufsc($curso_ufsc);

        $sql = "SELECT r.id, r.name as nome
              FROM {relationship} r
              JOIN (
                    SELECT ti.itemid as relationship_id
                      FROM {tag_instance} ti
                      JOIN {tag} t
                        ON (t.id=ti.tagid)
                     WHERE t.name='grupo_tutoria'
                   ) tr
                ON (r.id=tr.relationship_id)
              JOIN {context} ctx
                ON (ctx.id=r.contextid)
              JOIN {course_categories} cc
                ON (ctx.instanceid = cc.id AND (cc.path LIKE '/$ufsc_category/%' OR cc.path LIKE '/$ufsc_category'))";

        $relationship = $DB->get_record_sql($sql);

        if (!$relationship) {
            print_error('relationship_tutoria_not_available_error', 'report_unasus');
        }

        return $relationship;
    }

    /**
     * Dado que alimenta a lista do filtro tutores
     *
     * @param $curso_ufsc
     * @return array
     */
    static function get_tutores_curso_ufsc($curso_ufsc) {
        global $DB;

        $relationship = self::get_relationship_tutoria($curso_ufsc);
        $cohort_tutores = self::get_relationship_cohort_tutores($relationship->id);

        $sql = "SELECT DISTINCT u.id, CONCAT(firstname,' ',lastname) AS fullname
              FROM {user} u
              JOIN {relationship_members} rm
                ON (rm.userid=u.id AND rm.relationshipcohortid=:cohort_id)
              JOIN {relationship_groups} rg
                ON (rg.relationshipid=:relationship_id AND rg.id=rm.relationshipgroupid)
              ORDER BY u.firstname";

        $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_tutores->id);
        return $DB->get_records_sql_menu($sql, $params);
    }
}

/* ---------------------------------------
 * QUERIES UTILIZADAS EM VÁRIOS RELATÓRIOS
 * ---------------------------------------
 */

/**
 * Query para retornar os alunos pertencentes a um grupo de tutoria
 * Utilizada em diversos relatórios, necessita do relationship para rodar.
 *
 * Colunas:
 *
 * - user_id
 * - firstname
 * - lastname
 * - grupo_id
 *
 * @throws Exception
 * @return string
 */
function query_alunos_grupo_tutoria() {

    /** @var $report Factory */
    $report = Factory::singleton();
    $query_polo = ' ';

    $cohorts = int_array_to_sql($report->cohorts_selecionados);
    $polos = int_array_to_sql($report->polos_selecionados);

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

    return "SELECT u.id, u.firstname, u.lastname, rg.id AS grupo_id, vga.polo, co.id as cohort
              FROM {user} u
              JOIN {relationship_members} rm
                ON (rm.userid=u.id AND rm.relationshipcohortid=:cohort_relationship_id)
              JOIN {relationship_groups} rg
                ON (rg.relationshipid=:relationship_id AND rg.id=rm.relationshipgroupid)
              JOIN {view_Alunos} vga
                ON (vga.matricula = u.username {$query_polo})
                   {$query_cohort}
             WHERE rg.id=:grupo_tutoria
             GROUP BY u.id";
}

function query_alunos_grupo_orientacao() {

    return "SELECT DISTINCT u.id, u.firstname, 'cohort', 'polo'
                      FROM {view_Alunos_Orientadores} ao
                      JOIN {user} u
                        ON (ao.username_aluno=u.username)
                     WHERE ao.username_orientador = :orientador_id
            ";
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
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

    return " SELECT u.id AS userid,
                    u.polo,
                    u.cohort,
                    fp.submission_date,
                    fp.forum_name,
                    gg.grade,
                    gg.timemodified,
                    fp.itemid,
                    userid_posts IS NOT NULL AS has_post
                     FROM (

                        {$alunos_grupo_tutoria}

                     ) u
                     LEFT JOIN
                     (
                        SELECT fp.userid AS userid_posts, fp.created AS submission_date, fd.name AS forum_name, f.id as itemid
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
                        SELECT gg.userid, gg.rawgrade AS grade, gg.timemodified, gg.itemid, f.id as forumid
                        FROM {forum} f
                        JOIN {grade_items} gi
                          ON (gi.courseid=:courseid AND gi.itemtype = 'mod' AND
                              gi.itemmodule = 'forum'  AND gi.iteminstance=f.id)
                        JOIN {grade_grades} gg
                          ON (gg.itemid=gi.id)
                    GROUP BY gg.userid, gg.itemid
                    ) gg
                    ON (gg.userid = u.id AND fp.itemid=gg.forumid)
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
        $filtro_tutor = "WHERE u.id IN ({$tutores}) ";
    }

    return "SELECT year(from_unixtime(sud.`timeend`)) AS calendar_year,
                   month(from_unixtime(sud.`timeend`)) AS calendar_month,
                   day(from_unixtime(sud.`timeend`)) AS calendar_day,
                   u.id as userid
              FROM {user} u
        INNER JOIN {relationship_members} rm
                ON (rm.userid=u.id AND rm.relationshipcohortid=:cohort_id)
              JOIN {relationship_groups} rg
                ON (rg.id=rm.relationshipgroupid AND rg.relationshipid = :relationshipid)
         LEFT JOIN {stats_user_daily} sud
                ON (u.id=sud.userid)
                   {$filtro_tutor}
          GROUP BY calendar_year, calendar_month, calendar_day, sud.userid
          ORDER BY calendar_year, calendar_month, calendar_day";
}

function query_lti() {

    return "SELECT l.id,l.course, l.name, l.timecreated,
                   l.timemodified, l.grade, l.typeid,
                   t.name AS typename, t.baseurl,
                   cm.id as cmid, cm.completionexpected, cm.groupingid as grouping_id,
                   co.fullname as coursename
              FROM {lti} l
              JOIN {lti_types} t
                ON (l.typeid=t.id )
              JOIN {course} co
                ON (l.course=co.id)
              JOIN {course_modules} cm
                ON (l.course=cm.course AND cm.instance=l.id)
              JOIN {modules} m
                ON (m.id = cm.module AND m.name LIKE 'lti')
             WHERE l.course =:course AND cm.visible=TRUE";
}

function query_lti_config() {

    return "SELECT 
                   c.name AS name, c.value as value
              FROM {lti_types_config} c
             WHERE c.typeid =:typeid";
}

/**
 * Query Agrupamento
 * @return string
 */
function query_group_members() {

    return "SELECT gm.id, gm.userid, gm.groupid, gg.groupingid
              FROM {groups_members} gm
        INNER JOIN {groupings_groups} gg
                ON (gm.groupid=gg.groupid)
        INNER JOIN {groupings} g
                ON (g.id=gg.groupingid)
             WHERE g.courseid =:courseid
          GROUP BY gm.id";
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
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

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


function query_alunos_disciplina(){

    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

    return "SELECT DISTINCT u.id, u.firstname, u.lastname, u.cohort, u.polo,
                                u.grupo_id as grupo_id, (e.id = ue.enrolid IS NOT NULL) AS enrol
                           FROM ({$alunos_grupo_tutoria}) u
                           JOIN {user_enrolments} ue
                             ON ue.userid = u.id
                     INNER JOIN {enrol} e
                             ON e.id = ue.enrolid AND e.courseid =:enrol_courseid
            ";
}

function query_atividades_nao_postadas() {
    $alunos_disciplina = query_alunos_disciplina();

    return "SELECT u.id AS userid,
                   u.polo,
                   u.cohort,
                   u.polo,
                   u.enrol,
                   gr.grade,
                   sub.timecreated AS submission_date,
                   sub.timemodified AS submission_modified,
                   gr.timemodified AS grade_modified,
                   gr.timecreated AS grade_created,
                   sub.status
              FROM (

                      {$alunos_disciplina}

                   ) u
         LEFT JOIN {assign_submission} sub
                ON (u.id=sub.userid AND sub.assignment=:assignmentid)
         LEFT JOIN {assign_grades} gr
                ON (gr.assignment=:assignmentid2 AND gr.userid=u.id)
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
 * - submission_modified -> unixtime da data de alteracao da atividade
 * - grade_modified -> unixtime da alteração da atividade, algumas atividades não possuem submission_date
 * - grade_created -> unixtime da data que a nota foi atribuuda
 * - status -> estado da avaliaçao
 *
 * @return string
 *
 */
function query_atividades() {
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

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
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

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
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

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

function query_quiz_nao_postadas() {
    $alunos_disciplina = query_alunos_disciplina();

    return "SELECT u.id AS userid,
                   u.polo,
                   u.cohort,
                   u.enrol,
                   qg.grade,
                   qg.timemodified AS grade_date,
                   qa.timefinish AS submission_date
              FROM (

                    {$alunos_disciplina}

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

function query_alunos_modulos() {
    $alunos_grupo_tutoria = query_alunos_grupo_tutoria();

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

class LtiPortfolioQuery {

    /** @var array $estudantes_grupo_tutoria */
    private $estudantes_grupo_tutoria;
    private $estudantes_grupo_orientacao;

    /** @var array $report_estudantes_grupo_tutoria */
    private $report_estudantes_grupo_tutoria;
    private $report_estudantes_grupo_orientacao;

    function __construct() {
        $this->estudantes_grupo_tutoria = array();
        $this->estudantes_grupo_orientacao = array();
    }

    /**
     * @param $grupo_tutoria
     * @return array
     */
    private function &query_estudantes_by_grupo_tutoria($grupo_tutoria) {

        // Se a consulta já foi executada, não é necessário refazê-la
        if (isset($this->estudantes_grupo_tutoria[$grupo_tutoria])) {
            return $this->estudantes_grupo_tutoria[$grupo_tutoria];
        }

        // Middleware para as queries sql
        $middleware = Middleware::singleton();

        /** @var $factory Factory */
        $factory = Factory::singleton();

        /* Query alunos */
        $query_alunos = query_alunos_grupo_tutoria();
        $params = array(
            'curso_ufsc' => $factory->get_curso_ufsc(),
            'grupo_tutoria' => $grupo_tutoria,
            'tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE);

        $this->estudantes_grupo_tutoria[$grupo_tutoria] = $middleware->get_records_sql($query_alunos, $params);

        return $this->estudantes_grupo_tutoria[$grupo_tutoria];
    }

    /**
     * @param $grupo
     * @throws Exception
     * @throws dml_read_exception
     * @internal param $grupo_orientacao
     * @return array
     */
    private function &query_estudantes_by_grupo_orientacao($grupo) {

        // Se a consulta já foi executada, não é necessário refazê-la
        if (isset($this->estudantes_grupo_orientacao[$grupo])) {
            return $this->estudantes_grupo_orientacao[$grupo];
        }

        // Middleware para as queries sql
        $middleware = Middleware::singleton();

        /** @var $factory Factory */
        $factory = Factory::singleton();

        /* Query alunos */
        $query_alunos = query_alunos_grupo_orientacao();

        $params = array(
            'curso_ufsc' => $factory->get_curso_ufsc(),
            'orientador_id' => $grupo
        );

        $this->estudantes_grupo_orientacao[$grupo] = $middleware->get_records_sql($query_alunos, $params);

        return $this->estudantes_grupo_orientacao[$grupo];

    }


    /**
     * Realiza a consulta ao webservice do sistema de TCCs para obter os dados dos alunos que participam de um grupo de tutoria
     * @param int $grupo_tutoria código do grupo de tutoria
     * @param report_unasus_lti_activity $atividade
     * @return array
     */
    private function &query_report_data_by_grupo_tutoria($grupo_tutoria, &$atividade) {

        // Se a consulta já foi executada, não é necessário refazê-la
        if (isset($this->report_estudantes_grupo_tutoria[$grupo_tutoria])) {
            return $this->report_estudantes_grupo_tutoria[$grupo_tutoria];
        }

        $user_ids = array();
        $estudantes =& $this->query_estudantes_by_grupo_tutoria($grupo_tutoria);

        foreach ($estudantes as $aluno) {
            $user_ids[$aluno->id] = $aluno->id;
        }

        // WS Client
        $client = new SistemaTccClient($atividade->baseurl, $atividade->consumer_key);
        $this->report_estudantes_grupo_tutoria[$grupo_tutoria] = $client->get_report_data($user_ids);

        return $this->report_estudantes_grupo_tutoria[$grupo_tutoria];
    }

    /**
     * Realiza a consulta ao webservice do sistema de TCCs para obter os dados dos alunos que participam de um grupo de orientação
     * @param $grupo
     * @param report_unasus_lti_activity $atividade
     * @internal param int $grupo_tutoria
     * @return array
     */
    private function &query_report_data_by_grupo_orientacao($grupo, &$atividade) {

        // Se a consulta já foi executada, não é necessário refazê-la
        if (isset($this->report_estudantes_grupo_orientacao[$grupo])) {
            return $this->report_estudantes_grupo_orientacao[$grupo];
        }

        $user_ids = array();
        $estudantes =& $this->query_estudantes_by_grupo_orientacao($grupo);

        foreach ($estudantes as $aluno) {
            $user_ids[$aluno->id] = $aluno->id;
        }

        // WS Client
        $client = new SistemaTccClient($atividade->baseurl, $atividade->consumer_key);
        $this->report_estudantes_grupo_orientacao[$grupo] = $client->get_report_data_tcc($user_ids);

        return $this->report_estudantes_grupo_orientacao[$grupo];
    }

    /**
     * Realiza a contagem de alunos para cada grupo de tutoria/orientacao, e o total de alunos para cada atividade
     * Função utilizada pelo relatório de Portfolio e TCC consolidados
     *
     * @param $lista_atividades
     * @param $total_alunos
     * @param $atividade
     * @param $grupo
     * @param bool $is_orientação
     * @internal param $grupo_tutoria
     */
    function count_lti_report(&$lista_atividades, &$total_alunos, &$atividade, $grupo, $is_orientação = false) {

        if($is_orientação){
            $result =& $this->query_report_data_by_grupo_orientacao($grupo, $atividade);
        }else
            $result =& $this->query_report_data_by_grupo_tutoria($grupo, $atividade);

        $count_alunos = array();

        //Preencher total de alunos por grupo de tutoria
        $total_alunos[$grupo] = count($result);

        if (empty($result)) {
            return; // grupo sem membros cadastrados
        }
        $is_tcc = false;

        foreach ($result as $r) {
            //Verifica se é hub portfólio
            if(!isset($r->tcc->hubs)){
                $res = $r->tcc->hubs_tcc;
                $is_tcc = true;
            }else
                $res = $r->tcc->hubs;

            // Processando hubs encontrados
            foreach ($res as $hub) {
                $hub = ($is_tcc) ? $hub->hubs_tcc : $hub->hub;

                // Inicializar
                $position = $hub->position;
                if (!array_key_exists($position, $count_alunos)) {
                    $count_alunos[$position] = 0;
                }
                $count_alunos[$position]++;
            }
        }

        // array_atividade[lti_id][hubposition]
        foreach ($count_alunos as $position => $count_hub) {
            $lista_atividades[$atividade->id][$position] = new dado_atividades_alunos($count_hub);
        }
    }


    /**
     * Realiza as consultas necessarias para gerar os dados para os relatórios
     *
     * Esta função coordena as requisições realizadas via WebService e o processamento das mesmas
     * para retornar em um padrão semelhante aos dados que são retornados pelas consultas na base de dados
     *
     * @param report_unasus_lti_activity $atividade
     * @param int $grupo
     * @param bool $is_orientacao
     * @return array
     */
    public function get_report_data(&$atividade, $grupo, $is_orientacao = false) {

        if($is_orientacao){
            $estudantes =& $this->query_estudantes_by_grupo_orientacao($grupo);
            $result =& $this->query_report_data_by_grupo_orientacao($grupo, $atividade);
        }else {
            $estudantes =& $this->query_estudantes_by_grupo_tutoria($grupo);
            $result =& $this->query_report_data_by_grupo_tutoria($grupo, $atividade);
        }

        if (!$result) {
            // Falha ao conectar com Webservice
            // TODO: retornar dado vazio para todos os user_ids para mitigar problemas
            return array();
        }

        $output = array();

        foreach ($result as $r) {

            $userid = $r->tcc->user_id;
            $estudante = $estudantes[$userid];
            $found = false;
            $is_tcc = false;

            //Verifica se é hub portfólio
            if(!isset($r->tcc->hubs)){
                $res = $r->tcc->hubs_tcc;
                $is_tcc = true;
            }else
                $res = $r->tcc->hubs;

            // Processando hubs encontrados
            foreach ($res as $hub) {

                $hub = ($is_tcc) ? $hub->hubs_tcc : $hub->hub;

                // Só vamos processar o hub que corresponde a posição da atividade
                if ($hub->position != $atividade->position) {
                    continue;
                }

                $found = true;

                // criar dado
                $model = new stdClass();
                $model->userid = $userid;
                $model->grade = $hub->grade;

                $model->status = $hub->state;

                if (!empty($hub->grade_date)) {
                    $grade_date = new DateTime($hub->grade_date);
                    $model->grade_date = $grade_date->getTimestamp();
                } else {
                    $model->grade_date = false;
                }

                if (!empty($hub->state_date)) {
                    $submission_date = new DateTime($hub->state_date);
                    $model->submission_date = $submission_date->getTimestamp();
                } else {
                    $model->submission_date = false;
                }

                $model->cohort = $estudante->cohort;
                $model->polo = $estudante->polo;

                $model->status_abstract = (isset($r->tcc->abstract->state)) ? $r->tcc->abstract->state : null;
                $model->status_presentation = (isset($r->tcc->presentation->state)) ? $r->tcc->presentation->state : null;
                $model->status_final_considerations = (isset($r->tcc->final_considerations->state)) ? $r->tcc->final_considerations->state : null;

                $output[] = $model;
            }

            // Marcando usuário que não possuem dados correspondentes na pesquisa
            if (!$found) {

                // criar dado
                $model = new stdClass();
                $model->userid = $userid;
                $model->cohort = $estudante->cohort;
                $model->polo = $estudante->polo;
                $model->not_found = true;

                $output[] = $model;
            }
        }

        return $output;
    }
}