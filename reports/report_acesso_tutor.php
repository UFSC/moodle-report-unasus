<?php

class report_acesso_tutor extends Factory {

    public function initialize() {
        $this->mostrar_filtro_cohorts = false;
        $this->mostrar_filtro_modulos = false;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_filtro_polos = false;
        $this->mostrar_filtro_intervalo_tempo = true;
    }

    public function render_report($render) {
        if ($this->datas_validas()) {
            $this->texto_cabecalho = 'Tutores';
            echo $renderer->build_report();
        }
        $this->mostrar_aviso_intervalo_tempo = true;
        echo $renderer->build_page();
    }

    public function get_dados() {

    }

    public function get_table_header() {

    }

}