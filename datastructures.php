<?php

defined('MOODLE_INTERNAL') || die;

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
    protected $courseid;

    function __construct($name, $id, $courseid) {
        $this->name = $name;
        $this->id = $id;
        $this->courseid = $courseid;
    }

    function __toString() {
        global $OUTPUT;
        $email_icon = $OUTPUT->pix_icon('t/email', 'Enviar mensagem');
        $profile_link = html_writer::link(new moodle_url('/user/view.php', array('id' => $this->id, 'course' => $this->courseid)), $this->name, array('target' => '_blank'));
        $message_link = html_writer::link(new moodle_url('/message/index.php', array('id' => $this->id)), $email_icon, array('target' => '_blank'));

        return "{$message_link} {$profile_link}";
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

    /**
     * Dependendo do tipo do dado ele deve ser apresentado de uma forma diferente
     * Assim esta função é feita para definir qual classe de css deve ser aplicada
     * a um tipo de dado
     */
    public abstract function get_css_class();

    public function is_header() {
        return $header;
    }

    /**
     * Função para auxiliar na renderização dos relatórios, concordância para o caso de "um dia" e "mesmo dia"
     * @param int $num_dias
     * @return string
     */
    public function dia_toString($num_dias) {
        if ($num_dias == 0) {
            return 'mesmo dia';
        } elseif ($num_dias == 1) {
            return '1 dia';
        } else {
            return "{$num_dias} dias";
        }
    }

    /**
     * Formata uma nota para exibição
     * @param $grade
     */
    protected function format_grade($grade) {
        return format_float($grade, 2);
    }

}

class dado_atividades_vs_notas extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_ATRASADA = 1;
    const ATIVIDADE_AVALIADA = 2;
    const ATIVIDADE_NO_PRAZO_ENTREGA = 3;
    const ATIVIDADE_SEM_PRAZO_ENTREGA = 4;

    private $tipo;
    private $nota;
    private $atraso;
    private $atividade_id;

    function __construct($tipo, $atividade_id, $nota = 0, $atraso = 0) {

        $this->tipo = $tipo;
        $this->atividade_id = $atividade_id;
        $this->nota = $nota;
        $this->atraso = $atraso;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE:
                return 'não entregue';
                break;
            case dado_atividades_vs_notas::CORRECAO_ATRASADA:
                return $this->dia_toString($this->atraso);
                break;
            case dado_atividades_vs_notas::ATIVIDADE_AVALIADA:
                return (String) $this->format_grade($this->nota);
                break;
            case dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA:
                return 'no prazo';
                break;
            case dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA:
                return 'sem prazo';
                break;
        }
    }

    public function get_css_class() {
        global $CFG;
        switch ($this->tipo) {
            case dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
            case dado_atividades_vs_notas::CORRECAO_ATRASADA:
                return ($this->atraso > $CFG->report_unasus_prazo_maximo_avaliacao) ? 'muito_atraso' : 'pouco_atraso';
            case dado_atividades_vs_notas::ATIVIDADE_AVALIADA:
                return 'nota_atribuida';
            case dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA:
                return 'nao_realizada';
            case dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA:
                return 'sem_prazo';
            default:
                return '';
        }
    }

    public static function get_legend() {
        global $CFG;

        $legend = array();
        $legend['nota_atribuida'] = 'Nota atribuída';
        $legend['pouco_atraso'] = "Sem nota atribuída, dentro do prazo (até {$CFG->report_unasus_prazo_avaliacao} dias após data de entrega)";
        $legend['muito_atraso'] = "Sem nota atribuída, fora do prazo (após {$CFG->report_unasus_prazo_maximo_avaliacao} dias da data de entrega)";
        $legend['nao_entregue'] = 'Atividade não realizada, após data esperada';
        $legend['nao_realizada'] = 'Atividade não realizada, mas dentro da data esperado';
        $legend['sem_prazo'] = 'Atividade não realizada, sem prazo definido para a entrega';

        return $legend;
    }

    public function get_atividade_id(){
        return $this->atividade_id;
    }

}

class dado_entrega_de_atividades extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const ATIVIDADE_ENTREGUE_NO_PRAZO = 1;
    const ATIVIDADE_ENTREGUE_FORA_DO_PRAZO = 2;
    const ATIVIDADE_SEM_PRAZO_ENTREGA = 3;

    private $tipo;
    private $atraso;
    private $atividade_id;

    function __construct($tipo, $atividade_id, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
        $this->atividade_id = $atividade_id;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE:
                return '';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO:
                return '';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO:
                return $this->dia_toString($this->atraso);
                break;
            case dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA:
                return 'sem prazo';
                break;
        }
    }

    public function get_css_class() {
        global $CFG;
        switch ($this->tipo) {
            case dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO:
                return 'no_prazo';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO:
                return ($this->atraso > $CFG->report_unasus_prazo_maximo_entrega) ? 'muito_atraso' : 'pouco_atraso';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA:
                return 'sem prazo';
                break;
        }
    }

    public static function get_legend() {
        global $CFG;

        $legend = array();
        $legend['nao_entregue'] = 'Em aberto (não entregue)';
        $legend['sem_prazo'] = 'Atividade não realizada, sem prazo definido para a entrega';
        $legend['no_prazo'] = 'Atividade entregue em dia';
        $legend['pouco_atraso'] = "Atividade entregue, com atraso de até {$CFG->report_unasus_prazo_maximo_entrega} dias";
        $legend['muito_atraso'] = "Atividade entregue, com atraso de mais de {$CFG->report_unasus_prazo_maximo_entrega} dias";

        return $legend;
    }

    public function get_atividade_id(){
        return $this->atividade_id;
    }

}

class dado_historico_atribuicao_notas extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_NO_PRAZO = 1;
    const CORRECAO_POUCO_ATRASO = 2;
    const CORRECAO_MUITO_ATRASO = 3;
    const ATIVIDADE_ENTREGUE_NAO_AVALIADA = 4;

    private $tipo;
    private $atraso;
    private $atividade_id;

    function __construct($tipo, $atividade_id, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
        $this->atividade_id = $atividade_id;

    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_historico_atribuicao_notas::ATIVIDADE_NAO_ENTREGUE:
                return '';
                break;
            default:
                return $this->dia_toString($this->atraso);
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_historico_atribuicao_notas::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
                break;
            case dado_historico_atribuicao_notas::CORRECAO_NO_PRAZO:
                return 'no_prazo';
                break;
            case dado_historico_atribuicao_notas::CORRECAO_POUCO_ATRASO:
                return 'pouco_atraso';
                break;
            case dado_historico_atribuicao_notas::CORRECAO_MUITO_ATRASO:
                return 'muito_atraso';
                break;
            case dado_historico_atribuicao_notas::ATIVIDADE_ENTREGUE_NAO_AVALIADA:
                return 'nao_avaliada';
                break;
        }
    }

    public static function get_legend() {
        global $CFG;

        $legend = array();
        $legend['nao_entregue'] = 'Em aberto (atividade não entregue pelo estudante)';
        $legend['nao_avaliada'] = 'Atividade entregue e não avaliada';
        $legend['no_prazo'] = 'Avaliadas dentro do prazo';
        $legend['pouco_atraso'] = "Avaliadas fora do prazo, em até {$CFG->report_unasus_prazo_maximo_avaliacao} dias";
        $legend['muito_atraso'] = "Avaliadas fora do prazo, após  {$CFG->report_unasus_prazo_maximo_avaliacao} dias";

        return $legend;
    }

    public function get_atividade_id(){
        return $this->atividade_id;
    }

}

class dado_avaliacao_em_atraso extends unasus_data {

    private $total_alunos;
    private $count_atrasos;

    function __construct($total_alunos) {
        $this->total_alunos = $total_alunos;
        $this->count_atrasos = 0;
    }

    public function __toString() {
        $porcentagem = ($this->count_atrasos/$this->total_alunos) * 100;
        return $this->format_grade($porcentagem)."%";
    }

    public function get_css_class() {
        return '';
    }

    public static function get_legend() {
        return false;
    }

    public function incrementar_atraso(){
        $this->count_atrasos++;
    }

}

class dado_atividades_nota_atribuida extends dado_avaliacao_em_atraso {

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
        return $this->format_grade($this->media)."%";
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
class dado_acesso_tutor extends unasus_data {

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

    public static function get_legend() {
        $legend = array();
        $legend['acessou'] = 'Tutor acessou o sistema nesta data';
        $legend['nao_acessou'] = 'Tutor não acessou o sistema nesta data';

        return $legend;
    }

}

class dado_uso_sistema_tutor extends unasus_data {

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

    public static function get_legend() {
        $legend = array();
        $legend['acessou'] = 'Tutor acessou o sistema nesta data por X horas';
        $legend['nao_acessou'] = 'Tutor não acessou o sistema nesta data';

        return $legend;
    }

}

class dado_potenciais_evasoes extends unasus_data {

    const MODULO_NAO_CONCLUIDO = 0;
    const MODULO_CONCLUIDO = 1;
    const MODULO_PARCIALMENTE_CONCLUIDO = 2;

    private $estado;
    private $total_atividades;
    private $atividades_nao_realizadas;

    function __construct($total_atividades) {
        $this->total_atividades = $total_atividades;
        $this->atividades_nao_realizadas = 0;
        $this->estado = dado_potenciais_evasoes::MODULO_CONCLUIDO;
    }

    public function __toString() {
        //$total = $this->total_atividades - $this->atividades_nao_realizadas;
        switch ($this->estado) {
            case 0:
                return "Não";
                //return "Não {$total}/{$this->total_atividades}";
            case 1:
                return "Sim";
                //return "Sim {$total}/{$this->total_atividades}";
            case 2:
                return "Parcial";
                //return "Parcial {$total}/{$this->total_atividades}";
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

    public function add_atividade_nao_realizada(){
        $this->atividades_nao_realizadas++;
        if($this->atividades_nao_realizadas == 1){
            $this->estado = dado_potenciais_evasoes::MODULO_PARCIALMENTE_CONCLUIDO;
        }
        if($this->atividades_nao_realizadas == $this->total_atividades){
            $this->estado = dado_potenciais_evasoes::MODULO_NAO_CONCLUIDO;
        }
    }

    public function get_total_atividades_nao_realizadas(){
        return $this->atividades_nao_realizadas;
    }

    public static function get_legend() {
        $legend = array();
        $legend['nao_concluido'] = 'Módulo não concluído, nenhuma atividade realizada.';
        $legend['parcial'] = 'Módulo não concluído, atividades realizadas parcialmente';
        $legend['concluido'] = 'Módulo concluído';

        return $legend;
    }

}

class dado_modulo extends unasus_data {

    private $id;
    private $nome;

    function __construct($id, $nome) {
        $this->id = $id;
        $this->nome = $nome;
    }

    public function __toString() {
        $course_url = new moodle_url('/course/view.php', array('id' => $this->id));
        return html_writer::link($course_url, $this->nome);
    }

    public function get_css_class() {
        return 'bold';
    }

}

class dado_atividade extends unasus_data {

    private $id;
    private $nome;
    private $course_id;

    function __construct($id, $nome, $course_id) {
        $this->id = $id;
        $this->nome = $nome;
        $this->course_id = $course_id;
    }

    public function __toString() {
            $cm = get_coursemodule_from_instance('assign', $this->id, $this->course_id, null, MUST_EXIST);
            $atividade_url = new moodle_url('/mod/assign/view.php', array('id' => $cm->id));
            return html_writer::link($atividade_url, $this->nome);
    }

    public function get_css_class() {
        return '';
    }

}