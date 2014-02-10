<?php

class report_uso_sistema_tutor extends Factory {

    public function initialize() {
        $factory->mostrar_filtro_cohorts = false;
        $factory->mostrar_botoes_dot_chart = true;
        $factory->mostrar_botoes_grafico = false;
        $factory->mostrar_filtro_modulos = false;
        $factory->mostrar_filtro_polos = false;
        $factory->mostrar_filtro_intervalo_tempo = true;
    }

    public function render_report($render) {
        //As strings informadas sao datas validas?
        if ($this->datas_validas()) {
            $this->texto_cabecalho = null;
            echo $renderer->build_report();
        }
        $this->mostrar_aviso_intervalo_tempo = true;
        echo $renderer->build_page();
    }

}