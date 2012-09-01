<?php

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
    $modulos = array();
    for ($i =1; $i <= 20; $i++) {
        $modulos[] = "Módulo {$i}";
    }
    return $modulos;
}

/**
 * Dado que alimenta a lista do filtro tutores
 *
 * @return array(Strings)
 */
function get_nomes_tutores() {
    $tutores = array();
    for ($i = 1; $i <= 50; $i++) {
        $tutores[] = "Tutor João da Silva {$i}";
    }

    return $tutores;
}

/**
 * Dado que alimenta a lista do filtro polos
 *
 * @return array(Strings)
 */
function get_nomes_polos() {
    $polos = array();
    for ($i = 1; $i <= 40; $i++) {
        $polos[] = "Polo Nome da Cidade-UN {$i}";
    }
    return $polos;
}

/**
 * Classe que constroi a tabela para os relatorios, extende a html_table
 * da MoodleAPI.
 *
 */
class report_unasus_table extends html_table {

    function build_single_header($coluns) {
        $this->head = $coluns;
    }

    function build_double_header($grouped_coluns, $person_name='Estudantes') {

        $this->data = array();
        $blank = new html_table_cell();
        $blank->attributes = array('class' => 'blank');
        $student = new html_table_cell($person_name);
        $student->header = true;
        $student->attributes = array('class'=>'ultima_atividade');

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
                /*box*/
                if (in_array($count, $ultima_atividade_modulo)) {
                   $activity_cell->attributes = array('class'=>'ultima_atividade');
                }
                $heading2[] = $activity_cell;
                $count++;
            }
        }

        $this->data[] = new html_table_row($heading1);
        $this->data[] = new html_table_row($heading2);
    }

}