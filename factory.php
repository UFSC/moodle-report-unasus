<?php

class Factory{

    //Atributos globais
    protected $curso_ufsc;
    protected $curso_moodle;
    protected $cursos;

    // Relatório a ser mostrado
    protected $relatorio;
    // Tipo de exibição - null, tabela, grafico_valores, grafico_porcentagens, grafico_pontos
    protected $modo_exibicao;

    //Atributos para os filtros
    public $ocultar_barra_filtragem;
    public $mostrar_botoes_grafico;
    public $mostrar_botoes_dot_chart;
    public $mostrar_filtro_polos;
    public $mostrar_filtro_modulos;
    public $mostrar_filtro_intervalo_tempo;
    public $mostrar_aviso_intervalo_tempo;

    //Atributos para os gráficos
    public $modulos_selecionados;
    public $polos_selecionados;
    public $tutores_selecionados;
    public $agrupar_relatorios_por_polos;
    public $texto_cabecalho;

    //Atributos especificos para os relatorios de uso sistema tutor e acesso tutor
    public $data_inicio;
    public $data_fim;

    //Singleton
    private static $instance;

    // Setar os valores defaults para os relatórios e filtros
    private function __construct(){
        //Atributos globais
        $this->curso_ufsc = get_curso_ufsc_id();
        $this->curso_moodle = get_course_id();
        $this->cursos = get_cursos_ativos_list();

        //Atributos para os filtros
        $this->ocultar_barra_filtragem = false;
        $this->mostrar_botoes_grafico = true;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = true;
        $this->mostrar_filtro_modulos = true;
        $this->mostrar_filtro_intervalo_tempo = false;
        $this->mostrar_aviso_intervalo_tempo = false;

        //Atributos para os gráficos
        //Por default os módulos selecionados são os módulos que o curso escolhido possui
        $this->ocultar_barra_filtragem = true;
        $this->texto_cabecalho = 'Estudantes';

        $modulos_raw = optional_param_array('modulos', null, PARAM_INT);
        if(is_null($modulos_raw)){
            $modulos_raw = array_keys(get_id_nome_modulos(get_curso_ufsc_id()));
        }
        $this->modulos_selecionados = get_atividades_cursos(get_modulos_validos($modulos_raw));
        $this->polos_selecionados = optional_param_array('polos', null, PARAM_INT);
        $this->tutores_selecionados = optional_param_array('tutores', null, PARAM_INT);
        $this->agrupar_relatorios_por_polos = optional_param('agrupar_tutor_polo_select', null, PARAM_BOOL);

        //Atributos especificos para os relatorios de uso sistema tutor e acesso tutor
        $data_inicio = optional_param('data_inicio', null, PARAM_TEXT);
        $data_fim = optional_param('data_fim', null, PARAM_TEXT);
        if(date_interval_is_valid($data_inicio,$data_fim)){
            $this->data_inicio = $data_inicio;
            $this->data_fim = $data_fim;
        }

    }

    // Criação do objeto unico
    public static function singleton(){
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }

    // Previne que o usuário clone a instância
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }


    /**
     * @return int curso ufsc
     */
    public function get_curso_ufsc(){
        return $this->curso_ufsc;
    }

    /**
     * @return int curso ufsc
     */
    public function get_curso_moodle(){
        return $this->curso_moodle;
    }

    /**
     * @return string nome de uma classe
     */
    public function get_estrutura_dados_relatorio(){
        return "dado_{$this->relatorio}";
    }


    /**
     * @return string nome de um metodo
     */
    public function get_dados_relatorio(){
        return "get_dados_{$this->relatorio}";
    }

    /**
     * @return string nome de um metodo
     */
    public function get_table_header_relatorio(){
        return "get_table_header_{$this->relatorio}";
    }

    /**
     * @return string nome de um metodo
     */
    public function get_dados_grafico_relatorio(){
        return "get_dados_grafico_{$this->relatorio}";
    }


    /**
     * @param $relatorio string nome do relatorio
     */
    public function set_relatorio($relatorio){
        $options = report_unasus_relatorios_validos_list();
        if(in_array($relatorio, $options)){
            $this->relatorio = $relatorio;
        }else{
            print_error('unknow_report', 'report_unasus');
        }
    }

    /**
     * @return string nome do relatorio
     */
    public function get_relatorio(){
        return $this->relatorio;
    }

    /**
     * @param $modo_exibicao string tipo de relatorio a ser exibido
     */
    public function set_modo_exibicao($modo_exibicao){
        $options = array(null, 'grafico_valores', 'tabela', 'grafico_porcentagens', 'grafico_pontos');
        if(in_array($modo_exibicao, $options)){
            $this->modo_exibicao = $modo_exibicao;
        }else{
            print_error('unknow_report', 'report_unasus');
        }
    }

    /**
     * @return string tipo de relatorio a ser exibido
     */
    public function get_modo_exibicao(){
        return $this->modo_exibicao;
    }


    /**
     * Returns course context instance.
     * @return context_course
     */
    public function get_context(){
        return context_course::instance($this->get_curso_moodle());
    }

    /**
     * @return array Parametros para o GET da pagina HTML
     */
    public function get_page_params(){
        return array('relatorio' => $this->get_relatorio(), 'course' => $this->get_curso_moodle());
    }

    /**
     * @return array array com as ids dos modulos
     */
    public function get_modulos_ids(){
        return array_keys($this->modulos_selecionados);
    }

    /**
     * @return bool se as datas foram setadas no construtor, passando pelo date_interval_is_valid elas são validas
     */
    public function datas_validas(){
        return (!is_null($this->data_inicio) && !is_null($this->data_fim));
    }


}
