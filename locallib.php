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

/**
 * Dado que alimenta a lista do filtro tutores
 *
 * @return array(Strings)
 */
function get_tutores() {
    global $DB;
    $tutores = $DB->get_recordset_sql(
        "SELECT distinct u.id, CONCAT(firstname,' ',lastname) as fullname
             FROM {role_assignments} as ra
             JOIN {role} as r
               ON (r.id=ra.roleid)
             JOIN {context} as c
               ON (c.id=ra.contextid)
             JOIN {user} as u
               ON (u.id=ra.userid)
            WHERE c.contextlevel=40;");
    return $tutores;
}

function get_nomes_estudantes(){
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
