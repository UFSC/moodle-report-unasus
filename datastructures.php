<?php

defined('MOODLE_INTERNAL') || die;

//
// Pessoas
//

/**
 * Estrutura de dados de Pessoas (Tutores, Estudantes)
 *
 * Auxilia renderização nos relatórios.
 */
class report_unasus_person {

    protected $name;
    protected $id;
    protected $courseid;

    /**
     * @param $name
     * @param $id
     * @param $courseid
     */
    function __construct($name, $id, $courseid) {
        $this->name = $name;
        $this->id = $id;
        $this->courseid = $courseid;
    }

    /**
     * @return string
     */
    function __toString() {
        global $OUTPUT;
        $email_icon = $OUTPUT->pix_icon('t/email', 'Enviar mensagem');
        $profile_link = html_writer::link(new moodle_url('/user/view.php', array('id' => $this->id, 'course' => $this->courseid)), $this->name, array('target' => '_blank'));
        $message_link = html_writer::link(new moodle_url('/message/index.php', array('id' => $this->id)), $email_icon, array('target' => '_blank'));

        return "{$message_link} {$profile_link}";
    }

    function get_name() {
        return $this->name;
    }

}

/**
 * Estrutura de dados que representa um estudante
 */
class report_unasus_student extends report_unasus_person {

    public $cohort;
    public $polo;

    /**
     * Estudantes - Possuem polo e cohort para poderem fazer o agrupamento nos relatórios
     *
     * @todo Refatorar agrupamentos, para não precisar passar tantos parâmetros, passar apenas aquilo que está sendo utilizado
     * @param $name
     * @param $id
     * @param $courseid
     * @param $polo
     * @param null $cohort
     */
    function __construct($name, $id, $courseid, $polo, $cohort = null) {
        parent::__construct($name, $id, $courseid);
        $this->polo = $polo;
        $this->cohort = $cohort;
    }

}

//
// Relatórios
//

/**
 * Estrutura para auxiliar a renderização dos dados dos relatórios
 */
abstract class unasus_data {

    /**
     * Dependendo do tipo do dado ele deve ser apresentado de uma forma diferente
     * Assim esta função é feita para definir qual classe de css deve ser aplicada
     * a um tipo de dado
     */
    public abstract function get_css_class();

    /**
     * Função para auxiliar na renderização dos relatórios, concordância para o caso de "um dia" e "mesmo dia"
     *
     * @param int $num_dias
     * @return string
     */
    public function dia_toString($num_dias) {
        if ($num_dias == 0) {
            return '< 1 dia';
        } elseif ($num_dias == 1) {
            return '1 dia';
        } else {
            return "{$num_dias} dias";
        }
    }

    /**
     * Formata uma nota para exibição
     *
     * @param $grade
     * @return string
     */
    protected function format_grade($grade) {
        return format_float($grade, 1);
    }

}

class dado_atividades_vs_notas extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE = 0;
    const CORRECAO_ATRASADA = 1;
    const ATIVIDADE_AVALIADA_SEM_ATRASO = 2;
    const ATIVIDADE_NO_PRAZO_ENTREGA = 3;
    const ATIVIDADE_SEM_PRAZO_ENTREGA = 4;
    const ATIVIDADE_AVALIADA_COM_ATRASO = 5;

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
            case dado_atividades_vs_notas::ATIVIDADE_AVALIADA_SEM_ATRASO:
                return (String) $this->format_grade($this->nota);
                break;
            case dado_atividades_vs_notas::ATIVIDADE_AVALIADA_COM_ATRASO:
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
        switch ($this->tipo) {
            case dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE:
                return 'nao_entregue';
            case dado_atividades_vs_notas::CORRECAO_ATRASADA:
                return ($this->atraso >= get_prazo_avaliacao()) ? 'muito_atraso' : 'pouco_atraso';
            case dado_atividades_vs_notas::ATIVIDADE_AVALIADA_SEM_ATRASO:
                return 'nota_atribuida';
            case dado_atividades_vs_notas::ATIVIDADE_AVALIADA_COM_ATRASO:
                return 'nota_atribuida_atraso';
            case dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA:
                return 'nao_realizada';
            case dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA:
                return 'sem_prazo';
            default:
                return '';
        }
    }

    public static function get_legend() {

        $legend = array();
        $legend['nota_atribuida'] = 'Nota atribuída no prazo (até '.get_prazo_avaliacao() * 24 .'hs)';
        $legend['nota_atribuida_atraso'] = 'Nota atribuída fora do prazo (mais de '.get_prazo_avaliacao() * 24 .'hs)';
        $legend['pouco_atraso'] = "Sem nota atribuída, dentro do prazo (até ".get_prazo_avaliacao()." dias após data de entrega)";
        $legend['muito_atraso'] = "Sem nota atribuída, fora do prazo (após ".get_prazo_maximo_avaliacao()." dias da data de entrega)";
        $legend['nao_entregue'] = 'Atividade não realizada, após data esperada';
        $legend['nao_realizada'] = 'Atividade não realizada, mas dentro da data esperado';
        $legend['sem_prazo'] = 'Atividade não realizada, sem prazo definido para a entrega';

        return $legend;
    }

    public function get_atividade_id() {
        return $this->atividade_id;
    }

}

class dado_tcc_concluido extends unasus_data {

    const ATIVIDADE_NAO_CONCLUIDA = 0;
    const ATIVIDADE_CONCLUIDA = 1;

    function __construct($tipo, $chapter) {
        $this->tipo = $tipo;
        $this->chapter = $chapter;
    }

    public function __toString() {
        return '';
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_tcc_concluido::ATIVIDADE_NAO_CONCLUIDA:
                return 'nao_concluido';
                break;
            case dado_tcc_concluido::ATIVIDADE_CONCLUIDA:
                return 'concluido';
                break;
        }
    }

    public static function get_legend() {
        $legend = array();
        $legend['nao_concluido'] = 'Atividade não concluída';
        $legend['concluido'] = 'Atividade concluída';

        return $legend;
    }

}

class dado_tcc_portfolio_concluido extends unasus_data {

    const ATIVIDADE_NAO_CONCLUIDA = 0;
    const ATIVIDADE_CONCLUIDA = 1;
    const ATIVIDADE_NAO_APLICADO = 2;

    function __construct($tipo, $atividade_id, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
        $this->atividade_id = $atividade_id;
    }

    public function __toString() {
        return '';
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_tcc_portfolio_concluido::ATIVIDADE_NAO_CONCLUIDA:
                return 'nao_concluido';
                break;
            case dado_tcc_portfolio_concluido::ATIVIDADE_CONCLUIDA:
                return 'concluido';
                break;
            case dado_tcc_portfolio_concluido::ATIVIDADE_NAO_APLICADO:
                return 'nao_aplicado';
                break;
        }
    }

    public static function get_legend() {
        $legend = array();
        $legend['nao_concluido'] = 'Atividade não concluída';
        $legend['concluido'] = 'Atividade concluída';
        $legend['nao_aplicado'] = 'Não aplicado';

        return $legend;
    }

    public function get_atividade_id() {
        return $this->atividade_id;
    }

}

class dado_tcc_entrega_atividades extends unasus_data {

    const ATIVIDADE_RASCUNHO = 1;
    const ATIVIDADE_REVISAO = 2;
    const ATIVIDADE_AVALIADO = 3;
    const ATIVIDADE_NAO_APLICADO = 4;

    private $atraso;

    function __construct($tipo, $chapter, $atraso = 0) {
        $this->tipo = $tipo;
        $this->chapter = $chapter;
        $this->atraso = $atraso;
    }

    public function __toString() {
        $dia = ($this->atraso == 1) ? ' dia' : ' dias';
        switch ($this->tipo) {
            case dado_tcc_entrega_atividades::ATIVIDADE_RASCUNHO:
                return ($this->atraso == 0) ? '<b>' . 'Hoje' . '</b>' : '<b>' . $this->atraso . $dia . '</b>';
                break;
            case dado_tcc_entrega_atividades::ATIVIDADE_REVISAO:
                return ($this->atraso == 0) ? '<b>' . 'Hoje' . '</b>' : '<b>' . $this->atraso . $dia . '</b>';
                break;
        }
        return '';
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_tcc_entrega_atividades::ATIVIDADE_RASCUNHO:
                return 'rascunho';
            case dado_tcc_entrega_atividades::ATIVIDADE_REVISAO:
                return 'revisao';
            case dado_tcc_entrega_atividades::ATIVIDADE_AVALIADO:
                return 'avaliado';
            case dado_tcc_entrega_atividades::ATIVIDADE_NAO_APLICADO:
                return 'nao_aplicado';
        }
    }

    public static function get_legend() {

        $legend = array();
        $legend['rascunho'] = 'Rascunho';
        $legend['revisao'] = 'Avaliando';
        $legend['avaliado'] = 'Avaliado';
        $legend['nao_aplicado'] = 'Não editado';

        return $legend;
    }

}

class dado_tcc_portfolio_entrega_atividades extends unasus_data {

    const ATIVIDADE_NAO_ACESSADO = 0;
    const ATIVIDADE_RASCUNHO = 1;
    const ATIVIDADE_REVISAO = 2;
    const ATIVIDADE_AVALIACAO = 3;
    const ATIVIDADE_AVALIADO = 4;
    const ATIVIDADE_NAO_APLICADO = 5;

    function __construct($tipo, $atividade_id, $atraso = 0) {
        $this->tipo = $tipo;
        $this->atraso = $atraso;
        $this->atividade_id = $atividade_id;
    }

    public function __toString() {
        return '';
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_tcc_portfolio_entrega_atividades::ATIVIDADE_NAO_ACESSADO:
                return 'nao_acessado';
            case dado_tcc_portfolio_entrega_atividades::ATIVIDADE_RASCUNHO:
                return 'rascunho';
            case dado_tcc_portfolio_entrega_atividades::ATIVIDADE_REVISAO:
                return 'revisao';
            case dado_tcc_portfolio_entrega_atividades::ATIVIDADE_AVALIACAO:
                return 'avaliacao';
            case dado_tcc_portfolio_entrega_atividades::ATIVIDADE_AVALIADO:
                return 'avaliado';
            case dado_tcc_portfolio_entrega_atividades::ATIVIDADE_NAO_APLICADO:
                return 'nao_aplicado';
        }
    }

    public static function get_legend() {

        $legend = array();
        $legend['nao_acessado'] = 'Não acessado';
        $legend['rascunho'] = 'Rascunho';
        $legend['revisao'] = 'Revisão';
        $legend['avaliacao'] = 'Avaliação';
        $legend['avaliado'] = 'Avaliado';
        $legend['nao_aplicado'] = 'Não aplicado';

        return $legend;
    }

    public function get_atividade_id() {
        return $this->atividade_id;
    }

}

class dado_entrega_de_atividades extends unasus_data {

    const ATIVIDADE_NAO_ENTREGUE_FORA_DO_PRAZO = 0;
    const ATIVIDADE_ENTREGUE_NO_PRAZO = 1;
    const ATIVIDADE_ENTREGUE_FORA_DO_PRAZO = 2;
    const ATIVIDADE_SEM_PRAZO_ENTREGA = 3;
    const ATIVIDADE_NAO_ENTREGUE_MAS_NO_PRAZO = 4;

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
            case dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_FORA_DO_PRAZO:
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
            case dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_MAS_NO_PRAZO:
                return '';
                break;
        }
    }

    public function get_css_class() {
        global $CFG;
        switch ($this->tipo) {
            case dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_FORA_DO_PRAZO:
                return 'nao_entregue_fora_do_prazo';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO:
                return 'no_prazo';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO:
                return ($this->atraso > $CFG->report_unasus_prazo_maximo_entrega) ? 'muito_atraso' : 'pouco_atraso';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA:
                return 'sem_prazo';
                break;
            case dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_MAS_NO_PRAZO:
                return 'nao_entregue_mas_no_prazo';
                break;
        }
    }

    public static function get_legend() {
        global $CFG;

        $legend = array();
        $legend['nao_entregue_mas_no_prazo'] = 'Atividade não entregue, mas dentro do prazo';
        $legend['nao_entregue_fora_do_prazo'] = 'Atividade não entregue, já fora do prazo';
        $legend['sem_prazo'] = 'Atividade não realizada, sem prazo definido para a entrega';
        $legend['no_prazo'] = 'Atividade entregue em dia';
        $legend['pouco_atraso'] = "Atividade entregue, com atraso de até {$CFG->report_unasus_prazo_maximo_entrega} dias";
        $legend['muito_atraso'] = "Atividade entregue, com atraso de mais de {$CFG->report_unasus_prazo_maximo_entrega} dias";

        return $legend;
    }

    public function get_atividade_id() {
        return $this->atividade_id;
    }

}

class dado_boletim extends unasus_data {

    const ATIVIDADE_COM_NOTA = 0;
    const ATIVIDADE_SEM_NOTA = 1;

    private $tipo;
    private $atividade_id;
    private $nota;

    function __construct($tipo, $atividade_id, $nota = 0) {
        $this->tipo = $tipo;
        $this->atividade_id = $atividade_id;
        $this->nota = $nota;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_boletim::ATIVIDADE_COM_NOTA:
                return (String) $this->format_grade($this->nota);
                break;
            case dado_boletim::ATIVIDADE_SEM_NOTA:
                return '';
                break;
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_boletim::ATIVIDADE_COM_NOTA:
                return ($this->nota >= 70) ? 'na_media' : 'abaixo_media_nota';
                break;
            case dado_boletim::ATIVIDADE_SEM_NOTA:
                return 'sem_nota';
                break;
        }
    }

    public static function get_legend() {
        $legend = array();
        $legend['na_media'] = 'Atividade avaliada com nota acima de 70';
        $legend['abaixo_media_nota'] = 'Atividade avaliada com nota abaixo de 70';
        $legend['abaixo_media'] = 'Média final abaixo de 70';
        $legend['sem_nota'] = 'Atividade não avaliada ou não entregue';

        return $legend;
    }

}

class dado_nota_final extends unasus_data {

    const ATIVIDADE_COM_NOTA = 0;
    const ATIVIDADE_SEM_NOTA = 1;

    private $tipo;
    private $nota;

    function __construct($tipo, $nota = 0) {
        $this->tipo = $tipo;
        $this->nota = $nota;
    }

    public function __toString() {
        switch ($this->tipo) {
            case dado_boletim::ATIVIDADE_COM_NOTA:
                return (String) $this->format_grade($this->nota);
                break;
            case dado_boletim::ATIVIDADE_SEM_NOTA:
                return '';
                break;
        }
    }

    public function get_css_class() {
        switch ($this->tipo) {
            case dado_boletim::ATIVIDADE_COM_NOTA:
                return ($this->nota >= 70) ? 'na_media' : 'abaixo_media';
                break;
            case dado_boletim::ATIVIDADE_SEM_NOTA:
                return 'sem_nota';
                break;
        }
    }

    public static function get_legend() {
        $legend = array();
        $legend['com_nota'] = 'Atividade avaliada';
        $legend['sem_nota'] = 'Atividade não avaliada ou não entregue';

        return $legend;
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

    public function get_atividade_id() {
        return $this->atividade_id;
    }

}

class dado_atividades_nota_atribuida extends dado_atividades_alunos {}

/**
 * Classe para relatorio sintese de atividades concluidas,
 * N° de alunos de concluiram as atividades
 */
class dado_atividades_alunos extends unasus_data {

    private $total;
    private $count;

    function __construct($total, $count = 0) {
        $this->total = $total;
        $this->count = $count;
    }

    public function incrementar() {
        $this->count++;
    }

    public function get_total() {
        return $this->total;
    }

    public function set_total($total){
        $this->total = $total;
    }

    public function get_count() {
        return $this->count;
    }

    public function set_count($count){
        $this->count = $count;
    }

    public function __toString() {
        $porcentagem = new dado_media($this->count, $this->total);

        return "$porcentagem";
    }

    public function get_css_class() {
        return '';
    }

}

/**
 * Class dado_somatorio_grupo
 * Relatorio TCC consolidados
 */
class dado_somatorio_grupo extends unasus_data {

    private $soma = array();

    private function init($grupo, $id) {
        if (!array_key_exists($grupo, $this->soma)) {
            $this->soma[$grupo] = array();
        }
        if (!array_key_exists($id, $this->soma[$grupo])) {
            $this->soma[$grupo][$id] = 0;
        }
    }

    public function inc($grupo, $id, $bool = true) {
        $this->init($grupo, $id);
        if ($bool) {
            $this->soma[$grupo][$id]++;
        }
    }

    public function add($grupo, $id, $value) {
        $this->init($grupo, $id);
        $this->soma[$grupo][$id] += $value;
    }

    public function get($grupo = null, $id = null) {
        if (!is_null($grupo) && !is_null($id)) {
            return array_key_exists($grupo, $this->soma) ? $this->soma[$grupo][$id] : 0;
        } elseif (!is_null($grupo)) {
            return array_key_exists($grupo, $this->soma) ? $this->soma[$grupo] : 0;
        }

        return $this->soma;
    }

    public function get_css_class() {
        return '';
    }

    public function toString() {
        return '';
    }
}

class dado_atividades_nota_atribuida_alunos extends unasus_data {

    private $atividades_concluidas = array();
    private $total_atividades = array();

    function __construct($dados_aluno) {

        foreach ($dados_aluno as $dado_atividade) {
            /** @var report_unasus_data $dado_atividade */

            // Se o dado da atividade for do tipo vazio, não fazer nada.
            if ($dado_atividade instanceof report_unasus_data_empty) {
                continue;
            }

            $course_id = $dado_atividade->source_activity->course_id;

            if (!array_key_exists($course_id, $this->atividades_concluidas)) {
                $this->atividades_concluidas[$course_id] = 0;
                $this->total_atividades[$course_id] = 0;
            }

            if ($dado_atividade->has_submitted()) {
                $this->atividades_concluidas[$course_id]++;
            }

            $this->total_atividades[$course_id]++;
        }
    }

    /**
     * Retorna se todas atividades de um dados modulo esta completo
     *
     * @param $course_id
     * @return boolean
     */
    public function is_complete_activities($course_id) {
        if (array_key_exists($course_id, $this->atividades_concluidas)) {
            return $this->atividades_concluidas[$course_id] == $this->total_atividades[$course_id];
        }

        return false;
    }

    public function is_complete_all_activities() {
        foreach ($this->atividades_concluidas as $course_id => $atividade) {
            if (!$this->is_complete_activities($course_id)) {
                return false;
            }
        }

        return true;
    }

    public function get_css_class() {
        return '';
    }

}

/**
 * Dado Média Formatado
 * somatorio de media para incluir coluna Total de atividades concluídas por módulo
 * Ticket 5263
 */
class dado_media extends unasus_data {

    private $somatorio;
    private $total;

    function __construct($somatorio, $total) {
        $this->somatorio = isset($somatorio) ? $somatorio : 0;
        $this->total = isset($total) ? $total : 0;
    }

    public function __toString() {
        if (empty($this->somatorio) || empty($this->total)) {
            $media = 0; // impedir divisão por zero
        } else {
            $media = $this->somatorio * 100 / $this->total;
        }

        $soma = "$this->somatorio/$this->total";

        return "{$soma} <br /> {$this->format_grade($media)}%";
    }

    /* Aguardar reestruturação do design dos relatórios para inserir já o novo css */
    public function get_css_class() {
//        return 'media';
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

class dado_modulos_concluidos extends unasus_data {

    const MODULO_NAO_CONCLUIDO = 0;
    const MODULO_CONCLUIDO = 1;

    private $estado;
    private $final_grade; //nota em letra
    private $numero_atividades_modulo;
    private $atividades_nao_realizadas;
    private $atividade;
    private $atividades_pendentes = array();


    function __construct($numero_atividades_modulo, $final_grade, $atividade) {
        $this->numero_atividades_modulo = $numero_atividades_modulo;
        $this->atividades_nao_realizadas = 0;
        $this->estado = dado_modulos_concluidos::MODULO_CONCLUIDO;
        $this->final_grade = $final_grade;
        $this->atividade = $atividade;
    }

    public function __toString() {
        $pendentes = '';

        switch ($this->estado) {
            case 0: //Há atividades pendentes
                $n_pendentes = count($this->atividades_pendentes);
                for ($i = 0; $i < $n_pendentes; $i++) {
                    $pendentes .= ($i < ($n_pendentes - 1)) ? $this->atividades_pendentes[$i]->__toString().' - '
                            : $this->atividades_pendentes[$i]->__toString();
                }

                return '<b>'.$pendentes.'</b>';
            case 1:
                return ($this->final_grade == '-') ? '<b>'. 'Sem Nota'.'</b>' : (String) '<b>'.$this->final_grade.'</b>';
            default:
                return false;
                break;
        }
    }

    public function get_css_class() {
        switch ($this->estado) {
            case 0:
                return "nao_concluido";
            case 1:
                return "concluido";
            default:
                return false;
                break;
        }
    }

    public function add_atividade_nao_realizada() {
        $this->atividades_nao_realizadas++;
        if ($this->atividades_nao_realizadas == 1) {
            $this->estado = dado_modulos_concluidos::MODULO_NAO_CONCLUIDO;
        }
    }

    public function add_atividades_pendentes($nome_atividade) {
        $this->atividades_pendentes[] = $nome_atividade;
    }

    public function get_total_atividades_nao_realizadas() {
        return $this->atividades_nao_realizadas;
    }

    public static function get_legend() {
        $legend = array();
        $legend['concluido'] = 'Módulo concluído';
        $legend['nao_concluido'] = 'Módulo pendente';

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
            default:
                return false;
                break;
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
            default:
                return false;
                break;
        }
    }

    public function add_atividade_nao_realizada() {
        $this->atividades_nao_realizadas++;
        if ($this->atividades_nao_realizadas == 1) {
            $this->estado = dado_potenciais_evasoes::MODULO_PARCIALMENTE_CONCLUIDO;
        }
        if ($this->atividades_nao_realizadas == $this->total_atividades) {
            $this->estado = dado_potenciais_evasoes::MODULO_NAO_CONCLUIDO;
        }
    }

    public function get_total_atividades_nao_realizadas() {
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
        $course_url = new moodle_url('/course/view.php', array('id' => $this->id, 'target' => '_blank'));

        return html_writer::link($course_url, $this->nome, array('target' => '_blank'));
    }

    public function get_css_class() {
        return 'bold';
    }

}

class dado_texto extends unasus_data {

    private $texto;
    private $class;

    function __construct($texto, $class) {
        $this->texto = $texto;
        $this->class = $class;
    }

    public function __toString() {
        return $this->texto;
    }

    public function get_css_class() {
        return $this->class;
    }

}

class dado_atividade extends unasus_data {

    private $atividade;

    function __construct($atividade) {
        $this->atividade = $atividade;
    }

    public function __toString() {
        return ''.$this->atividade->source_activity->__toString();
    }

    public function get_css_class() {
        return '';
    }

}

/**
 * Representa um dado de cujo estudante não faz parte da atividade
 */
class dado_nao_aplicado extends unasus_data {

    public function __toString() {
        return 'N/A';
    }

    public function get_css_class() {
        return 'nao_aplicado';
    }
}