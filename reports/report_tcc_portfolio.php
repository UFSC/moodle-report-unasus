<?php

class report_tcc_portfolio extends Factory {

    public function initialize() {

    }

    public function render_report($render) {
        echo $renderer->build_report();
    }

}