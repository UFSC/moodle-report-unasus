<?php

class report_boletim extends Factory {

    public function initialize() {
    }

    public function render_report($render) {
        echo $renderer->build_report();
    }

}