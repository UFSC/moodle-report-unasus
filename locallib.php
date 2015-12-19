<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/tutores/middlewarelib.php');
require_once($CFG->dirroot . '/local/tutores/lib.php');
require_once($CFG->dirroot . '/report/unasus/datastructures.php');
require_once($CFG->dirroot . '/report/unasus/activities_datastructures.php');
require_once($CFG->dirroot . '/report/unasus/relatorios/queries.php');
require_once($CFG->dirroot . '/report/unasus/relatorios/loops.php');


function get_datetime_from_unixtime($unixtime) {
    return date_create(date("Y-m-d H:m:s", $unixtime));
}

function get_count_estudantes($categoria_turma) {
    $middleware = Middleware::singleton();

    $relationship = grupos_tutoria::get_relationship_tutoria($categoria_turma);
    $cohort_estudantes = grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

    $query = "SELECT rg.id AS grupo_id, COUNT(DISTINCT rm.userid)
                FROM {relationship_groups} rg
           LEFT JOIN {relationship_members} rm
                  ON (rg.relationshipid=:relationship_id
                 AND rg.id=rm.relationshipgroupid
                 AND rm.relationshipcohortid=:cohort_id)
          INNER JOIN {user} u
                  ON (u.id=rm.userid)
            GROUP BY rg.name
            ORDER BY rg.id";
    $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_estudantes->id);

    $result = $middleware->get_records_sql_menu($query, $params);

    foreach ($result as $key => $value) {
        $result[$key] = (int) $value;
    }

    return $result;
}

function get_count_estudantes_orientacao($categoria_turma) {
    $middleware = Middleware::singleton();

    $relationship = grupo_orientacao::get_relationship_orientacao($categoria_turma);
    $cohort_estudantes = grupo_orientacao::get_relationship_cohort_estudantes($relationship->id);

    $query = "SELECT rg.id AS grupo_id, COUNT(DISTINCT rm.userid)
                FROM {relationship_groups} rg
           LEFT JOIN {relationship_members} rm
                  ON (rg.relationshipid=:relationship_id
                 AND rg.id=rm.relationshipgroupid
                 AND rm.relationshipcohortid=:cohort_id)
          INNER JOIN {user} u
                  ON (u.id=rm.userid)
            GROUP BY rg.name
            ORDER BY rg.id";
    $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_estudantes->id);

    $result = $middleware->get_records_sql_menu($query, $params);

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
function get_nomes_cohorts($categoria_curso) {
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
 * Dado que alimenta a lista do filtro polos
 *s
 * @param $categoria_turma
 * @return array
 */
function get_polos($categoria_turma) {
    $academico = Middleware::singleton();

    #$relationship = grupos_tutoria::get_relationship_tutoria($curso_ufsc);
    $relationship = grupos_tutoria::get_relationship_tutoria($categoria_turma);
    $cohort_estudantes = grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

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

    return $polos;
}

function get_final_grades($id_aluno, $course_id){

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

function get_id_nome_modulos($ufsc_category, $method = 'get_records_sql_menu') {
    global $DB, $SITE;

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
                AND c.visible=TRUE
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
function get_nome_modulos($categoria_curso) {
    $modulos = get_id_nome_modulos($categoria_curso, 'get_records_sql');

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

function get_id_modulos() {
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

function get_id_nome_atividades() {
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
function get_agrupamentos_membros($courses) {
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
 *
 * @param array $courses array de ids dos cursos moodle
 * @param bool $mostrar_nota_final
 * @param bool $mostrar_total
 * @throws Exception
 * @return GroupArray array(course_id => (assign_id1,assign_name1),(assign_id2,assign_name2)...)
 */
function get_atividades_cursos($courses, $mostrar_nota_final = false, $mostrar_total = false, $buscar_lti = true, $categoryid = 0) {

    if (empty($courses)) {
        throw new Exception("Falha ao obter as atividades, curso não informado.");
    }

    $atividades_config_curso = array();

    if ($categoryid != 0){
        $atividades_config_curso = get_activities_config_report($categoryid, $courses);
    }

    // Nesta query de assigns ainda estão voltando os diários - parte 1 e 2 - para o TCC
    $assigns = query_assign_courses($courses);
    $foruns = query_forum_courses($courses);
    $quizes = query_quiz_courses($courses);
    $databases = query_database_courses($courses);
    $scorms = query_scorm_courses($courses);

    $group_array = new GroupArray();

    foreach ($assigns as $atividade) {

        if (!empty($atividades_config_curso)){
            if(array_search($atividade->assign_id, $atividades_config_curso)){
                $assign_object = new report_unasus_assign_activity($atividade, $atividades_config_curso);
                $group_array->add($atividade->course_id, $assign_object);
            }
        } else {
            $assign_object = new report_unasus_assign_activity($atividade, $atividades_config_curso);
            $group_array->add($atividade->course_id, $assign_object);
        }
    }

    foreach ($foruns as $forum) {

        if (!empty($atividades_config_curso)){
            if(array_search($forum->forum_id, $atividades_config_curso)) {
                $group_array->add($forum->course_id, new report_unasus_forum_activity($forum, $atividades_config_curso));
            }
        } else {
            $group_array->add($forum->course_id, new report_unasus_forum_activity($forum, $atividades_config_curso));
        }
    }

    foreach ($quizes as $quiz) {
        if (!empty($atividades_config_curso)){
            if(array_search($quiz->quiz_id, $atividades_config_curso)){
                $group_array->add($quiz->course_id, new report_unasus_quiz_activity($quiz, $atividades_config_curso));
            }
        } else {
            $group_array->add($quiz->course_id, new report_unasus_quiz_activity($quiz, $atividades_config_curso));
        }
    }

    foreach ($databases as $database) {
        if (!empty($atividades_config_curso)){
            if(array_search($database->database_id, $atividades_config_curso)){
                $group_array->add($database->course_id, new report_unasus_db_activity($database, $atividades_config_curso));
            }
        } else {
            $group_array->add($database->course_id, new report_unasus_db_activity($database, $atividades_config_curso));
        }
    }

    foreach ($scorms as $scorm) {
        if (!empty($atividades_config_curso)){
            if(array_search($scorm->scorm_id, $atividades_config_curso)){
                $group_array->add($scorm->course_id, new report_unasus_scorm_activity($scorm, $atividades_config_curso));
            }
        } else {
            $group_array->add($scorm->course_id, new report_unasus_scorm_activity($scorm, $atividades_config_curso));
        }
    }

    // Apenas nos relatórios direcionados ao TCC é necessário a apresentação do nome dos capítulos.
    // Nos relatórios de atividades o TCC é tratado apenas como uma atividade.
    if($buscar_lti) {
        process_header_atividades_lti($courses, $group_array);
    }

    if ($mostrar_nota_final) {
        $cursos_com_nota_final = query_courses_com_nota_final($courses);
        foreach ($cursos_com_nota_final as $nota_final) {
            $group_array->add($nota_final->course_id, new report_unasus_final_grade($nota_final));
        }
    }

    if ($mostrar_total) {
        $cursos_com_nota_final = query_courses_com_nota_final($courses);
        foreach ($cursos_com_nota_final as $nota_final) {
            $group_array->add($nota_final->course_id, new report_unasus_total_atividades_concluidas($nota_final));
        }
    }

    $atividades = $group_array->get_assoc();

    if (empty($atividades)) {
        print_error('no_valid_activity_found_error', 'report_unasus');
    }

    return $atividades;
}

/**
 * Atividades LTI
 *
 * @param $courses
 * @param GroupArray $group_array
 * @param bool $is_tcc
 * @return array
 */
function process_header_atividades_lti($courses, GroupArray &$group_array) {
    $ltis = query_lti_courses($courses);

    // Nenhuma atividade lti encontrada,
    // Retornar pois webservice retorna msg de erro e nao deve ser interado no foreach
    if (empty($ltis)) {

        return;
    }

    /* A atividade de LTI é composta (vai gerar sub-atividades para cada eixo */
    foreach ($ltis as $lti) {

        foreach ($lti->tcc_definition->chapter_definitions as $chapter_definition) {

            $chapter_definition = $chapter_definition->chapter_definition;

            // sub-atividade simulada
            $db_model = new stdClass();
            $db_model->id = $lti->id;
            $db_model->course_module_id = $lti->course_module_id;
            $db_model->name = $chapter_definition->title;
            $db_model->completionexpected = $lti->completionexpected;
            $db_model->position = $chapter_definition->position;

            $db_model->course_id = $lti->course_id;
            $db_model->course_name = $lti->course_name;
            $db_model->baseurl = $lti->baseurl;
            $db_model->consumer_key = $lti->config['resourcekey'];
            $db_model->grouping_id = $lti->grouping_id;

            $group_array->add($db_model->course_id, new report_unasus_lti_activity($db_model));
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
function query_assign_courses($courses) {
    global $DB, $SITE;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT a.id AS assign_id,
                     a.name AS assign_name,
                     cm.completionexpected,
                     a.nosubmissions,
                     a.grade,
                     c.id AS course_id,
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
                     cm.groupingid AS grouping_id
                FROM {course} AS c
           LEFT JOIN {assign} AS a
                  ON (c.id = a.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance=a.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'assign')
               WHERE c.id IN ({$string_courses}) AND cm.visible=TRUE
           ORDER BY c.sortorder";

    return $DB->get_recordset_sql($query, array('siteid' => $SITE->id));
}

/**
 * Função que busca os courses com seus respectivos quiz e datas de entrega
 * utilizada no get_atividade_modulos
 *
 * @global moodle_database $DB
 * @param array $courses
 * @return moodle_recordset
 */
function query_quiz_courses($courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT q.id AS quiz_id,
                     q.name AS quiz_name,
                     q.timeopen,
                     cm.completionexpected,
                     q.grade,
                     c.id AS course_id,
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
                     cm.groupingid as grouping_id
                FROM {course} AS c
                JOIN {quiz} AS q
                  ON (c.id = q.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance=q.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'quiz')
               WHERE c.id IN ({$string_courses}) AND cm.visible=TRUE
            ORDER BY c.sortorder";

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

function query_database_courses($courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT d.id AS database_id,
                     d.name AS database_name,
	                 cm.completionexpected,
	                 c.id AS course_id,
	                 REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
	                 cm.groupingid AS grouping_id,
	                 cm.id AS cm_id
                FROM course AS c
           LEFT JOIN data AS d
                  ON (c.id = d.course AND c.id != :siteid)
                JOIN course_modules cm
                  ON (cm.course = c.id)
                JOIN modules m
                  ON (m.id = cm.module AND m.name LIKE 'data')
               WHERE c.id IN ({$string_courses}) AND cm.visible=TRUE
            GROUP BY database_id
            ORDER BY c.sortorder";

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

function query_scorm_courses($courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT s.id AS scorm_id,
                     s.name AS scorm_name,
	                 cm.completionexpected,
	                 c.id AS course_id,
	                 REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
	                 cm.groupingid AS grouping_id,
	                 cm.id AS cm_id
                FROM course AS c
           LEFT JOIN scorm AS s
                  ON (c.id = s.course AND c.id != :siteid)
                JOIN course_modules cm
                  ON (cm.course = c.id)
                JOIN modules m
                  ON (m.id = cm.module AND m.name LIKE 'scorm')
               WHERE c.id IN ({$string_courses}) AND cm.visible=TRUE
            GROUP BY scorm_id
            ORDER BY c.sortorder";

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

/**
 * Função para buscar atividades de lti
 *
 * @param $courses
 * @internal param \type $tcc_definition_id
 * @return array
 */
function query_lti_courses($courses) {
    global $DB;

    if (empty($courses)) {
        return false;
    }

    $courses = is_string($courses) ? explode(',', $courses) : $courses;
    $lti_activities = array();

    foreach ($courses as $course) {

        $ltis = $DB->get_records_sql(query_lti(), array('course' => $course));

        $course_name = $DB->get_field('course', 'fullname', array('id' => $course));

        foreach ($ltis as $lti) {
            $config = $DB->get_records_sql_menu(query_lti_config(), array('typeid' => $lti->typeid));
            $customparameters = get_tcc_definition($config['customparameters']);
            $consumer_key = $config['resourcekey'];

            // WS Client
            $client = new SistemaTccClient($lti->baseurl, $consumer_key);
            $object = $client->get_tcc_definition($customparameters['tcc_definition']);

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

    return $lti_activities;
}
/**
 * Retorna definições da lti
 * @param type $tcc_definition
 * @return array
 */
function get_tcc_definition($tcc_definition) {
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

function query_forum_courses($courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT f.id AS forum_id,
                     f.name AS forum_name,
                     cm.completionexpected,
                     c.id AS course_id,
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name,
                     cm.groupingid as grouping_id
                FROM {course} AS c
           LEFT JOIN {forum} AS f
                  ON (c.id = f.course AND c.id != :siteid)
           LEFT JOIN {grade_items} AS gi
                  ON (gi.courseid=c.id AND gi.itemtype = 'mod' AND
                      gi.itemmodule = 'forum'  AND gi.iteminstance=f.id)
                JOIN {course_modules} cm
                  ON (cm.course=c.id AND cm.instance=f.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'forum')
               WHERE c.id IN ({$string_courses})
                 AND cm.visible=TRUE
                 AND (gi.id=TRUE OR cm.completion != 0)
            ORDER BY c.sortorder";

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

function query_courses_com_nota_final($courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

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

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

/**
 * Verifica se o usuário não enviar uma listagem de modulos obtem todos os modulos válidos (possuem atividade)
 *
 * @param array $modulos
 * @return string
 */
function get_modulos_validos($modulos) {

    $string_modulos = empty($modulos) ? int_array_to_sql(get_id_modulos()) : int_array_to_sql($modulos);
    return $string_modulos;
}

function get_prazo_avaliacao() {
    global $CFG;
    return (int) $CFG->report_unasus_prazo_avaliacao;
}

function get_prazo_maximo_avaliacao() {
    global $CFG;
    return (int) $CFG->report_unasus_prazo_maximo_avaliacao;
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
        $student = new html_table_cell('Estudantes');
        $student->header = true;
        $student->attributes = array('class' => 'title estudante');

        array_shift($coluns);
        array_unshift($coluns, $student);

        $this->head = $coluns;
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
        $student->attributes = array('class' => 'ultima_atividade title estudante');

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
            $module_cell->attributes = array('class' => 'modulo_header');
            $heading1[] = $module_cell;

            foreach ($activities as $activity) {
                $activity_cell = new html_table_cell($activity);
                $activity_cell->header = true;
                $activity_cell->attributes = array('class' => 'rotate');
                /* box */
                if (in_array($count, $ultima_atividade_modulo)) {
                    $activity_cell->attributes = array('class' => 'ultima_atividade rotate');
                }
                $heading2[] = $activity_cell;
                $count++;
            }
        }

        $this->data[] = new html_table_row($heading1);
        $this->data[] = new html_table_row($heading2);
    }

}

class html_table_cell_header extends html_table_cell {

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
class GroupArray {

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
function int_array_to_sql($array) {
    if (!is_array($array)) {
        return $array;
    }
    return implode(',', $array);
}

/**
 * Recupera o curso UFSC a partir do código de curso moodle que originou a visualização do relatório
 *
 * A informação do curso UFSC está armazenada no campo idnumber da categoria principal (nivel 1)
 *
 * @return bool|string
 */
function get_curso_ufsc_id() {
    global $DB;

    $course = $DB->get_record('course', array('id' => get_course_id()), 'category', MUST_EXIST);
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

function get_course_id() {
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

function get_time_interval($data_inicio, $data_fim, $tempo_pulo, $date_format) {
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
function get_time_interval_com_meses($data_inicio, $data_fim, $tempo_pulo, $date_format) {
    $data_inicio = date_create_from_format($date_format, $data_inicio);
    $data_fim = date_create_from_format($date_format, $data_fim);
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
        $meses[$mes][] = $date->format($date_format);
    }

    return $meses;
}

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class date_picker_moodle_form extends moodleform {

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
function date_interval_is_valid($data_inicio, $data_fim) {
    if (date_is_valid($data_inicio) && date_is_valid($data_fim)) {
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
function date_is_valid($str) {
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
function dot_chart_com_tutores_com_acesso($dados) {
    foreach ($dados as $tutor) {
        foreach ($tutor as $dia) {
            if ($dia[0] != 0)
                return true;
        }
    }
    return false;
}

function get_atividades($nome_atividade, $atividade, $courseid, $grupo, $report, $is_boletim = false)
{

    global $DB;

    $relationship = grupos_tutoria::get_relationship_tutoria($report->get_categoria_turma_ufsc());
    $cohort_estudantes = grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

    switch ($nome_atividade) {
        case 'report_unasus_assign_activity':
            $params = array(
                'courseid' => $courseid,
                'enrol_courseid' => $courseid,
                'assignmentid' => $atividade->id,
                'assignmentid2' => $atividade->id,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id);
            $query = query_atividades();
            break;
        case 'report_unasus_forum_activity':
            $params = array(
                'courseid' => $courseid,
                'enrol_courseid' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
                'forumid' => $atividade->id);
            $query = query_postagens_forum();
            break;
        case 'report_unasus_quiz_activity':
            $params = array(
                'assignmentid' => $atividade->id,
                'assignmentid2' => $atividade->id,
                'courseid' => $courseid,
                'enrol_courseid' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
                'forumid' => $atividade->id);
            $query = query_quiz();
            break;
        case 'report_unasus_db_activity':
            $params = array(
                'id_activity' => $atividade->id,
                'courseid' => $courseid,
                'enrol_courseid' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
                'coursemoduleid' => $atividade->cm_id
            );
            $query = query_database_adjusted();
            break;
        case 'report_unasus_scorm_activity':
            $params = array(
                'id_activity' => $atividade->id,
                'courseid' => $courseid,
                'enrol_courseid' => $courseid,
                'relationship_id' => $relationship->id,
                'cohort_relationship_id' => $cohort_estudantes->id,
                'grupo' => $grupo->id,
            );
            $query = query_scorm();
            break;
        default:
            if ($is_boletim) { //Nota final para relatório boletim
                $params = array(
                    'courseid' => $courseid,
                    'enrol_courseid' => $courseid,
                    'relationship_id' => $relationship->id,
                    'cohort_relationship_id' => $cohort_estudantes->id,
                    'grupo' => $grupo->id);
                $query = query_nota_final();
                break;
            }
            break;
    }

    return $DB->get_records_sql($query, $params);
}

function get_activities_config_report($categoryid, $courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT *
                FROM {activities_course_config} AS config
               WHERE config.courseid IN ({$string_courses}) AND config.categoryid = :categoryid
           ";

    return $DB->get_records_sql_menu($query, array('categoryid' => $categoryid));
}