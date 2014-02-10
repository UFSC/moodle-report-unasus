<?php

class report_potenciais_evasoes extends Factory {

    public function initialize() {
        $this->mostrar_botoes_grafico = false;
        $this->texto_cabecalho = 'Tutores';
    }
    
    public function render_report($render) {
        $this->texto_cabecalho = 'Tutores';
        echo $renderer->build_report();
    }

}