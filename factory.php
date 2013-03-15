<?php

class Factory{

    //Atributos globais
    protected $curso_ufsc;
    protected $curso_moodle;

    //Atributos para os filtros
    public $ocultar_barra_filtragem;
    public $mostrar_botoes_grafico;
    public $mostrar_botoes_dot_chart;
    public $mostrar_filtro_polos;

    //Atributos para os gráficos
    public $modulos_selecionados;
    public $polos_selecionados;
    public $tutores_selecionados;
    public $agrupar_relatorios_por_polos;

    //Singleton
    private static $instance;

    // Setar os valores defaults para os relatórios e filtros
    private function __construct(){
        //Atributos globais
        $this->curso_ufsc = get_course_id();
        $this->curso_moodle = get_curso_ufsc_id();;

        //Atributos para os filtros
        $this->ocultar_barra_filtragem = false;
        $this->mostrar_botoes_grafico = true;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = true;

        //Atributos para os gráficos
        //Por default os módulos selecionados são os módulos que o curso escolhido possui
        $this->modulos_selecionados = array_keys(get_id_nome_modulos($this->curso_ufsc));;
        $this->polos_selecionados = null;
        $this->tutores_selecionados = null;
        $this->agrupar_relatorios_por_polos = false;
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


    // Funções de Acesso
    public function get_curso_ufsc(){
        return $this->curso_ufsc;
    }

    public function get_curso_moodle(){
        return $this->curso_moodle;
    }


}