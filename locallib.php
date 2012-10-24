<?php

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->dirroot}/{$CFG->admin}/tool/tutores/middlewarelib.php");
require_once("$CFG->dirroot/$CFG->admin/tool/tutores/lib.php");
require_once($CFG->dirroot . '/report/unasus/datastructures.php');
require_once($CFG->dirroot . '/report/unasus/dados.php');
require_once($CFG->dirroot . '/report/unasus/dados/dados_atividades_vs_notas.php');

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
          "SELECT DISTINCT(REPLACE(fullname, CONCAT(shortname, ' - '), '')) as fullname
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
          "SELECT distinct CONCAT(firstname,' ',lastname) as fullname
             FROM {role_assignments} as ra
             JOIN {role} as r
               ON (r.id=ra.roleid)
             JOIN {context} as c
               ON (c.id=ra.contextid)
             JOIN {user} as u
               ON (u.id=ra.userid)
            WHERE c.contextlevel=40;");
    return array_keys($tutores);
}

function get_count_estudantes() {
    global $DB;
    $estudantes = $DB->get_records_sql("
          SELECT COUNT(distinct u.id)
            FROM {role_assignments} as ra
            JOIN {role} as r
              ON (r.id=ra.roleid)
            JOIN {context} as c
              ON (c.id=ra.contextid)
            JOIN {user} as u
              ON (u.id=ra.userid)
           WHERE c.contextlevel=50;");
    $value = array_keys($estudantes);
    return $value[0];
}

/**
 * Dado que alimenta a lista do filtro polos
 *
 * @return array(Strings)
 */
function get_polos() {
    $academico = Middleware::singleton();
    $polos = $academico->get_records_sql_menu("
          SELECT DISTINCT(polo), nomepolo
            FROM {View_Usuarios_Dados_Adicionais}
           WHERE nomepolo != ''
        ORDER BY nomepolo");

    return $polos;
}

function get_id_nome_modulos() {
    global $DB, $SITE;
    $modulos = $DB->get_records_sql_menu(
          "SELECT DISTINCT(c.id),
                REPLACE(fullname, CONCAT(shortname, ' - '), '') as fullname
           FROM {course} c
           JOIN {assign} a
             ON (c.id = a.course)
          WHERE c.id != :siteid
            AND c.visible=true", array('siteid' => $SITE->id));
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
            AND c.visible=true", array('siteid' => $SITE->id));
    return array_keys($modulos);
}

function get_id_nome_atividades() {
    global $DB;
    $modulos = $DB->get_records_sql_menu(
          "SELECT a.id,
                a.name
           FROM {assign} a");
    return $modulos;
}

/**
 * Dado que alimenta a lista do filtro tutores
 *
 * @return array(Strings)
 */
function get_tutores_menu($curso_ufsc) {
    $middleware = Middleware::singleton();

    $sql = "SELECT DISTINCT u.id, CONCAT(firstname,' ',lastname) as fullname
              FROM {user} u
              JOIN {table_PessoasGruposTutoria} pg
                ON (pg.matricula=u.username AND pg.tipo=:tipo)
              JOIN {table_GruposTutoria} gt
                ON (gt.id=pg.grupo AND gt.curso=:curso_ufsc)";

    $params = array('curso_ufsc' => $curso_ufsc, 'tipo' => GRUPO_TUTORIA_TIPO_TUTOR);
    return $middleware->get_records_sql_menu($sql, $params);
}

/**
 * Função que busca todas as atividades (assign) dentro de um modulo (course)
 *
 * @param array $modulos array de ids dos modulos, padrão null, retornando todos os modulos
 * @return GroupArray array(course_id => (assign_id1,assign_name1),(assign_id2,assign_name2)...)
 */
function get_atividades_modulos($modulos = null) {
    $atividades_modulos = query_atividades_modulos($modulos);
    $group_array = new GroupArray();

    foreach ($atividades_modulos as $atividade) {
        $group_array->add($atividade->course_id, $atividade);
    }

    return $group_array->get_assoc();
}

/**
 * Função que busca os courses com suas respectivas atividades e datas de entrega
 * utilizada no get_atividade_modulos
 *
 * @global moodle_database $DB
 * @param array $modulos
 * @return moodle_recordset
 */
function query_atividades_modulos($modulos) {
    global $DB, $SITE;

    $string_modulos = get_modulos_validos($modulos);

    $query = "SELECT a.id as assign_id,
                         a.duedate,
                         a.name as assign_name,
                         c.id as course_id,
                         REPLACE(c.fullname, CONCAT(shortname, ' - '), '') as course_name
                    FROM {course} as c
               LEFT JOIN {assign} as a
                      ON (c.id = a.course)
                   WHERE c.id != :siteid
                     AND c.id IN ({$string_modulos})
               ORDER BY c.id";

    return $DB->get_recordset_sql($query, array('siteid' => $SITE->id));
}

/**
 * Verifica se o usuário não enviar uma listagem de modulos obtem todos os modulos válidos (possuem atividade)
 *
 * @param array $modulos
 * @return array
 */
function get_modulos_validos($modulos) {
    $string_modulos;
    if ($modulos) {
        $string_modulos = int_array_to_sql($modulos);
    } else {
        $string_modulos = int_array_to_sql(get_id_modulos());
    }
    return $string_modulos;
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
 * @return bool|string
 */
function get_curso_ufsc_id() {
    global $DB;

    $course = $DB->get_record('course', array('id' => get_course_id()), 'category', MUST_EXIST);
    $category = $DB->get_record('course_categories', array('id' => $course->category), 'idnumber', MUST_EXIST);
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