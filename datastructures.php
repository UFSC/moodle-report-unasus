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

    function __toString() {
        global $OUTPUT;
        $email_icon = $OUTPUT->pix_icon('t/email', 'Enviar mensagem');
        $profile_link = html_writer::link(new moodle_url('/user/profile.php', array('id' => $this->id)), $this->name, array('target' => '_blank'));
        $message_link = html_writer::link(new moodle_url('/message/index.php', array('id' => $this->id)), $email_icon, array('target' => '_blank'));

        return "{$profile_link} {$message_link}";
    }

}

/**
 * Representa um estudante nos relatórios
 */
class estudante extends pessoa {

}

/**
 * Representa um tutor nos relatórios
 */
class tutor extends pessoa {

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

    //public static abstract function get_legend();

    public function is_header() {
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

    public static function get_legend() {
        $legend = array();
        $legend['nota_atribuida'] = 'Nota atribuída';
        $legend['pouco_atraso'] = 'Sem nota atribuída, dentro do prazo (até X dias após data de entrega)';
        $legend['muito_atraso'] = 'Sem nota atribuída, fora do prazo (após X dias da data de entrega)';
        $legend['nao_entregue'] = 'Atividade não realizada, após data esperada';
        $legend['nao_realizada'] = 'Atividade não realizada, mas dentro da data esperada';

        return $legend;
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

    public static function get_legend() {
        $legend = array();
        $legend['nao_entregue'] = 'Em aberto (não entregue)';
        $legend['no_prazo'] = 'Atividade entregue em dia';
        $legend['pouco_atraso'] = 'Atividade entregue, com atraso de até X dias';
        $legend['muito_atraso'] = 'Atividade entregue, com atraso de mais de X dias';

        return $legend;
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
                if ($this->atraso == 0) {
                    return 'mesmo dia';
                } elseif ($this->atraso == 1) {
                    return '1 dia';
                } else {
                    return "{$this->atraso} dias";
                }
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

    public static function get_legend() {
        $legend = array();
        $legend['nao_entregue'] = 'Em aberto (atividade não entregue pelo estudante)';
        $legend['no_prazo'] = 'Avaliadas dentro do prazo';
        $legend['pouco_atraso'] = 'Avaliadas fora do prazo, em até X dias';
        $legend['muito_atraso'] = 'Avaliadas for a do prazo, após X dias';

        return $legend;
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

    public static function get_legend(){
        return false;
    }

}

/**
 *  @TODO media deve se auto-calcular.
 */
class dado_media extends unasus_data {

    private $media;

    function __construct($media) {
        $this->media = $media;
    }

    public function __toString() {
        return "{$this->media}%";
    }

    public function value() {
        return "{$this->media}";
    }

    public function get_css_class() {
        return 'media';
    }

}

/**
 * @TODO somatorio deve se auto-calcular
 *
 */
class dado_somatorio extends unasus_data {

    private $sum;

    function __construct($sum) {
        $this->sum = $sum;
    }

    public function __toString() {
        return "{$this->sum}";
    }

    public function get_css_class() {
        return 'somatorio';
    }

}

/**
 * @TODO unir o dado_acesso com dado_tempo_acesso??
 */
class dado_acesso extends unasus_data {

    private $acesso;

    function __construct($acesso = true) {
        $this->acesso = $acesso;
    }

    public function __toString() {
        return ($this->acesso) ? 'Sim' : 'Não';
    }

    public function get_css_class() {
        return ($this->acesso) ? 'acessou' : 'nao_acessou';
    }

    public static function get_legend(){
        $legend = array();
        $legend['acessou'] = 'Tutor acessou o sistema nesta data';
        $legend['nao_acessou'] = 'Tutor não acessou o sistema nesta data';

        return $legend;
    }

}

class dado_tempo_acesso extends unasus_data {

    private $acesso;

    function __construct($acesso) {
        $this->acesso = $acesso;
    }

    public function __toString() {
        return "{$this->acesso}";
    }

    public function get_css_class() {
        return ($this->acesso) ? 'acessou' : 'nao_acessou';
    }

    public static function get_legend(){
        $legend = array();
        $legend['acessou'] = 'Tutor acessou o sistema nesta data por X horas';
        $legend['nao_acessou'] = 'Tutor não acessou o sistema nesta data';

        return $legend;
    }

}

class dado_potencial_evasao extends unasus_data {

    const MODULO_NAO_CONCLUIDO = 0;
    const MODULO_CONCLUIDO = 1;
    const MODULO_PARCIALMENTE_CONCLUIDO = 2;

    private $estado;

    function __construct($estado) {
        $this->estado = $estado;
    }

    public function __toString() {
        switch ($this->estado) {
            case 0:
                return "NÃO";
            case 1:
                return "SIM";
            case 2:
                return "PARCIAL";
        }
    }

    public function get_css_class() {
        switch ($this->estado) {
            case 0:
                return "nao_concluido";
            case 1:
                return "concluido";
            case 2:
                return "parcial";
        }
    }

    public static function get_legend(){
        $legend = array();
        $legend['nao_concluido'] = 'Módulo não concluído, nenhuma atividade realizada.';
        $legend['parcial'] = 'Módulo não concluído, atividades realizadas parcialmente';
        $legend['concluido'] = 'Módulo concluído';

        return $legend;
    }

}

class dado_modulo extends unasus_data {

    private $modulo;

    function __construct($modulo) {
        $this->modulo = $modulo;
    }

    public function __toString() {
        return $this->modulo;
    }

    public function get_css_class() {
        return 'bold';
    }

}

class dado_atividade extends unasus_data {

    private $atividade;

    function __construct($atividade) {
        $this->atividade = $atividade;
    }

    public function __toString() {
        return $this->atividade;
    }

    public function get_css_class() {
        return '';
    }

}