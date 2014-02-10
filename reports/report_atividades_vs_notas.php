<?php

class report_atividades_vs_notas extends Factory {

    public function initialize() {

    }

    public function render_report($render) {
        echo $renderer->build_report();
    }

}