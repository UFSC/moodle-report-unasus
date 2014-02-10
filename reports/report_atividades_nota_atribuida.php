<?php

class report_atividades_nota_atribuida extends Factory {

    public function initialize() {
        $this->mostrar_botoes_grafico = false;
    }
    
    public function render_report($render) {
        echo $renderer->page_atividades_nao_avaliadas();
    }

}