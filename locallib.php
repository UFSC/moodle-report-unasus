<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/tutores/middlewarelib.php');
require_once($CFG->dirroot . '/local/tutores/lib.php');
require_once($CFG->dirroot . '/report/unasus/datastructures.php');
require_once($CFG->dirroot . '/report/unasus/activities_datastructures.php');
require_once($CFG->dirroot . '/report/unasus/relatorios/queries.php');
require_once($CFG->dirroot . '/report/unasus/relatorios/loops.php');
//require_once($CFG->dirroot . '/report/unasus/sistematcc.php');

function report_unasus_get_datetime_from_unixtime($unixtime) {
    return date_create(date("Y-m-d H:m:s", $unixtime));
}

function report_unasus_get_count_estudantes($categoria_turma) {
    global $DB;

    $relationship = local_tutores_grupos_tutoria::get_relationship_tutoria($categoria_turma);
    $cohort_estudantes = local_tutores_grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

    $query = "SELECT rg.id AS grupo_id, COUNT(DISTINCT rm.userid)
                FROM {relationship_groups} rg
           LEFT JOIN {relationship_members} rm
                  ON (rg.relationshipid=:relationship_id
                 AND rg.id=rm.relationshipgroupid
                 AND rm.relationshipcohortid=:cohort_id)
          INNER JOIN {user} u
                  ON (u.id=rm.userid)
            GROUP BY rg.id
            ORDER BY rg.id";
    $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_estudantes->id);

    $result = $DB->get_records_sql_menu($query, $params);

    foreach ($result as $key => $value) {
        $result[$key] = (int) $value;
    }

    return $result;
}

function report_unasus_get_count_estudantes_orientacao($categoria_turma) {
    global $DB;

    $relationship = local_tutores_grupo_orientacao::get_relationship_orientacao($categoria_turma);
    $cohort_estudantes = local_tutores_grupo_orientacao::get_relationship_cohort_estudantes($relationship->id);

    $query = "SELECT rg.id AS grupo_id, COUNT(DISTINCT rm.userid)
                FROM {relationship_groups} rg
           LEFT JOIN {relationship_members} rm
                  ON (rg.relationshipid=:relationship_id
                 AND rg.id=rm.relationshipgroupid
                 AND rm.relationshipcohortid=:cohort_id)
          INNER JOIN {user} u
                  ON (u.id=rm.userid)
            GROUP BY rg.id
            ORDER BY rg.id";
    $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_estudantes->id);

    $result = $DB->get_records_sql_menu($query, $params);

    foreach ($result as $key => $value) {
        $result[$key] = (int) $value;
    }

    return $result;
}

/**
 * Dado que alimenta a lista do filtro cohort
 *
 * @param int $categoria_curso
 * @return array (nome dos cohorts)
 */
function report_unasus_get_nomes_cohorts($categoria_curso) {
    global $DB;

    $modulos = $DB->get_records_sql_menu(
            "SELECT DISTINCT(cohort.id), cohort.name
           FROM {cohort} cohort
           JOIN {context} ctx
             ON (cohort.contextid = ctx.id AND ctx.contextlevel = 40)
           JOIN {course_categories} cc
             ON (ctx.instanceid = cc.id AND
                ((cc.path LIKE '%/{$categoria_curso}') or (cc.path LIKE '%/{$categoria_curso}/%')))");
    return $modulos;
}

/**
 * Verifica se a base de dados do Middleware está instalada/configurada
 *
 * @return boolean
 */
function report_unasus_verifica_middleware() {
    $midleware = Middleware::singleton();

    $exist = $midleware->exist();
    return $exist;
}

/**
 * Dado que alimenta a lista do filtro polos
 *s
 * @param $categoria_turma
 * @return array
 */
function report_unasus_get_polos($categoria_turma) {
    $polos = null;
    if (report_unasus_verifica_middleware()) {
        try {
            $academico = Middleware::singleton();

            $relationship = local_tutores_grupos_tutoria::get_relationship_tutoria($categoria_turma);
            $cohort_estudantes = local_tutores_grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

            $sql = "
          SELECT DISTINCT(ua.polo), ua.nomepolo
            FROM {View_Usuarios_Dados_Adicionais} ua
            JOIN {user} u
              ON (u.username=ua.username)
            JOIN {relationship_members} rm
              ON (rm.userid=u.id AND rm.relationshipcohortid=:cohort_id)
            JOIN {relationship_groups} rg
              ON (rg.relationshipid=:relationship_id AND rg.id=rm.relationshipgroupid)
           WHERE nomepolo != ''
        ORDER BY nomepolo";

            $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_estudantes->id);
            $polos = $academico->get_records_sql_menu($sql, $params);
        } catch (Exception $e) {
            $polos = null;
        }
    }
    return $polos;
}

function report_unasus_get_final_grades($id_aluno, $course_id){

    global $DB;

    $sql = "SELECT u.id,
                  ROUND(gg.finalgrade,2) grade
              FROM {course} AS c
              JOIN {context} AS ctx
	            ON c.id = ctx.instanceid
              JOIN {role_assignments} AS ra
	            ON ra.contextid = ctx.id
              JOIN {user} AS u
	            ON u.id = ra.userid
              JOIN {grade_grades} AS gg
	            ON gg.userid = u.id
              JOIN {grade_items} AS gi
	            ON gi.id = gg.itemid
              JOIN {course_categories} AS cc
                ON cc.id = c.category
             WHERE gi.courseid = c.id AND gi.itemtype = 'course'
               AND u.id = :id_aluno
               AND c.id = :courseid";

    return $DB->get_records_sql($sql, array('id_aluno' => $id_aluno, 'courseid' => $course_id));
}

function report_unasus_get_id_nome_modulos($ufsc_category, $method = 'get_records_sql_menu', $visible = TRUE) {
    global $DB, $SITE;

    $visibleSQL = $visible ? "AND c.visible=TRUE " : "";
    $sql = " SELECT DISTINCT(c.id),
                    REPLACE(fullname,
                    CONCAT(shortname, ' - '), '') AS fullname,
                    c.category AS categoryid,
                    cc.name AS category,
                    cc.depth
               FROM {course} c
               JOIN {course_categories} cc
                 ON (c.category = cc.id
                     AND (
                           ((cc.path LIKE '%/$ufsc_category') or
                            (cc.path LIKE '%/$ufsc_category/%'))
                          )
                    )
               JOIN {course_modules} cm
                 ON (c.id = cm.course)
              WHERE c.id != :siteid
                $visibleSQL
           ORDER BY cc.depth, cc.sortorder, c.sortorder";

    $params = array('siteid' => $SITE->id);
    $modulos = $DB->$method($sql, $params);

    return $modulos;
}

/**
 * Lista de modulos separados por categoria da turma
 * Estrutura =   $array = array(
 *      array('Odd' => array(1 => 'Item 1 do grupo 1', 2 => 'Item 2 do grupo 1')),
 *       array('Even' => array(3 => 'Item 1 do grupo 2', 4 => 'Item 2 do grupo 2')),
 *       5 => 'lista principal 1',
 *       6 => 'lista principal 2',
 *   );
 *
 * @param $categoria_curso
 * @return array
 */
function report_unasus_get_nome_modulos($categoria_curso) {
    $modulos = report_unasus_get_id_nome_modulos($categoria_curso, 'get_records_sql', false);

    // Interar para criar array dos modulos separados por grupos
    $listall = array();
    $list = array();

    foreach ($modulos as $key => $modulo) {
        if ($modulo->depth == 1) {
            $listall[$key] = $modulo->fullname;
        } else {
            $list[$modulo->category][$key] = $modulo->fullname;
        }
    }

    foreach ($list as $key => $l) {
        array_push($listall, array($key => $l));
    }

    return $listall;
}

function report_unasus_get_id_modulos() {
    global $DB, $SITE;

    $modulos = $DB->get_records_sql_menu(
        "SELECT DISTINCT(c.id)
           FROM {course} c
           JOIN {assign} a
             ON (c.id = a.course)
          WHERE c.id != :siteid
            AND c.visible=TRUE", array('siteid' => $SITE->id));
    return array_keys($modulos);
}

function report_unasus_get_id_nome_atividades() {
    global $DB;

    $modulos = $DB->get_records_sql_menu("SELECT a.id, a.name FROM {assign} a");
    return $modulos;
}

/**
 * Função que busca os membros da cada agrupamento
 *
 * @param array $courses array de ids dos cursos moodle
 * @return array(course_id => (userid1, userid2, ...))
 */
function report_unasus_get_agrupamentos_membros($courses) {
    global $DB;

    $groups = array();

    foreach ($courses as $course_id) {
        $members = $DB->get_recordset_sql(query_group_members(), array('courseid' => $course_id));

        foreach ($members as $member) {
            $groups[$member->groupingid][$course_id][$member->userid] = true;
        }
    }
    return $groups;
}

/**
 * Função que busca todas as atividades (assign, forum) dentro de um modulo (course)
 * em ordem de apresentação do curso
 *
 * @param array $courses array de ids dos cursos moodle
 * @param bool $mostrar_nota_final
 * @param bool $mostrar_total
 * @throws Exception
 * @return report_unasus_GroupArray array(course_id => (assign_id1,assign_name1),(assign_id2,assign_name2)...)
 */
function report_unasus_get_atividades_cursos_ordem($courses, $mostrar_nota_final = false, $mostrar_total = false,
                                             $buscar_lti = false, $categoryid = 0) {

    if (empty($courses)) {
        throw new Exception("Falha ao obter as atividades, curso não informado.");
    }

    try {
        // Busca quais atividades não serão apresentadas nos relatórios
        $atividades_config_curso = report_unasus_get_activities_config_report($courses);
    } catch (Exception $e) {
        $atividades_config_curso = array();
    }

    $atividades = report_unasus_query_activities_ordered_courses($courses);
    $group_array = new report_unasus_GroupArray();

    foreach ($atividades as $atividade) {
        $chave = $atividade->activity_id.'-'.$atividade->module_id.'-'.$atividade->course_id;
        // Verifica se a atividade será apresentada nos relatórios
        // Se não encontrar na lista de "Ocultos", então apresenta

        if(!array_search($chave, $atividades_config_curso) || empty($atividades_config_curso)){

            switch ($atividade->module_name) {
                case 'lti' :

                    $atividade_object = new report_unasus_lti_activity2($atividade);
                    // se não for para buscar lti ou não for lti de tcc não processa os capítulos
                    $tcc_lti_array = null;

                    if ($buscar_lti) {
                        //verificar se é lit de TCC, retornando um array com os capítulos do tcc
                        $tcc_lti_array = report_unasus_process_header_tcc($atividade->activity_id, $atividade->course_id);
                    };

                    if (is_array($tcc_lti_array)) {

                        // Se for então adiciona a atividade no header como lti_tcc, anexando o array dos capítulos

                        $atividade->baseurl                = $tcc_lti_array['lti']->baseurl;
                        $atividade->consumer_key           = $tcc_lti_array['lti_config']['resourcekey'];
                        $atividade->custom_parameters      = $tcc_lti_array['lti_config']['customparameters'];

                        $group_array->add($atividade->course_id,
                            new report_unasus_lti_activity_tcc2($atividade,
                                $tcc_lti_array['tcc_definition']));
                    } else {
                        // se não for então adiciona atividade lti normal no header
                        $group_array->add($atividade->course_id, new report_unasus_lti_activity2($atividade));
                    }
                    break;
                default :
                    $atividade_object = new report_unasus_generic_activity($atividade);
                    break;

            }

            $group_array->add($atividade->course_id, $atividade_object);
        }
    }

    if ($mostrar_nota_final) {
        $cursos_com_nota_final = report_unasus_query_courses_com_nota_final($courses);
        foreach ($cursos_com_nota_final as $nota_final) {
            $atividade_nota_final = new report_unasus_final_grade($nota_final);
            $group_array->add($nota_final->course_id, $atividade_nota_final);
        }
    }

    if ($mostrar_total) {
        $cursos_com_nota_final = report_unasus_query_courses_com_nota_final($courses);
        foreach ($cursos_com_nota_final as $nota_final) {
            $atividade_nota_final_total = report_unasus_total_atividades_concluidas($nota_final);
            $group_array->add($nota_final->course_id, $atividade_nota_final_total);
        }
    }

    $atividades_all = $group_array->get_assoc();
    if (empty($atividades_all)) {
        print_error('no_valid_activity_found_error', 'report_unasus');
    }

    $atividades_ret = array();

    if ($buscar_lti) {
        // (false) {
        // passa por todas os módulos e todas as suas atividades e incui os módulos que tem LTI de tcc
        foreach ($atividades_all as $course_id => $atividades) {
            foreach ($atividades as $atividade) {
                // Sé conterá atividades de TCC se conseguir conectar com o Webservice do sistema de TCC
                if (is_a($atividade, 'report_unasus_lti_activity_tcc')) {
                    $atividades_ret[$course_id] = $atividades;
                    break;
                }
            }
        }
    } else {
        $atividades_ret = $atividades_all;
        // coloca as atividades em ordem de apresentação do curso

    }

    return $atividades_ret;
}

/**
 * Função que busca todas as atividades (assign, forum) dentro de um modulo (course)
 *
 * @param array $courses array de ids dos cursos moodle
 * @param bool $mostrar_nota_final
 * @param bool $mostrar_total
 * @throws Exception
 * @return report_unasus_GroupArray array(course_id => (assign_id1,assign_name1),(assign_id2,assign_name2)...)
 */
function report_unasus_get_atividades_cursos($courses, $mostrar_nota_final = false, $mostrar_total = false,
                                             $buscar_lti = false, $categoryid = 0) {

    if (empty($courses)) {
        throw new Exception("Falha ao obter as atividades, curso não informado.");
    }

    try {
        $atividades_config_curso = report_unasus_get_activities_config_report($courses);
    } catch (Exception $e) {
        $atividades_config_curso = array();
    }

    // Nesta query de assigns ainda estão voltando os diários - parte 1 e 2 - para o TCC
    $assigns    = report_unasus_query_assign_courses($courses);
    $foruns     = report_unasus_query_forum_courses($courses);
    $quizes     = report_unasus_query_quiz_courses($courses);
    $databases  = report_unasus_query_database_courses($courses);
    $scorms     = report_unasus_query_scorm_courses($courses);
    $ltis       = report_unasus_query_lti_courses_moodle($courses);

    $group_array = new report_unasus_GroupArray();

    // cria as atividades
    foreach ($assigns as $atividade) {
        $chave = $atividade->assign_id.'-'.$atividade->module_id.'-'.$atividade->course_id;
        if(!array_search($chave, $atividades_config_curso) || empty($atividades_config_curso)){
            $assign_object = new report_unasus_assign_activity($atividade);
            $group_array->add($atividade->course_id, $assign_object);
        }
    }

    foreach ($foruns as $forum) {
        $chave = $forum->forum_id.'-'.$forum->module_id.'-'.$forum->course_id;
        if(!array_search($chave, $atividades_config_curso) || empty($atividades_config_curso)) {
            $group_array->add($forum->course_id, new report_unasus_forum_activity($forum));
        }
    }

    foreach ($quizes as $quiz) {
        $chave = $quiz->quiz_id.'-'.$quiz->module_id.'-'.$quiz->course_id;
        if(!array_search($chave, $atividades_config_curso) || empty($atividades_config_curso)){
            $group_array->add($quiz->course_id, new report_unasus_quiz_activity($quiz));
        }
    }

    foreach ($databases as $database) {
        $chave = $database->database_id.'-'.$database->module_id.'-'.$database->course_id;
        if(!array_search($chave, $atividades_config_curso) || empty($atividades_config_curso)){
            $group_array->add($database->course_id, new report_unasus_db_activity($database));
        }
    }

    foreach ($scorms as $scorm) {
        $chave = $scorm->scorm_id.'-'.$scorm->module_id.'-'.$scorm->course_id;
        if (!array_search($chave, $atividades_config_curso) || empty($atividades_config_curso)) {
            $group_array->add($scorm->course_id, new report_unasus_scorm_activity($scorm));
        }
    }

    foreach ($ltis as $db_lti) {

        // verifica se está habilitado no config report
        $chave = $db_lti->lti_id.'-'.$db_lti->module_id.'-'.$db_lti->course_id;
        if(!array_search($chave, $atividades_config_curso) || empty($atividades_config_curso) ){

            // se não for para buscar lti ou não for lti de tcc não processa os capítulos
            $tcc_lti_array = null;

            if ($buscar_lti) {
                //verificar se é lit de TCC, retornando um array com os capítulos do tcc
                $tcc_lti_array = report_unasus_process_header_tcc($db_lti->lti_id, $db_lti->course_id);
            };

            if (is_array($tcc_lti_array)) {

                // Se for então adiciona a atividade no header como lti_tcc, anexando o array dos capítulos

                $db_lti->baseurl                = $tcc_lti_array['lti']->baseurl;
                $db_lti->consumer_key           = $tcc_lti_array['lti_config']['resourcekey'];
                $db_lti->custom_parameters      = $tcc_lti_array['lti_config']['customparameters'];

                $group_array->add($db_lti->course_id,
                    new report_unasus_lti_activity_tcc($db_lti,
                        $tcc_lti_array['lti_config'],
                        $tcc_lti_array['tcc_definition']));
            } else {
                // se não for então adiciona atividade lti normal no header
                $group_array->add($db_lti->course_id, new report_unasus_lti_activity($db_lti));
            }
        }
    }

    if ($mostrar_nota_final) {
        $cursos_com_nota_final = report_unasus_query_courses_com_nota_final($courses);
        foreach ($cursos_com_nota_final as $nota_final) {
            $group_array->add($nota_final->course_id, new report_unasus_final_grade($nota_final));
        }
    }

    if ($mostrar_total) {
        $cursos_com_nota_final = report_unasus_query_courses_com_nota_final($courses);
        foreach ($cursos_com_nota_final as $nota_final) {
            $group_array->add($nota_final->course_id, new report_unasus_total_atividades_concluidas($nota_final));
        }
    }

    $atividades_all = $group_array->get_assoc();

    if (empty($atividades_all)) {
        print_error('no_valid_activity_found_error', 'report_unasus');
    }

    $atividades_ret = array();

    if ($buscar_lti) {
        // (false) {
        // passa por todas os módulos e todas as suas atividades e incui os módulos que tem LTI de tcc
        foreach ($atividades_all as $course_id => $atividades) {
            foreach ($atividades as $atividade) {
                // Sé conterá atividades de TCC se conseguir conectar com o Webservice do sistema de TCC
                if (is_a($atividade, 'report_unasus_lti_activity_tcc')) {
                    $atividades_ret[$course_id] = $atividades;
                    break;
                }
            }
        }
    } else {
        $atividades_ret = $atividades_all;
    }

    return $atividades_ret;
}

/**
 * Processa os cabeçalhos do TCC, seus capítulos
 *
 * @param int $lti_id
 * @param int $course_id
 * @return array Com os headers dos capítulos de um TCC específico
 * @return boolean false Se não for um LTI de TCC
 */
function report_unasus_process_header_tcc($lti_id, $course_id) {

    // retorna o false se não for TCC
    $tcc_lti_result = report_unasus_lti_tcc_definition($lti_id, $course_id);

    if (!$tcc_lti_result) {
        return false;
    } else {
        $tcc_definition = $tcc_lti_result['tcc_definition'];
        $lti_config = $tcc_lti_result['lti_config'];
    }

    return $tcc_lti_result;
}

/**
 * Nome dos capítulos do TCC para serem usadas no cabeçalho
 *
 * @param $courses
 * @param report_unasus_GroupArray $group_array
 * @param bool $is_tcc
 * @return array
 */
function report_unasus_process_header_tcc_atividades($courses, report_unasus_GroupArray &$group_array) {

    // $ltis = retorna os capítulos dos tccs
    $ltis = report_unasus_query_lti_courses($courses);

    // Nenhuma atividade lti encontrada,
    // Retornar pois webservice retorna msg de erro e nao deve ser interado no foreach

    if (empty($ltis)) {
        return;
    }

    // passa por todos os os TCCs
    foreach ($ltis as $lti) {

        // $lti tem os dados de um tcc, e de seus capítulos

        // passa por todos os capítulos >> $lti->tcc_definition->chapter_definitions

        foreach ($lti->tcc_definition->chapter_definitions as $chapter) {

            // $chapter contém o chapter_definition do capítulo

            $db_model = new stdClass();

            // $chapter_definition contém os dados do capítulo (Título, Posição)
            $chapter_definition = $chapter->chapter_definition;

            $db_model->course_id = $lti->course_id;
            $db_model->course_name = $lti->course_name;

            $db_model->lti_id = $lti->id;
            $db_model->course_module_id = $lti->course_module_id;
            // $db_model->name = $lti->name;

            $db_model->name = $chapter_definition->title;
            $db_model->completionexpected = $lti->completionexpected;
            $db_model->position = $chapter_definition->position;

            $db_model->baseurl = $lti->baseurl;
            $db_model->grouping_id = $lti->grouping_id;
            $db_model->consumer_key = $lti->config['resourcekey'];

            // $group_array->add($db_model->course_id, new report_unasus_lti_activity_tcc($db_model));

        }
    }
}

/**
 * Função que busca os courses com suas respectivas atividades e datas de entrega
 * utilizada no get_atividade_modulos
 *
 * @param array $courses
 * @throws Exception
 * @global moodle_database $DB
 * @return moodle_recordset
 */
function report_unasus_query_activities_ordered_courses($courses)
{
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else  {
        $query = "
SELECT
    CASE
        WHEN m.name = 'assign' THEN a.id
        WHEN m.name = 'forum' THEN f.id
        WHEN m.name = 'quiz' THEN q.id
        WHEN m.name = 'data' THEN dt.id
        WHEN m.name = 'scorm' THEN s.id
        WHEN m.name = 'lti' THEN l.id
        ELSE 'Descrição não disponível'
    END AS activity_id,
    CASE
        WHEN m.name = 'assign' THEN a.name
        WHEN m.name = 'forum' THEN f.name
        WHEN m.name = 'quiz' THEN q.name
        WHEN m.name = 'data' THEN dt.name
        WHEN m.name = 'scorm' THEN s.name
        WHEN m.name = 'lti' THEN l.name
        ELSE 'Descrição não disponível'
    END AS activity_name,
    cm.completionexpected,
    CASE
        WHEN m.name = 'assign' THEN a.grade
        WHEN m.name = 'forum' THEN f.scale
        WHEN m.name = 'quiz' THEN q.grade
        WHEN m.name = 'data' THEN dt.scale
        WHEN m.name = 'scorm' THEN s.maxgrade
        WHEN m.name = 'lti' THEN l.grade
        ELSE 'Descrição não disponível'
    END AS activity_grade,
    /* no submission quer dizer que esta
     atividade não possui uma entrega */
    CASE
        WHEN m.name = 'assign' THEN a.nosubmissions
        WHEN m.name = 'assign' THEN a.nosubmissions
        WHEN m.name = 'forum' THEN false
        WHEN m.name = 'quiz' THEN false
        WHEN m.name = 'data' THEN false
        WHEN m.name = 'scorm' THEN true
        WHEN m.name = 'lti' THEN true
        ELSE 'Descrição não disponível'
    END AS activity_nosubmissions,
    c.id AS course_id,
    REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
    cm.groupingid as grouping_id,
    cm.module AS module_id,
    m.name AS module_name,
    cm.id AS coursemoduleid,

    cm.completion,
    cm.instance AS instance_id,
    cs.section AS section_number,
    cs.name AS section_name,
    cs.sequence,
    FIND_IN_SET(cm.id, cs.sequence) AS module_order
FROM course_sections cs
JOIN course c ON cs.course = c.id
JOIN course_modules cm ON FIND_IN_SET(cm.id, cs.sequence) > 0
JOIN modules m ON cm.module = m.id
LEFT JOIN assign a ON cm.instance = a.id AND m.name = 'assign'
LEFT JOIN forum f ON cm.instance = f.id AND m.name = 'forum'
LEFT JOIN quiz q ON cm.instance = q.id AND m.name = 'quiz'
LEFT JOIN data dt ON cm.instance = dt.id AND m.name = 'data'
LEFT JOIN scorm s ON cm.instance = s.id AND m.name = 'scorm'
LEFT JOIN lti l ON cm.instance = l.id AND m.name = 'lti'
WHERE
    c.id IN ({$string_courses})
    AND (cm.completion != 0)
    AND m.name in ('assign','forum','quiz','data','scorm','lti')
ORDER BY
    c.sortorder ASC,
    cs.section ASC,
    module_order ASC;";

        return $DB->get_recordset_sql($query);
    }
}



/**
 * Função que busca os courses com suas respectivas atividades e datas de entrega
 * utilizada no get_atividade_modulos
 *
 * @param array $courses
 * @throws Exception
 * @global moodle_database $DB
 * @return moodle_recordset
 */
function report_unasus_query_assign_courses($courses)
{
    global $DB, $SITE;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else  {
        $query = "SELECT a.id AS assign_id,
                     a.name AS assign_name,
                     cm.completionexpected,
                     a.nosubmissions,
                     a.grade,
                     c.id AS course_id,
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
                     cm.groupingid AS grouping_id,
	                 cm.module AS module_id,
	                 m.name AS module_name,
                     cm.id AS coursemoduleid
                FROM {course} AS c
           LEFT JOIN {assign} AS a
                  ON (c.id = a.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance=a.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'assign')
               WHERE c.id IN ({$string_courses})
                 -- AND cm.visible=TRUE
            ORDER BY c.sortorder, assign_id";

        return $DB->get_recordset_sql($query, array('siteid' => $SITE->id));
    }
}

/**
 * Função que busca os courses com seus respectivos quiz e datas de entrega
 * utilizada no get_atividade_modulos
 *
 * @global moodle_database $DB
 * @param array $courses
 * @return moodle_recordset
 */
function report_unasus_query_quiz_courses($courses) {
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else {
        $query = "SELECT q.id AS quiz_id,
                     q.name AS quiz_name,
                     q.timeopen,
                     cm.completionexpected,
                     q.grade,
                     c.id AS course_id,
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
                     cm.groupingid as grouping_id,
	                 cm.module AS module_id,
	                 m.name AS module_name,
                     cm.id AS coursemoduleid
                FROM {course} AS c
                JOIN {quiz} AS q
                  ON (c.id = q.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance = q.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'quiz')
               WHERE c.id IN ({$string_courses})
                  -- AND cm.visible=TRUE
             ORDER BY c.sortorder, quiz_id";

        return $DB->get_recordset_sql($query, array('siteid' => SITEID));
    }
}

function report_unasus_query_database_courses($courses) {
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else {
        $query = "SELECT d.id AS database_id,
                     d.name AS database_name,
	                 cm.completionexpected,
	                 c.id AS course_id,
	                 REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
	                 cm.groupingid AS grouping_id,
	                 cm.module AS module_id,
	                 m.name AS module_name,
                     cm.id AS coursemoduleid
                FROM {course} AS c
           LEFT JOIN {data} AS d
                  ON (c.id = d.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance = d.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'data')
               WHERE c.id IN ({$string_courses})
                  -- AND cm.visible=TRUE
            ORDER BY c.sortorder, database_id";

        return $DB->get_recordset_sql($query, array('siteid' => SITEID));
    }
}

function report_unasus_query_scorm_courses($courses) {
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else {
        $query = "SELECT s.id AS scorm_id,
                     s.name AS scorm_name,
	                 cm.completionexpected,
	                 c.id AS course_id,
	                 REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
	                 cm.groupingid AS grouping_id,
	                 cm.module AS module_id,
	                 m.name AS module_name,
                     cm.id AS coursemoduleid
                FROM {course} AS c
           LEFT JOIN {scorm} AS s
                  ON (c.id = s.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance = s.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'scorm')
               WHERE c.id IN ({$string_courses})
                  -- AND cm.visible=TRUE
            ORDER BY c.sortorder, scorm_id";

        return $DB->get_recordset_sql($query, array('siteid' => SITEID));
    }
}

/**
 * Função para buscar o tcc_definition e os capítulos de uma atividade de lti de TCC
 *
 * @param int $lti_id
 * @param int $course_id
 * @return array FIRST: stdClass tcc_definition para LTI de TCC SECOND: config dos dados do LTI
 * @return boolean false Para LTI que é de TCC
 * @throws dml_missing_record_exception
 * @throws dml_multiple_records_exception
 */
function report_unasus_lti_tcc_definition($lti_id, $course_id) {
    global $DB;

    // pega os dados da atividade LIT do TCC
    $lti = $DB->get_record_sql(query_lti_activity(), array('course' => $course_id, 'lti_id' => $lti_id));
    if ($lti) {
        $config = $DB->get_records_sql_menu(query_lti_activities_config(), array('typeid' => $lti->typeid));
        $customparameters = report_unasus_get_tcc_definition($config['customparameters']);
        $consumer_key = empty($config['resourcekey']) ? "" : $config['resourcekey'];
        $base_url = empty($lti->baseurl) ? "" : $lti->baseurl;

        // WS Client
        $client = new report_unasus_SistemaTccClient($base_url, $consumer_key);
        $object = null;
        if ($client->getZendInstalled()) {
            $tcc_definition = empty($customparameters['tcc_definition']) ? "" : $customparameters['tcc_definition'];
            $object = $client->get_tcc_definition($tcc_definition);
        }
    }
    if (!$object) {
        // Ocorreu alguma falha
        return false;
    }

    return array('lti' => $lti, 'tcc_definition' => $object->tcc_definition, 'lti_config' => $config);
}

/**
 * Função para buscar atividades de lti
 *
 * @param $courses
 * @internal param \type $tcc_definition_id
 * @return array
 */
function report_unasus_query_lti_courses($courses) {
    global $DB;

    if (empty($courses)) {
        return false;
    }

    $courses = is_string($courses) ? explode(',', $courses) : $courses;
    $lti_activities = array();

    foreach ($courses as $course) {
        if (is_array($course)){
            foreach ($course as $course_id => $c) {
                foreach ($c as $id_course => $course_name) {
                    $ltis = $DB->get_records_sql(query_lti_activities(), array('course' => $id_course));

                    $course_name = $DB->get_field('course', 'fullname', array('id' => $id_course));

                    foreach ($ltis as $lti) {
                        $config = $DB->get_records_sql_menu(query_lti_activities_config(), array('typeid' => $lti->typeid));
                        $customparameters = report_unasus_get_tcc_definition($config['customparameters']);
                        $consumer_key = empty($config['resourcekey']) ? "" : $config['resourcekey'];
                        $base_url = empty($lti->baseurl) ? "" : $lti->baseurl;

                        // WS Client
                        $client = new report_unasus_SistemaTccClient($base_url, $consumer_key);
                        $object = null;
                        if ($client->getZendInstalled()) {
                            $tcc_definition = empty($customparameters['tcc_definition']) ? "" : $customparameters['tcc_definition'];
                            $object = $client->get_tcc_definition($tcc_definition);
                        }

                        if (!$object) {
                            // Ocorreu alguma falha
                            continue;
                        }

                        $object->id = $lti->id;
                        $object->course_id = $course;
                        $object->course_name = $course_name;
                        $object->course_module_id = $lti->cmid;
                        $object->config = $config;
                        $object->custom_parameters = $customparameters;
                        $object->completionexpected = $lti->completionexpected;
                        $object->grouping_id = $lti->grouping_id;
                        $object->baseurl = $lti->baseurl;

                        array_push($lti_activities, $object);
                    }
                }
            }
        } else {
            $ltis = $DB->get_records_sql(query_lti_activities(), array('course' => $course));

            $course_name = $DB->get_field('course', 'fullname', array('id' => $course));
            foreach ($ltis as $lti) {
                $config = $DB->get_records_sql_menu(query_lti_activities_config(), array('typeid' => $lti->typeid));
                $customparameters = report_unasus_get_tcc_definition($config['customparameters']);
                $consumer_key = empty($config['resourcekey']) ? "" : $config['resourcekey'];
                $base_url = empty($lti->baseurl) ? "" : $lti->baseurl;

                // WS Client
                $client = new report_unasus_SistemaTccClient($base_url, $consumer_key);
                $object = null;
                if ($client->getZendInstalled()) {
                    $tcc_definition = empty($customparameters['tcc_definition']) ? "" : $customparameters['tcc_definition'];
                    $object = $client->get_tcc_definition($tcc_definition);
                }

                if (!$object) {
                    // Ocorreu alguma falha
                    continue;
                }

                $object->id = $lti->id;
                $object->course_id = $course;
                $object->course_name = $course_name;
                $object->course_module_id = $lti->cmid;
                $object->config = $config;
                $object->custom_parameters = $customparameters;
                $object->completionexpected = $lti->completionexpected;
                $object->grouping_id = $lti->grouping_id;
                $object->baseurl = $lti->baseurl;

                array_push($lti_activities, $object);
            }
        }
    }

    return $lti_activities;
}

/**
 * Função para buscar atividades de lti do moodle
 *
 * @param $courses
 * @return array
 */
function report_unasus_query_lti_courses_moodle($courses) {
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else {
        $query = "SELECT l.id AS lti_id,
		             l.name AS name,
		             c.id AS course_id,
		             REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
                     cm.id AS coursemoduleid,
	                 cm.groupingid AS grouping_id,
	                 cm.module AS module_id,
	                 m.name AS module_name,
	                 cm.completionexpected
                FROM {course} AS c
           LEFT JOIN {lti} AS l
                  ON (c.id = l.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance=l.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'lti')
               WHERE c.id IN ({$string_courses}) -- AND cm.visible=TRUE
            ORDER BY c.sortorder, lti_id";

        return $DB->get_recordset_sql($query, array('siteid' => SITEID));
    }
}

/**
 * Retorna definições da lti
 * @param type $tcc_definition
 * @return array
 */
function report_unasus_get_tcc_definition($tcc_definition) {
    $tcc_definition = explode(';', $tcc_definition);
    $arr = array();

    foreach ($tcc_definition as $value) {
        $config = explode('=', $value);
        if (isset($config[0]) && isset($config[1])) {
            $arr[$config[0]] = $config[1];
        }
    }
    return $arr;
}

function report_unasus_query_forum_courses($courses) {
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else {
        $query = "SELECT f.id AS forum_id,
                     f.name AS forum_name,
                     cm.completionexpected,
                     c.id AS course_id,
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
                     cm.groupingid as grouping_id,
	                 cm.module AS module_id,
	                 m.name AS module_name,
                     cm.id AS coursemoduleid
                FROM {course} AS c
           LEFT JOIN {forum} AS f
                  ON (c.id = f.course AND c.id != :siteid)
           LEFT JOIN {grade_items} AS gi
                  ON (gi.courseid=c.id AND gi.itemtype = 'mod' AND
                      gi.itemmodule = 'forum' AND gi.iteminstance=f.id AND 
                      gi.itemnumber = 0 )
                JOIN {course_modules} cm
                  ON (cm.course=c.id AND cm.instance=f.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'forum')
               WHERE c.id IN ({$string_courses})
                 -- AND cm.visible=TRUE
                 AND (gi.id=TRUE OR cm.completion != 0)
            ORDER BY c.sortorder, forum_id";

        return $DB->get_recordset_sql($query, array('siteid' => SITEID));
    }
}

function report_unasus_query_courses_com_nota_final($courses) {
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    if (empty($string_courses)) {
        return [];
    } else {
        $query = "SELECT gi.id,
                     gi.courseid AS course_id,
                     gi.itemname
                FROM {grade_items} gi
                JOIN {course} AS c
                  ON (c.id = gi.courseid)
               WHERE (gi.itemtype LIKE 'course'
                 AND itemmodule IS NULL
                 AND gi.courseid IN ({$string_courses}))
            ORDER BY c.sortorder";
        return $DB->get_recordset_sql($query);
    }
}

/**
 * Verifica se o usuário não enviar uma listagem de modulos obtem todos os modulos válidos (possuem atividade)
 *
 * @param array $modulos
 * @return string
 */
function report_unasus_get_modulos_validos($modulos) {

    $string_modulos = empty($modulos) ? report_unasus_int_array_to_sql(report_unasus_get_id_modulos()) : report_unasus_int_array_to_sql($modulos);
    return $string_modulos;
}

function report_unasus_get_prazo_avaliacao() {
    global $DB;
    return (int) $DB->get_field('config', 'value', array('name' => 'report_unasus_prazo_avaliacao'));
}

function report_unasus_get_prazo_maximo_avaliacao() {
    global $DB;
    return (int) $DB->get_field('config', 'value', array('name' => 'report_unasus_prazo_maximo_avaliacao'));
}

function report_unasus_get_passing_grade_percentage() {
    global $DB;
    return (int) $DB->get_field('config', 'value', array('name' => 'report_unasus_passing_grade_percentage'));
}

function report_unasus_get_prazo_maximo_entrega() {
    global $DB;
    return (int) $DB->get_field('config', 'value', array('name' => 'report_unasus_prazo_maximo_entrega'));
}

/**
 * Classe que constroi a tabela para os relatorios, extende a html_table
 * da MoodleAPI.
 *
 */
class report_unasus_table extends html_table {

    // Para o caso que a tabela tenha um cabeçalho de uma única linha.
    // Head 1  |  Head 2  |  Head 3
    //
    // Data 1  |  Data 2  |  Data 3
    // Data 4  |  Date 5  |  Data 6
    function build_single_header($coluns) {
        $new_coluns = [];
        $student = new html_table_cell('Estudantes');
        $student->header = true;
        $student->attributes = array('class' => 'title estudante_header');

        array_shift($coluns);
        foreach ($coluns as $colum) {

            $new_colum = new html_table_cell($colum);
            $new_colum->header = true;
            $new_colum->attributes = array('class' => 'title c_body');
            array_push($new_coluns, $new_colum);
        }
        array_unshift($new_coluns, $student);
        $this->head = $new_coluns;
    }

    // Para o caso de um cabeçalho duplo, que a MoodleAPI não cobre
    //         |  Group 1              |    Group 2
    // Types   |  Head 1   |  Head 2   |    Head 3   |  Head 4
    // Type 1  |  Data     |  Data     |    Data     |  Data
    function build_double_header($grouped_coluns, $person_name = 'Estudantes') {

        $this->data = array();

        $blank = new html_table_cell();
        $blank->attributes = array('class' => 'blank');

        $student = new html_table_cell($person_name);
        $student->header = true;
        $student->attributes = array('class' => 'ultima_atividade title estudante_header');

        $heading1 = array(); // Primeira linha
        $heading1[] = $blank; // Acrescenta uma célula em branco na primeira linha

        $heading2 = array(); // Segunda linha
        $heading2[] = $student;

        /* box */

        $ultima_atividade_modulo = array();
        $ultimo_alvo = 0;
        $ultima_atividade_modulo[] = $ultimo_alvo;
        foreach ($grouped_coluns as $module_name => $activities) {
            $ultimo_alvo += count($activities);
            $ultima_atividade_modulo[] = $ultimo_alvo;
        }

        $count = 1;
        foreach ($grouped_coluns as $module_name => $activities) {
            $module_cell = new html_table_cell($module_name);
            $module_cell->header = true;
            $module_cell->colspan = count($activities);
            $module_cell->attributes = array('class' => 'modulo_header c_body');
            $heading1[] = $module_cell;

            foreach ($activities as $activity) {
                $activity_cell = new html_table_cell($activity);
                $activity_cell->header = true;
                $activity_cell->attributes = array('class' => 'relatorio-unasus rotate c_body');
                /* box */
                if (in_array($count, $ultima_atividade_modulo)) {
                    $activity_cell->attributes = array('class' => 'ultima_atividade rotate c_body');
                }
                $heading2[] = $activity_cell;
                $count++;
            }
        }

        $this->data[] = new html_table_row($heading1);
        $this->data[] = new html_table_row($heading2);
    }

}

class report_unasus_html_table_cell_header extends html_table_cell {

    public function __construct($text = null) {
        $this->text = $text;
        $this->attributes['class'] = '';
    }
}

/**
 * Estrutura de dados semelhante ao Array() do php, que permite armazenar mais
 * de um dado em uma mesma chave
 *
 * @author Gabriel Mazetto
 */
class report_unasus_GroupArray {

    private $data = array();

    function add($key, $value) {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = array();
        }

        array_push($this->data[$key], $value);
    }

    function add_exclusive($key, $value) {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = array();
            array_push($this->data[$key], $value);
        }
    }

    function add_index($key, $index, $value) {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key][$index] = array();
        }
        array_push($this->data[$key][$index], $value);
    }

    function get($key) {
        return $this->data[$key];
    }

    function get_assoc() {
        return $this->data;
    }

}

/**
 * Transforma um array de inteiros numa string unica
 * EX: array(32,33,45)  para  "32,33,45
 *
 * @param array $array
 * @return String
 */

// TODO: Trocar esta função por get_in_or_equal() em lib/dml/moodle_database.php
function report_unasus_int_array_to_sql($array) {
    if (!is_array($array)) {
        return $array;
    }
    return implode(',', $array);
}

/**
 * Transforma um array de inteiros numa string unica
 * EX: array(32,33,45)  para  "IN (32,33,45)"
 * EX: array(32)  para  "= 32"
 *
 * @param array $array
 * @return String
 */

// TODO: Trocar esta função por get_in_or_equal() em lib/dml/moodle_database.php
function report_unasus_int_array_to_IN_OR_EQUAL($array) {
    if (!is_array($array)) {
        if (is_integer($array)) {
            return "= $array";
        } else {
            return $array;
        }

    }

    $return_array = implode(',', $array);

    if (strpos($return_array, ',') === FALSE) {
        $return_array = "= {$return_array}";
    } else {
        $return_array = "IN ({$return_array})";
    }

    return $return_array;
}

/**
 * Recupera o curso UFSC a partir do código de curso moodle que originou a visualização do relatório
 *
 * A informação do curso UFSC está armazenada no campo idnumber da categoria principal (nivel 1)
 *
 * @return bool|string
 */
function report_unasus_get_curso_ufsc_id() {
    global $DB;

    $course = $DB->get_record('course', array('id' => report_unasus_get_course_id()), 'category', MUST_EXIST);
    $category = $DB->get_record('course_categories', array('id' => $course->category), 'id, idnumber, depth, path', MUST_EXIST);

    if ($category->depth > 1) {
        // Pega o primeiro id do caminho
        preg_match('/^\/([0-9]+)\//', $category->path, $matches);
        $root_category = $matches[1];

        $category = $DB->get_record('course_categories', array('id' => $root_category), 'id, idnumber, depth, path', MUST_EXIST);
    }

    $curso_ufsc_id = str_replace('curso_', '', $category->idnumber, $count);
    return ($count) ? $curso_ufsc_id : false;
}

function report_unasus_get_course_id() {
    return required_param('course', PARAM_INT);
}

///
/// Funcionalidades semelhantes duplicadas de tool tutores
/// TODO: refatorar e deduplicar as funcinoalidades abaixo de forma que ambas ferramentas disponibilizem uma única API.
///

/*
 * @dias_atras quantos dias antes da data atual no formato (P120D)
 * @tempo_pulo de quanto em quanto tempo deve ser o itervalo (P1D)
 * @date_format formato da data em DateTime()
 */

function report_unasus_get_time_interval($data_inicio, $data_fim, $tempo_pulo, $date_format) {
    // Intervalo de dias no formato d/m
    $interval = $data_inicio->diff($data_fim);

    $begin = clone $data_fim;
    $begin->sub($interval);

    $increment = new DateInterval($tempo_pulo);
    $daterange = new DatePeriod($begin, $increment, $data_fim);

    $dias_meses = array();
    foreach ($daterange as $date) {
        $dias_meses[] = $date->format($date_format);
    }
    return $dias_meses;
}

/**
 * Retorna um intervalo entre duas datas com meses
 *
 * @param string $data_inicio data no formato informado em $date_format
 * @param string $data_fim data no formato informado em $date_format
 * @param string $tempo_pulo de quanto em quanto tempo deve ser o itervalo (P1D)
 * @param string $date_format formato da data em DateTime()
 * @return array
 */
function report_unasus_get_time_interval_com_meses($data_inicio, $data_fim, $tempo_pulo, $date_format_read, $date_format_display) {
    $data_inicio = date_create_from_format($date_format_read, $data_inicio);
    $data_fim    = date_create_from_format($date_format_read, $data_fim);

    $interval = $data_inicio->diff($data_fim);

    $begin = clone $data_fim;
    $begin->sub($interval);

    $increment = new DateInterval($tempo_pulo);
    $daterange = new DatePeriod($begin, $increment, $data_fim);

    $meses = array();
    foreach ($daterange as $date) {
        $mes = strftime("%B", $date->format('U'));
        if (!array_key_exists($mes, $meses)) {
            $meses[$mes] = null;
        }
        $meses[$mes][] = $date->format($date_format_display);
    }
    return $meses;
}

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class report_unasus_date_picker_moodle_form extends moodleform {

    function definition() {
        global $CFG;
        $mform = & $this->_form;

        $mform->addElement('date_selector', 'assesstimefinish', $this->label);
        $mform->setAttributes(array('class' => ''));
    }

    function validation($data, $files) {
        return array();
    }

}

/**
 * Verifica se um intervalo de datas são validos
 *
 * Compara se a data de inicio é menor que a de fim e se as strings são datas validas
 *
 * @param string $data_inicio data
 * @param string $data_fim data
 * @return bool
 */
function report_unasus_date_interval_is_valid($data_inicio, $data_fim) {
    if (report_unasus_date_is_valid($data_inicio) && report_unasus_date_is_valid($data_fim)) {
        $diferenca_datas = date_diff(date_create_from_format('d/m/Y', $data_inicio), date_create_from_format('d/m/Y', $data_fim));
        //intervalo de data de inicio menor que a de fim
        if ($diferenca_datas->invert == 0) {
            return true;
        }
    }
    return false;
}

/**
 * Verifica se a string informada é uma data valida, EX: 22/10/1988
 *
 * @param $str String data
 * @return bool
 */
function report_unasus_date_is_valid($str) {
    if (substr_count($str, '/') == 2) {
        list($d, $m, $y) = explode('/', $str);
        return checkdate($m, $d, sprintf('%04u', $y));
    }

    return false;
}

/**
 * @FIXME bug grafael não aceita um grafico de dot chart com todos os varores de entradas nulos
 * Devido a um bug no grafael que não aceita todos os valores no gráfico serem igual a zero
 * faz-se necessário a criacao desta funcao para verificar se, no array informado, todos os valores
 * são ou não iguais a zero.
 *
 * @param $dados array( tutores => datas => quantidade de acesso)
 * @return bool
 */
function report_unasus_dot_chart_com_tutores_com_acesso($dados) {
    foreach ($dados as $tutor) {
        foreach ($tutor as $dia) {
            if ($dia[0] != 0)
                return true;
        }
    }
    return false;
}

/**
 * @param $nome_atividade
 * @param $atividade
 * @param $courseid
 * @param $grupo
 * @param $report
 * @param bool $is_boletim
 * @return array
 */
function report_unasus_get_atividades($nome_atividade, $atividade, $courseid, $grupo, $report, $is_boletim = false)
{

    global $DB;

    $relationship = local_tutores_grupos_tutoria::get_relationship_tutoria($report->get_categoria_turma_ufsc());
    $cohort_estudantes = local_tutores_grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

    switch ($nome_atividade) {
        case 'report_unasus_assign_activity':
            $params = array(
                'courseid' => $courseid,
                'courseid2' => $courseid,
                'assignmentid' => $atividade->id,
                'assignmentid2' => $atividade->id,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id);
            $query = query_atividades_from_users($cohort_estudantes);
            break;
        case 'report_unasus_forum_activity':
            $params = array(
                'courseid' => $courseid,
                'courseid2' => $courseid,
                //'enrol_courseid' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
                'forumid' => $atividade->id);
            $query = query_postagens_forum_from_users($cohort_estudantes);
            break;
        case 'report_unasus_quiz_activity':
            $params = array(
                'courseid' => $courseid,
                'courseid2' => $courseid,
                'assignmentid' => $atividade->id,
                'assignmentid2' => $atividade->id,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
                'forumid' => $atividade->id);
            $query = query_quiz_from_users($cohort_estudantes);
            break;
        case 'report_unasus_db_activity':
            $params = array(
                'id_activity' => $atividade->id,
                'courseid' => $courseid,
                'courseid2' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
                'coursemoduleid' => $atividade->coursemoduleid
            );
            $query = query_database_adjusted_from_users($cohort_estudantes);
            break;
        case 'report_unasus_scorm_activity':
            $params = array(
                'id_activity' => $atividade->id,
                'courseid' => $courseid,
                'courseid2' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
            );
            $query = query_scorm_from_users($cohort_estudantes);
            break;
        case 'report_unasus_lti_activity':
            $params = array(
                'id_activity' => $atividade->id,
                'courseid' => $courseid,
                'courseid2' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
            );
            $query = query_lti_from_users($cohort_estudantes);
            break;
        case 'report_unasus_lti_activity_tcc':
        // case 'report_unasus_lti_tcc':
            $params = array(
                'courseid' => $courseid,
                'courseid2' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
            );
            $query = query_grades_lti($cohort_estudantes);
            break;
        default:
            if ($is_boletim) { //Nota final para relatório boletim
                $params = array(
                    'courseid' => $courseid,
                    'courseid2' => $courseid,
                    'relationship_id' => $relationship->id,
                    'cohort_relationship_id' => $cohort_estudantes->id,
                    'grupo' => $grupo->id);
                $query = query_nota_final($cohort_estudantes);
                break;
            }
            break;
    }


    return $DB->get_records_sql($query, $params);
}

/**
 * @param $nome_atividade
 * @param $atividade
 * @param $courseid
 * @param $grupo
 * @param $report
 * @param bool $is_boletim
 * @return array
 */
function report_unasus_get_atividades2($nome_atividade, $atividade, $courseid, $grupo, $report, $is_boletim = false)
{

    global $DB;

    $relationship = local_tutores_grupos_tutoria::get_relationship_tutoria($report->get_categoria_turma_ufsc());
    $cohort_estudantes = local_tutores_grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

    switch ($nome_atividade) {
        case 'report_unasus_generic_activity' :
            switch ($atividade->module_name) {
                case 'assign':
                    $params = array(
                        'courseid' => $courseid,
                        'courseid2' => $courseid,
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id);
                    $query = query_atividades_from_users($cohort_estudantes);
                    break;
                case 'forum':
                    $params = array(
                        'courseid' => $courseid,
                        'courseid2' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'forumid' => $atividade->id);
                    $query = query_postagens_forum_from_users($cohort_estudantes);
                    break;
                case 'quiz':
                    $params = array(
                        'courseid' => $courseid,
                        'courseid2' => $courseid,
                        'assignmentid' => $atividade->id,
                        'assignmentid2' => $atividade->id,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'forumid' => $atividade->id);
                    $query = query_quiz_from_users($cohort_estudantes);
                    break;
                case 'data':
                    $params = array(
                        'id_activity' => $atividade->id,
                        'courseid' => $courseid,
                        'courseid2' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                        'coursemoduleid' => $atividade->coursemoduleid
                    );
                    $query = query_database_adjusted_from_users($cohort_estudantes);
                    break;
                case 'scorm':
                    $params = array(
                        'id_activity' => $atividade->id,
                        'courseid' => $courseid,
                        'courseid2' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                    );
                    $query = query_scorm_from_users($cohort_estudantes);
                    break;
                case 'lti':
                    $params = array(
                        'id_activity' => $atividade->id,
                        'courseid' => $courseid,
                        'courseid2' => $courseid,
                        'relationship_id' => $relationship->id,
                        'cohort_relationship_id' => $cohort_estudantes->id,
                        'grupo' => $grupo->id,
                    );
                    $query = query_lti_from_users($cohort_estudantes);
                    break;
            }
            break;
        case 'report_unasus_lti_activity_tcc2':
            // case 'report_unasus_lti_tcc':
            $params = array(
                'courseid' => $courseid,
                'courseid2' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
            );
            $query = query_grades_lti($cohort_estudantes);
            break;
        default:
            if ($is_boletim) { //Nota final para relatório boletim
                $params = array(
                    'courseid' => $courseid,
                    'courseid2' => $courseid,
                    'relationship_id' => $relationship->id,
                    'cohort_relationship_id' => $cohort_estudantes->id,
                    'grupo' => $grupo->id);
                $query = query_nota_final($cohort_estudantes);
                break;
            }
            break;
    }

    return $DB->get_records_sql($query, $params);
}

function report_unasus_get_activities_config_report($courses) {
    global $DB;

    $string_courses = report_unasus_get_modulos_validos($courses);

    $query = "SELECT id,
                      CONCAT(activityid,'-',moduleid,'-',courseid)
                FROM {activities_course_config} AS config
               WHERE config.courseid IN ({$string_courses})
               ORDER BY courseid, moduleid, activityid
             ";

    return $DB->get_records_sql_menu($query);
}
