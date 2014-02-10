<?php

class report_estudante_sem_atividade_avaliada extends Factory {

    public function initialize() {
        $this->mostrar_botoes_grafico = false;
    }
    
    public function render_report($render) {
        echo $renderer->page_todo_list();
    }

}