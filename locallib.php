<?php

require_once("{$CFG->dirroot}/{$CFG->admin}/tool/tutores/middlewarelib.php");
require_once($CFG->dirroot . '/report/unasus/datastructures.php');
require_once($CFG->dirroot . '/report/unasus/dados.php');

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
    global $DB;
    $query = $DB->get_records_sql(
          "SELECT REPLACE(fullname, CONCAT(shortname, ' - '), '') as fullname FROM {course} c WHERE c.id != 1");
    return array_keys($query);
}

/**
 * Dado que alimenta a lista do filtro tutores
 *
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

function get_nomes_estudantes() {
    global $DB;

    $estudantes = $DB->get_recordset_sql("
          SELECT distinct u.id, CONCAT(firstname,' ', REPLACE(lastname, CONCAT('(', username, ')'), '')) as fullname
            FROM {role_assignments} as ra
            JOIN {role} as r
              ON (r.id=ra.roleid)
            JOIN {context} as c
              ON (c.id=ra.contextid)
            JOIN {user} as u
              ON (u.id=ra.userid)
           WHERE c.contextlevel=50;");
    return $estudantes;
}

/**
 * Dado que alimenta a lista do filtro polos
 *
 * @return array(Strings)
 */
function get_nomes_polos() {
    $academico = Academico::singleton();
    $polos = $academico->db->get_records_sql_menu("
          SELECT DISTINCT(nomepolo)
            FROM {$academico->view_usuarios_dados_adicionais}
           WHERE nomepolo != ''
        ORDER BY nomepolo");

    return array_keys($polos);
}

/**
 * Função que busca todas as atividades (assign) dentro de um modulo (course)
 *
 * @param array $modulos array de ids dos modulos, padrão null, retornando todos os modulos
 * @return GroupArray array(course_id => (assign_id1,assign_name1),(assign_id2,assign_name2)...)
 */
function get_atividades_modulos($modulos = null) {
    global $DB;
    $query =     "SELECT a.id as assign_id, a.name as assign_name, c.fullname as course_name, c.id as course_id
                    FROM {course} as c
                    JOIN {assign} as a
                      ON (c.id = a.course)";
    if($modulos){
        $string_modulos = int_array_to_sql($modulos);
        $query .= "WHERE c.id IN ({$string_modulos})";
    }
    $query .=     "ORDER BY c.id";

    $atividades_modulos = $DB->get_recordset_sql($query);



    $group_array = new GroupArray();
    foreach ($atividades_modulos as $atividade){
        $group_array->add($atividade->course_id, new stdClass($atividade->assign_name, $atividade->assign_id));
    }
    return $group_array;
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
function int_array_to_sql($array){
    if(!is_array($array)){
        return $array;
    }
    return implode(',', $array);
}

///
/// Funcionalidades semelhantes duplicadas de tool tutores
/// TODO: refatorar e deduplicar as funcinoalidades abaixo de forma que ambas ferramentas disponibilizem uma única API.
///

function get_cursos_ativos_list() {
    $middleware = Academico::singleton();
    $sql = "SELECT curso, nome_sintetico FROM {$middleware->view_cursos_ativos}";
    return $middleware->db->get_records_sql_menu($sql);
}

function get_curso_ufsc_id() {
    return optional_param('curso_ufsc', null, PARAM_INT);
}