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

/**
 * Dado que alimenta a lista do filtro tutores
 * 
 * @return array(Strings) 
 */
function get_nomes_tutores() {
    return array("joao", "maria", "ana");
}

/**
 * Dado que alimenta a lista do filtro polos
 * 
 * @return array(Strings) 
 */
function get_nomes_polos() {
    return array("joinville", "blumenau", "xapecó");
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

    function build_double_header($grouped_coluns) {

        $this->data = array();
        $blank = new html_table_cell();
        $blank->attributes = array('class' => 'blank');
        $student = new html_table_cell('Estudante');
        $student->header = true;

        $heading1 = array(); // Primeira linha
        $heading1[] = $blank; // Acrescenta uma célula em branco na primeira linha

        $heading2 = array(); // Segunda linha
        $heading2[] = $student;

        foreach ($grouped_coluns as $module_name => $activities) {
            $module_cell = new html_table_cell($module_name);
            $module_cell->header = true;
            $module_cell->colspan = count($activities);

            $heading1[] = $module_cell;
            foreach ($activities as $activity) {
                $activity_cell = new html_table_cell($activity);
                $activity_cell->header = true;
                $heading2[] = $activity_cell;
            }
        }

        $this->data[] = new html_table_row($heading1);
        $this->data[] = new html_table_row($heading2);
    }

}

abstract class unasus_data {
    public abstract function get_css_class();
}

class dado_atividade_vs_nota extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_ATRASADA = 1;
    const ATIVIDADE_AVALIADA = 2;
    const ATIVIDADE_NO_PRAZO_ENTREGA = 3;

    var $tipo;
    var $nota;
    var $atraso;

    function __construct($tipo, $nota = 0, $atraso = 0) {

        $this->tipo = $tipo;
        $this->nota = $nota;
        $this->atraso = $atraso;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_atividade_vs_nota::ATIVIDADE_NAO_ENTREGUE:
                return 'Atividade não Entregue';
                break;
            case dado_atividade_vs_nota::CORRECAO_ATRASADA:
                return "$this->atraso dias";
                break;
            case dado_atividade_vs_nota::ATIVIDADE_AVALIADA:
                return (String)$this->nota;
                break;
            case dado_atividade_vs_nota::ATIVIDADE_NO_PRAZO_ENTREGA:
                return 'No prazo';
                break;
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_atividade_vs_nota::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
            case dado_atividade_vs_nota::CORRECAO_ATRASADA:
                return ($this->atraso > 2) ? 'muito_atraso' : 'pouco_atraso';
            case dado_atividade_vs_nota::ATIVIDADE_AVALIADA:
                return 'nota_atribuida';
            case dado_atividade_vs_nota::ATIVIDADE_NO_PRAZO_ENTREGA:
                return 'nao_realizada';
            default:
                return '';
        }
    }

}

class dado_entrega_atividade extends unasus_data {
    const ATIVIDADE_NAO_ENTREGUE = 0;
    const ATIVIDADE_ENTREGUE_NO_PRAZO = 1;
    const ATIVIDADE_ENTREGUE_FORA_DO_PRAZO = 2;

    var $tipo;
    var $atraso;

    function __construct($tipo, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_entrega_atividade::ATIVIDADE_NAO_ENTREGUE:
                return '';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_NO_PRAZO:
                return '';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO:
                return "$this->atraso dias";
                break;
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_entrega_atividade::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_NO_PRAZO:
                return 'no_prazo';
                break;
            case dado_entrega_atividade::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO:
                return ($this->atraso > 2) ? 'muito_atraso' : 'pouco_atraso';
                break;
        }
    }

}

class dado_acompanhamento_avaliacao extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_NO_PRAZO = 1;
    const CORRECAO_ATRASADA = 2;

    var $tipo;
    var $atraso;

    function __construct($tipo, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
    }

    public function to_string() {
        switch ($this->tipo) {
            case dado_acompanhamento_avaliacao::ATIVIDADE_NAO_ENTREGUE:
                return '';
                break;
            default: 
                return "$this->atraso dias";
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_acompanhamento_avaliacao::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
                break;
            case dado_acompanhamento_avaliacao::CORRECAO_NO_PRAZO:
                return 'no_prazo';
                break;
            case dado_acompanhamento_avaliacao::CORRECAO_ATRASADA:
                return ($this->atraso > 7) ? 'muito_atraso' : 'pouco_atraso';
                break;
        }
    }

}
