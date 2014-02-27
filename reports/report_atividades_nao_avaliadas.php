<?php

class report_atividades_nao_avaliadas extends Factory {

    public function initialize($filtro = true) {
        $this->mostrar_filtro_tutores = true;
        $this->mostrar_barra_filtragem = $filtro;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = true;
        $this->mostrar_filtro_cohorts = true;
        $this->mostrar_filtro_modulos = true;
        $this->mostrar_filtro_intervalo_tempo = false;
        $this->mostrar_aviso_intervalo_tempo = false;
        $this->mostrar_botao_exportar_csv = true;
    }

    public function render_report_default($renderer){
        echo $renderer->build_page();
    }
    
    public function render_report_table($renderer, $report) {
        $this->initialize(false);
        echo $renderer->page_atividades_nao_avaliadas($report);
    }

    /**
     * @param $name_report
     */
    public function render_report_csv($name_report) {

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=relatorio ' . $name_report . '.csv');
        readfile('php://output');

        $dados = $this->get_dados();
        $header = $this->get_table_header();

        $fp = fopen('php://output', 'w');

        $data_header = array('Tutores');
        $first_line = array('');

        foreach($header as $h){
            if(isset($h[0]->course_name)){
                $course_name = $h[0]->course_name;
                $first_line[] = $course_name;
            }
            $n = count($h);
            for($i=0;$i < $n; $i++ ){
                if(isset($h[$i]->name)){
                    $element = $h[$i]->name;
                    $data_header[] = $element;
                }
                //Insere o nome do módulo na célula acima da primeira atividade daquele módulo
                if($i<$n-1){
                    $first_line[] = '';
                } else
                    continue;
            }
        }
        $data_header[] = 'Média';

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        foreach($dados as $d){
            $output = array_map("Factory::eliminate_html", $d);
            fputcsv($fp, $output);
        }
        fclose($fp);
    }

    public function get_dados() {
        // Consulta
        $query_alunos_grupo_tutoria = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();

        $result_array = loop_atividades_e_foruns_sintese($query_alunos_grupo_tutoria, $query_forum, $query_quiz);

        $total_alunos = $result_array['total_alunos'];
        $total_atividades = $result_array['total_atividades'];
        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];

        $somatorio_total_atrasos = array();
        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $results) {

                foreach ($results as $atividade) {
                    /** @var report_unasus_data $atividade */
                    if (!array_key_exists($grupo_id, $somatorio_total_atrasos)) {
                        $somatorio_total_atrasos[$grupo_id] = 0;
                    }

                    if (!$atividade->has_grade() && $atividade->is_grade_needed()) {

                        /** @var dado_atividades_alunos $dado */
                        unset($dado); // estamos trabalhando com ponteiro, não podemos atribuir null ou alteramos o array.

                        if (is_a($atividade, 'report_unasus_data_activity')) {
                            $dado =& $lista_atividade[$grupo_id]['atividade_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_forum')) {
                            $dado =& $lista_atividade[$grupo_id]['forum_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_quiz')) {
                            $dado =& $lista_atividade[$grupo_id]['quiz_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_lti')) {
                            $dado =& $lista_atividade[$grupo_id][$atividade->source_activity->id][$atividade->source_activity->position];

                        }
                        $dado->incrementar();
                        $somatorio_total_atrasos[$grupo_id]++;
                    }
                }
            }
        }

        $dados = array();
        foreach ($lista_atividade as $grupo_id => $grupo) {
            $data = array();
            $data[] = grupos_tutoria::grupo_tutoria_to_string($this->get_curso_ufsc(), $grupo_id);
            foreach ($grupo as $atividades) {
                if (is_array($atividades)) {
                    foreach ($atividades as $atividade) {
                        $data[] = $atividade;
                    }
                } else {
                    $data[] = $atividades;
                }
            }
            $somatorio_atrasos = isset($somatorio_total_atrasos[$grupo_id]) ? $somatorio_total_atrasos[$grupo_id] : 0;
            $data[] = new dado_media($somatorio_atrasos, $total_alunos[$grupo_id] * $total_atividades);
            $dados[] = $data;
        }

        return $dados;
    }

    public function get_table_header($mostrar_nota_final = false, $mostrar_total = false) {
        $header = $this->get_table_header_modulos_atividades($mostrar_nota_final, $mostrar_total);
        $header[''] = array('Média');
        return $header;
    }
}