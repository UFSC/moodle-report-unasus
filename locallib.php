<?php

defined('MOODLE_INTERNAL') || die;

define('REPORT_UNASUS_COHORT_EMPTY', 'Sem cohort'); // estudantes sem cohort

require_once("{$CFG->dirroot}/local/tutores/middlewarelib.php");
require_once("{$CFG->dirroot}/local/tutores/lib.php");
require_once($CFG->dirroot . '/report/unasus/datastructures.php');
require_once($CFG->dirroot . '/report/unasus/activities_datastructures.php');
require_once($CFG->dirroot . '/report/unasus/relatorios/relatorios.php');

function get_datetime_from_unixtime($unixtime) {
    return date_create(date("Y-m-d H:m:s", $unixtime));
}

/**
 * Função para capturar um formulario do moodle e pegar sua string geradora
 * já que a unica função para um moodleform é o display que printa automaticamente
 * o form, sem possuir um metodo tostring()
 *
 * @param moodleform $mform Formulario do Moodle
 * @return string
 */
function get_form_display(&$mform) {
    ob_start();
    $mform->display();
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
}

function get_nomes_modulos() {
    global $DB, $SITE;
    $modulos = $DB->get_records_sql(
        "SELECT DISTINCT(REPLACE(fullname, CONCAT(shortname, ' - '), '')) AS fullname
           FROM {course} c
           JOIN {assign} a
             ON (c.id = a.course)
          WHERE c.id != :siteid");
    return array_keys($modulos, array('siteid' => $SITE->id));
}

/**
 * Dado que alimenta a lista do filtro tutores
 *
 * @deprecated
 * @return array(Strings)
 */
function get_nomes_tutores() {
    global $DB;
    $tutores = $DB->get_records_sql(
        "SELECT DISTINCT CONCAT(firstname,' ',lastname) AS fullname
           FROM {role_assignments} AS ra
           JOIN {role} AS r
             ON (r.id=ra.roleid)
           JOIN {context} AS c
             ON (c.id=ra.contextid)
           JOIN {user} AS u
             ON (u.id=ra.userid)
          WHERE c.contextlevel=40;");
    return array_keys($tutores);
}

function get_count_estudantes($curso_ufsc) {
    $middleware = Middleware::singleton();

    $query = "SELECT pg.grupo AS grupo_id, COUNT(DISTINCT pg.matricula)
                FROM {table_PessoasGruposTutoria} pg
                JOIN {table_GruposTutoria} gt
                  ON (gt.id=pg.grupo)
               WHERE gt.curso=:curso_ufsc AND pg.tipo=:tipo_aluno
               GROUP BY pg.grupo";
    $params = array('tipo_aluno' => GRUPO_TUTORIA_TIPO_ESTUDANTE, 'curso_ufsc' => $curso_ufsc);

    $result = $middleware->get_records_sql_menu($query, $params);

    foreach ($result as $key => $value) {
        $result[$key] = (int) $value;
    }

    return $result;
}

/**
 * Dado que alimenta a lista do filtro cohort
 *
 * @param type $curso_ufsc
 * @return array (nome dos cohorts)
 */
function get_nomes_cohorts($curso_ufsc) {
    global $DB;

    $ufsc_category_sql = "
        SELECT cc.id 
          FROM {course_categories} cc 
         WHERE cc.idnumber=:curso_ufsc";

    $ufsc_category = $DB->get_field_sql($ufsc_category_sql, array('curso_ufsc' => "curso_{$curso_ufsc}"));

    $modulos = $DB->get_records_sql_menu(
        "SELECT DISTINCT(cohort.id), cohort.name
           FROM {cohort} cohort
           JOIN {context} ctx
             ON (cohort.contextid = ctx.id AND ctx.contextlevel = 40)
           JOIN {course_categories} cc
             ON (ctx.instanceid = cc.id AND (cc.idnumber = :curso_ufsc OR cc.path LIKE '/{$ufsc_category}/%'))",
        array('curso_ufsc' => "curso_{$curso_ufsc}"));
    return $modulos;
}

/**
 * Dado que alimenta a lista do filtro polos
 *
 * @param $curso_ufsc
 * @return array
 */
function get_polos($curso_ufsc) {
    $academico = Middleware::singleton();
    $sql = "
          SELECT DISTINCT(u.polo), u.nomepolo
            FROM {View_Usuarios_Dados_Adicionais} u
            JOIN {table_PessoasGruposTutoria} pg
              ON (pg.matricula=u.username)
            JOIN {table_GruposTutoria} gt
              ON (gt.id=pg.grupo)
           WHERE gt.curso=:curso_ufsc
             AND pg.tipo=:tipo
             AND nomepolo != ''
        ORDER BY nomepolo";

    $params = array('curso_ufsc' => $curso_ufsc, 'tipo' => GRUPO_TUTORIA_TIPO_ESTUDANTE);
    $polos = $academico->get_records_sql_menu($sql, $params);

    return $polos;
}

function get_id_nome_modulos($curso_ufsc) {
    global $DB, $SITE;

    $modulos = $DB->get_records_sql_menu(
        "SELECT DISTINCT(c.id),
                REPLACE(fullname, CONCAT(shortname, ' - '), '') AS fullname
           FROM {course} c
           JOIN {course_categories} cc
             ON ( (c.category = cc.id OR cc.path LIKE CONCAT('/', c.category, '/%')) AND cc.idnumber = :curso_ufsc)
           JOIN {assign} a
             ON (c.id = a.course)
          WHERE c.id != :siteid
            AND c.visible=TRUE", array('siteid' => $SITE->id, 'curso_ufsc' => "curso_{$curso_ufsc}"));
    return $modulos;
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
 * Dado que alimenta a lista do filtro tutores
 *
 * @param $curso_ufsc
 * @return array
 */
function get_tutores_menu($curso_ufsc) {
    $middleware = Middleware::singleton();

    $sql = "SELECT DISTINCT u.id, CONCAT(firstname,' ',lastname) AS fullname
              FROM {USER} u
              JOIN {table_PessoasGruposTutoria} pg
                ON (pg.matricula=u.username AND pg.tipo=:tipo)
              JOIN {table_GruposTutoria} gt
                ON (gt.id=pg.grupo AND gt.curso=:curso_ufsc)";

    $params = array('curso_ufsc' => $curso_ufsc, 'tipo' => GRUPO_TUTORIA_TIPO_TUTOR);
    return $middleware->get_records_sql_menu($sql, $params);
}

/**
 * Função que busca todas as atividades (assign, forum) dentro de um modulo (course)
 *
 * @param array $courses array de ids dos cursos moodle, padrão null, retornando todos os modulos
 * @param bool $mostrar_nota_final
 * @return GroupArray array(course_id => (assign_id1,assign_name1),(assign_id2,assign_name2)...)
 */
function get_atividades_cursos($courses = null, $mostrar_nota_final = false) {
    $assigns = query_assign_courses($courses);
    $foruns = query_forum_courses($courses);
    $quizes = query_quiz_courses($courses);

    $group_array = new GroupArray();

    foreach ($assigns as $atividade) {
        $group_array->add($atividade->course_id, new report_unasus_assign_activity($atividade));
    }

    foreach ($foruns as $forum) {
        $group_array->add($forum->course_id, new report_unasus_forum_activity($forum));
    }

    foreach ($quizes as $quiz) {
        $group_array->add($quiz->course_id, new report_unasus_quiz_activity($quiz));
    }

    if ($mostrar_nota_final) {
        $cursos_com_nota_final = query_courses_com_nota_final($courses);
        foreach ($cursos_com_nota_final as $nota_final) {
            $group_array->add($nota_final->course_id, new report_unasus_final_grade($nota_final));
        }
    }

    return $group_array->get_assoc();
}

/**
 * Função que busca os courses com suas respectivas atividades e datas de entrega
 * utilizada no get_atividade_modulos
 *
 * @global moodle_database $DB
 * @param array $courses
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
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name
                FROM {course} AS c
           LEFT JOIN {assign} AS a
                  ON (c.id = a.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance=a.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'assign')
               WHERE c.id IN ({$string_courses})
           ORDER BY c.id";

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
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name
                FROM {course} AS c
                JOIN {quiz} AS q
                  ON (c.id = q.course AND c.id != :siteid)
                JOIN {course_modules} cm
                  ON (cm.course = c.id AND cm.instance=q.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'quiz')
               WHERE c.id IN ({$string_courses})
            ORDER BY c.id";

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

function query_forum_courses($courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT f.id AS forum_id,
                     f.name AS forum_name,
                     cm.completionexpected,
                     c.id AS course_id,
                     REPLACE(c.fullname, CONCAT(shortname, ' - '), '') AS course_name
                FROM {course} AS c
           LEFT JOIN {forum} AS f
                  ON (c.id = f.course AND c.id != :siteid)
                JOIN {grade_items} AS gi
                  ON (gi.courseid=c.id AND gi.itemtype = 'mod' AND
                      gi.itemmodule = 'forum'  AND gi.iteminstance=f.id)
                JOIN {course_modules} cm
                  ON (cm.course=c.id AND cm.instance=f.id)
                JOIN {modules} m
                  ON (m.id = cm.module AND m.name LIKE 'forum')
               WHERE c.id IN ({$string_courses})
            ORDER BY c.id";

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

function query_courses_com_nota_final($courses) {
    global $DB;

    $string_courses = get_modulos_validos($courses);

    $query = "SELECT gi.id,
                     gi.courseid AS course_id,
                     gi.itemname
                FROM {grade_items} gi
               WHERE (gi.itemtype LIKE 'course' AND itemmodule IS NULL AND gi.courseid IN ({$string_courses}))
            ORDER BY gi.id";

    return $DB->get_recordset_sql($query, array('siteid' => SITEID));
}

/**
 * Verifica se o usuário não enviar uma listagem de modulos obtem todos os modulos válidos (possuem atividade)
 *
 * @param array $modulos
 * @return array
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
        $student->attributes = array('class' => 'ultima_atividade');

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
                /* box */
                if (in_array($count, $ultima_atividade_modulo)) {
                    $activity_cell->attributes = array('class' => 'ultima_atividade');
                }
                $heading2[] = $activity_cell;
                $count++;
            }
        }

        $this->data[] = new html_table_row($heading1);
        $this->data[] = new html_table_row($heading2);
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

function get_cursos_ativos_list() {
    $middleware = Middleware::singleton();
    $sql = "SELECT curso, nome_sintetico FROM {View_Cursos_Ativos}";
    return $middleware->get_records_sql_menu($sql);
}

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
