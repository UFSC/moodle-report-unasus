<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Class Factory
 *
 * Esta classe tem como objetivo ser uma central de informações para os filtros e gráficos
 * durante o processo de geração deste plugin.
 *
 * Ela é uma classe singleton para que em qualquer escopo deste plugin as variáveis setadas sejam as mesmas.
 *
 * Esta opção se mostrou altamente eficiente já que a quantidade de parametros passados a cada função
 * estavam crescendo de acordo com a complexidade dos gráficos e a utilização destas variáveis só são
 * invocadas quando realmente necessárias.
 *
 * Os atributos setados no construtor da classe são os valores padrão de filtragem e parametros pegos via
 * GET e POST, alguns parametros são protected para evitar sua alteração desnecessária.
 *
 * Os atributos da barra de filtragem, que variam de relatório em relatório são setados no arquivo
 * index.php de acordo com o relatório selecionado.
 */
define('AGRUPAR_TUTORES', 'TUTORES');
define('AGRUPAR_POLOS', 'POLOS');
define('AGRUPAR_COHORTS', 'COHORTS');
define('AGRUPAR_ORIENTADORES', 'ORIENTADORES');

define('TCC-Turma-A', 230);
define('TCC-Turma-B', 258);

//use local_ufsc\ufsc;

class report_unasus_factory {

    // Atributos globais

    /** @var int|mixed $curso_moodle Código do curso Moodle em que este relatório foi acessado */
    protected $curso_moodle;

    /** @var bool|string $categoria_curso_ufsc ID da categoria do curso UFSC associdado a este relatório */
    protected $categoria_curso_ufsc;

    /** @var bool|string $categoria_turma_ufsc ID da categoria da turma do curso UFSC associdado a este relatório */
    protected $categoria_turma_ufsc;

    /** @var  string $relatorio relatório atual que será mostrado */
    protected $relatorio;

    /** @var  mixed $modo_exibicao valores possíveis: null, tabela, grafico_valores, grafico_porcentagens, grafico_pontos */
    protected $modo_exibicao;

    /** @var  array $visiveis_atividades_cursos Apresenta uma lista com atividades/cursos da categoria do relatório que foram configuradas para serem apresentadas */
    public $visiveis_atividades_cursos;


    // Atributos para construir tela de filtros
    public $mostrar_barra_filtragem; //mostrar ou esconder filtro
    public $mostrar_botoes_grafico;
    public $mostrar_botoes_dot_chart;
    public $mostrar_filtro_polos;
    public $mostrar_filtro_modulos;
    public $mostrar_filtro_grupo_tutoria;
    public $mostrar_filtro_tutores;
    public $mostrar_filtro_grupos_orientacao;
    public $mostrar_filtro_orientadores;
    public $mostrar_filtro_intervalo_tempo;
    public $mostrar_aviso_intervalo_tempo;

    public $mostrar_filtro_cohorts;
    public $mostrar_botao_exportar_csv;

    // Armazenamento de valores definidos nos filtros
    public $cohorts_selecionados;
    public $modulos_selecionados;
    public $polos_selecionados;
    public $tutores_selecionados;
    public $orientadores_selecionados;
    public $agrupar_relatorios;

    // Atributos para os gráficos e tabelas
    public $texto_cabecalho;

    //Atributos especificos para os relatorios de uso sistema tutor e acesso tutor
    public $data_inicio;
    public $data_fim;

    // Singleton
    private static $report;

    protected function __construct() {
        // Atributos globais
        $this->curso_moodle = report_unasus_get_course_id();

        $this->categoria_curso_ufsc = \local_tutores\categoria::curso_ufsc($this->curso_moodle);
        $this->categoria_turma_ufsc = \local_tutores\categoria::turma_ufsc($this->curso_moodle);

        // Atributos para os gráficos
        // Por default os módulos selecionados são os módulos que o curso escolhido possui
        $this->texto_cabecalho = 'Estudantes';

        // Recupera os módulos enviados do filtro e caso nenhum tenha sido selecionado, retorna todos os possíveis.
        $modulos = optional_param_array('modulos', null, PARAM_INT);
        if (is_null($modulos)) {
            $modulos = array_keys(report_unasus_get_id_nome_modulos($this->categoria_turma_ufsc));
        }

        // Valores definidos nos filtros
        $this->cohorts_selecionados = optional_param_array('cohorts', null, PARAM_INT);
        $this->modulos_selecionados = $modulos;

        // chama report_unasus_get_atividades_cursos para montar o conjunto de colunas visíveis do cabeçalho
        $this->visiveis_atividades_cursos = report_unasus_get_atividades_cursos(
            $modulos,
            false,
            false,
            false,
            $this->categoria_turma_ufsc
        );


        $this->polos_selecionados = optional_param_array('polos', null, PARAM_INT);
        $this->tutores_selecionados = optional_param_array('tutores', null, PARAM_INT);
        $this->orientadores_selecionados = optional_param_array('orientadores', null, PARAM_INT);

        //AGRUPAMENTO DO RELATORIO
        $agrupar_relatorio = optional_param('agrupar_tutor_polo_select', null, PARAM_INT);
        switch ($agrupar_relatorio) {
            case 1:
                $this->agrupar_relatorios = AGRUPAR_POLOS;
                break;
            case 2:
                $this->agrupar_relatorios = AGRUPAR_COHORTS;
                break;
            case 3:
                $this->agrupar_relatorios = AGRUPAR_ORIENTADORES;
                break;
            default:
                $this->agrupar_relatorios = AGRUPAR_TUTORES;
                break;
        }

        $this->agrupamentos_membros = report_unasus_get_agrupamentos_membros($modulos);

        //Atributos especificos para os relatorios de uso sistema tutor e acesso tutor
        $data_inicio = optional_param('data_inicio', null, PARAM_TEXT);
        $data_fim = optional_param('data_fim', null, PARAM_TEXT);

        if (report_unasus_date_interval_is_valid($data_inicio, $data_fim)) {
            $this->data_inicio = $data_inicio;
            $this->data_fim = $data_fim;
        }

        // Verifica se é um relatorio valido
        $this->set_relatorio(optional_param('relatorio', null, PARAM_ALPHANUMEXT));

        // Verifica se é um modo de exibicao valido
        $this->set_modo_exibicao(optional_param('modo_exibicao', null, PARAM_ALPHANUMEXT));
    }

    /**
     * Fabrica um objeto com as definições dos relatórios que também é um singleton
     * 
     * @global type $CFG
     * @return report_unasus_factory
     * @throws Exception
     */

    public static function singleton() {

        global $CFG;

        $report = optional_param('relatorio', null, PARAM_ALPHANUMEXT);

        if (! in_array($report, report_unasus_relatorios_validos_list())){
            print_error('unknow_report', 'report_unasus');
            return false;
        }

        $class_name = "report_{$report}";

        // carrega arquivo de definição do relatório
        require_once $CFG->dirroot . "/report/unasus/reports/{$class_name}.php";

        if (!class_exists($class_name)) {
            throw new Exception('Missing format class.');
        }

        if (!isset(self::$report)) {
            self::$report = new $class_name;
            self::$report->initialize();
        }

        return self::$report;
    }

    /**
     * Verifica se é um relatório válido e o seta
     * @deprecated 
     * @param string $relatorio nome do relatorio
     */
    public function set_relatorio($relatorio) {
        $options = report_unasus_relatorios_validos_list();
        if (in_array($relatorio, $options)) {
            $this->relatorio = $relatorio;
        } else {
            print_error('unknow_report', 'report_unasus');
        }
    }

    // Previne que o usuário clone a instância
    public function __clone() {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    /**
     * @return int curso ufsc
     */
    public function get_curso_moodle() {
        return $this->curso_moodle;
    }

    /**
     * @return int categoria do curso ufsc
     */
    public function get_categoria_curso_ufsc() {
        return $this->categoria_curso_ufsc;
    }

    /**
     * @return int categoria da turma do curso ufsc
     */
    public function get_categoria_turma_ufsc() {
        return $this->categoria_turma_ufsc;
    }

    /**
     * @return string nome de uma classe
     */
    public function get_estrutura_dados_relatorio() {
        return "report_unasus_dado_{$this->relatorio}_render";
    }

    /**
     * Verifica se o relatório possui gráfico definido
     *
     * @param $report
     * @return bool
     */
    public function relatorio_possui_grafico($report) {
        $method = 'get_dados_grafico';

        if (method_exists($report, $method))
            return true;
        return false;
    }

    /**
     * @return string nome do relatorio
     */
    public function get_relatorio() {
        return $this->relatorio;
    }

    /**
     * Verifica se é um modo de exibição válido e o seta
     *
     * @param string $modo_exibicao tipo de relatorio a ser exibido
     */
    public function set_modo_exibicao($modo_exibicao) {
        $options = array(null, 'grafico_valores', 'tabela', 'grafico_porcentagens', 'grafico_pontos', 'export_csv');
        if (in_array($modo_exibicao, $options)) {
            $this->modo_exibicao = $modo_exibicao;
        } else {
            print_error('unknow_report', 'report_unasus');
        }
    }

    /**
     * @return string tipo de relatorio a ser exibido
     */
    public function get_modo_exibicao() {
        return $this->modo_exibicao;
    }


    /**
     * Returns course context instance.
     *
     * @return context_course
     */
    public function get_context() {
        return context_course::instance($this->get_curso_moodle());
    }

    /**
     * @return array Parametros para o GET da pagina HTML
     */
    public function get_page_params() {
        return array('relatorio' => $this->get_relatorio(), 'course' => $this->get_curso_moodle());
    }

    /**
     * @return array ids dos modulos
     */
    public function get_modulos_ids() {
        return $this->modulos_selecionados;
    }

    /**
     * @return bool se as datas foram setadas no construtor, passando pelo date_interval_is_valid elas são validas
     */
    public function datas_validas() {
        return (!is_null($this->data_inicio) && !is_null($this->data_fim));
    }

    /**
     * Retorna TRUE se usuário faz parte de um determinado agrupamento p/ um determinado course_id
     * @param $grouping_id
     * @param $course_id
     * @param $user_id
     * @return bool
     */
    public function is_member_of($grouping_id, $course_id, $user_id) {
        return isset($this->agrupamentos_membros[$grouping_id][$course_id][$user_id]);
    }

    static function eliminate_html ($data){
        return strip_tags($data);
    }

    function get_table_header_modulos_atividades($mostrar_nota_final = false, $mostrar_total = false) {
        $header = array();
        foreach ($this->visiveis_atividades_cursos as $course_id => $atividades) {
          if(isset($atividades[0]->course_name)) {
              $course_url = new moodle_url('/course/view.php', array('id' => $course_id, 'target' => '_blank'));
              $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));
              $header[$course_link] = $atividades;
          }
        }

        return $header;
    }

    function get_table_header_tcc_atividades($is_tcc = false) {

        $group_array = new report_unasus_GroupArray();

        // Busca os dados dos capítulos dos TCCs e coloca em $group_array
        report_unasus_process_header_tcc_atividades(
            $this->get_modulos_ids(),
            $group_array,
            $is_tcc);

        $atividades_cursos = $group_array->get_assoc();
        $header = array();

        foreach ($atividades_cursos as $course_module_id => $atividades) {
            if (!empty($atividades)) {
                $course_url = new moodle_url('/mod/lti/view.php', array('id' => $atividades[0]->course_module_id, 'target' => '_blank'));
                $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));
                $header[$course_link] = $atividades;
            }
        }

        return $header;
    }

}
