<?php

class report_entrega_de_atividades extends Factory {

    public function initialize() {

    }

    public function render_report($render) {
        echo $renderer->build_report();
    }

}