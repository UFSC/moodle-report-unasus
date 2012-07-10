<?php

//
// Pessoas
//

/**
 * Estrutura de dados de Pessoas (Tutores, Estudantes)
 * Auxilia renderização nos relatórios.
 */
abstract class pessoa {

    protected $name;
    protected $id;

    function __construct($name) {
        $this->name = $name;
        $this->id = 1; // mock id - visitante
    }

}

/**
 * Representa um estudante nos relatórios
 */
class estudante extends pessoa {

    function __toString() {
        global $OUTPUT;
        $email_icon = $OUTPUT->pix_icon('t/email', 'Enviar mensagem');
        $profile_link = html_writer::link(new moodle_url('/user/profile.php', array('id' => $this->id)), $this->name);
        $message_link = html_writer::link(new moodle_url('/message/index.php', array('id' => $this->id)), $email_icon);

        return "{$profile_link} {$message_link}";
    }

}

/**
 * Representa um tutor nos relatórios
 */
class tutor extends pessoa {

    function __toString() {
        return $this->name;
    }

}

//
// Relatórios
//

/**
 * Estrutura para auxiliar a renderização dos dados dos relatórios
 */
abstract class unasus_data {
    protected $header = false;

    public abstract function get_css_class();

    public function is_header(){
        return $header;
    }
}

class dado_atividade_vs_nota extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_ATRASADA = 1;
    const ATIVIDADE_AVALIADA = 2;
    const ATIVIDADE_NO_PRAZO_ENTREGA = 3;

    private $tipo;
    private $nota;
    private $atraso;

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
                return (String) $this->nota;
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

    private $tipo;
    private $atraso;

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

    private $tipo;
    private $atraso;

    function __construct($tipo, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
    }

    public function __toString() {
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

class dado_atividades_nao_avaliadas extends unasus_data {   
    private $taxa;

    function __construct($taxa) {
        $this->taxa = $taxa;
    }

    public function __toString() {
        return "{$this->taxa}%";
    }

    public function get_css_class() {
        return '';
    }

}

/**
 *  @TODO media deve se auto-calcular.
 */
class dado_media extends unasus_data{
    private $media;
    
    function __construct($media) {
        $this->media = $media;
    }

    public function __toString() {
        return "{$this->media}%";
    }

    public function get_css_class() {
        return 'media';
    }
    
}