<?php

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][avaliacao]
 */
function get_dados_dos_alunos() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array("Fulano de Tal {$i}",
                avaliacao_aleatoria(),
                avaliacao_aleatoria(),
                avaliacao_aleatoria(true));
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

/**
 * Utilizado para gerar uma avalização aleatórioa para finalidade de testes.
 * @return Avaliacao avaliacao aleatória para os relatórios
 */
function avaliacao_aleatoria($no_prazo = false) {
    $random = rand(0, 100);

    if ($random <= 65) { // Avaliada
        return new Avaliacao(Avaliacao::AVALIADA, rand(0, 10));
    } elseif ($random > 65 && $random <= 85) { // Avaliação atrasada
        return new Avaliacao(Avaliacao::ATRASADA, null, rand(0, 20));
    } elseif ($random > 85) { // Não entregue
        return $no_prazo ? new Avaliacao(Avaliacao::NO_PRAZO) : new Avaliacao(Avaliacao::NAO_ENTREGUE);
    }
}


function get_dados_entrega_atividades(){
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array("Fulano de Tal {$i}",
                atividade_aleatoria(),
                atividade_aleatoria(),
                atividade_aleatoria(true));
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

function atividade_aleatoria($no_prazo = false) {
    $random = rand(0, 100);

    if ($random <= 65) { // Avaliada
        return new Avaliacao(Avaliacao::AVALIADA);
    } elseif ($random > 65 && $random <= 85) { // Avaliação atrasada
        return new Avaliacao(Avaliacao::ATRASADA, null, rand(0, 20));
    } elseif ($random > 85) { // Não entregue
        return $no_prazo ? new Avaliacao(Avaliacao::NO_PRAZO) : new Avaliacao(Avaliacao::NAO_ENTREGUE);
    }
}

function get_nomes_tutores() {
    return array("joao", "maria", "ana");
}

function get_nomes_polos() {
    return array("joinville", "blumenau", "xapecó");
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

class Avaliacao {

    const NAO_ENTREGUE = 0;
    const ATRASADA = 1;
    const AVALIADA = 2;
    const NO_PRAZO = 3;

    var $tipo;
    var $nota;
    var $atraso;

    function __construct($tipo, $nota = 0, $atraso = 0) {

        $this->tipo = $tipo;
        $this->nota = $nota;
        $this->atraso = $atraso;
    }

    public function to_string() {
        switch ($this->tipo) {
            case Avaliacao::NAO_ENTREGUE:
                return "Atividade não Entregue";
                break;
            case Avaliacao::ATRASADA:
                return $this->atraso . " dias";
                break;
            case Avaliacao::AVALIADA:
                return $this->nota;
                break;
            case Avaliacao::NO_PRAZO:
                return "No prazo";
                break;
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case Avaliacao::NAO_ENTREGUE:
                return 'nao_entregue';
            case Avaliacao::ATRASADA:
                return ($this->atraso > 2) ? "alto_atraso" : "baixo_atraso";
            case Avaliacao::NO_PRAZO:
                return 'no_prazo';
            case Avaliacao::AVALIADA:
                return 'avaliada';
            default:
                return '';
        }
    }

}

class Tutor {
    var $nome;

    function __construct($nome) {
        $this->nome = $nome;
    }

    function to_string() {
        return $this->nome;
    }

}

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
