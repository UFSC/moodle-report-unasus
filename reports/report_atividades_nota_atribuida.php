<?php

class report_atividades_nota_atribuida extends Factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = true;
        $this->mostrar_barra_filtragem = true;
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

    /**
     * @param $renderer report_unasus_renderer
     * @param $object
     * @param null $factory
     */
    public function render_report_table($renderer, $report) {
        $this->mostrar_barra_filtragem = false;
        echo $renderer->page_atividades_nao_avaliadas($report);
    }

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

                if($i == $n-2){
                    $data_header[] = 'Atividades Concluídas';
                }
            }
        }
        $data_header[] = 'N° Alunos com atividades concluídas';

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        foreach($dados as $d){
            $output = array_map("Factory::eliminate_html", $d);
            fputcsv($fp, $output);
        }
        fclose($fp);
    }

    public function get_dados(){
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
        $atividades_alunos_grupos = $this->get_dados_alunos_atividades_concluidas($associativo_atividade)->somatorio_grupos;

        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $aluno) {

                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    if (!array_key_exists($grupo_id, $somatorio_total_atrasos)) {
                        $somatorio_total_atrasos[$grupo_id] = 0;
                    }


                    if ($atividade->has_grade() && $atividade->is_grade_needed()) {

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

                    $total_atividades++;
                }
            }
        }

        //soma atividades concluidas
        $dados = array();
        $somatorio_total_alunos = 0;
        $somatorio_total_alunos_atividades_concluidas = 0;

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

            /* Coluna  N° Alunos com atividades concluídas */
            $somatorioalunosgrupos = isset($atividades_alunos_grupos[$grupo_id]) ? $atividades_alunos_grupos[$grupo_id] : 0;
            $data[] = new dado_media($somatorioalunosgrupos, $total_alunos[$grupo_id]);

            $dados[] = $data;
            $somatorio_total_alunos_atividades_concluidas += $somatorioalunosgrupos;
            $somatorio_total_alunos += $total_alunos[$grupo_id];
        }

        /* Linha total alunos com atividades concluidas  */
        $data_total = array(html_writer::tag('strong', 'Total alunos com atividade concluida / Total alunos'));
        $count = count($data) - 2;
        for ($i = 0; $i < $count; $i++) {
            $data_total[] = '';
        }
        $data_total[] = new dado_media($somatorio_total_alunos_atividades_concluidas, $somatorio_total_alunos);

        $dados[] = $data_total;
        return $dados;
    }

    public function get_table_header(){
        $header = $this->get_table_header_modulos_atividades(false, true);
        $header[''] = array(get_string('column_aluno_atividade_concluida', 'report_unasus'));
        return $header;
    }

    /**
     * Numero de Alunos que concluiram todas Atividades de um modulo,
     * e n° de alunos que concluiram todas atividades de um curso
     *
     * UTILIZADO PELO RELATÓRIO 'report_atividades_nota_atribuida'
     *
     * @param $associativo_atividade
     * @return \stdClass
     */
    function get_dados_alunos_atividades_concluidas($associativo_atividade) {
        $somatorio_total_modulo = array();
        $somatorio_total_grupo = array();

        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $dados_aluno) {
                $alunos_grupo[$grupo_id][] = new dado_atividades_nota_atribuida_alunos($dados_aluno);
            }
        }

        foreach ($alunos_grupo as $grupo_id => $alunos_por_grupo) {
            $somatorio_total_modulo[$grupo_id] = array();

            foreach ($alunos_por_grupo as $dados_aluno) {
                /** @var dado_atividades_nota_atribuida_alunos $dados_aluno */

                foreach ($this->modulos_selecionados as $course_id => $activities) {
                    // Inicializa o contador pra cada curso e pra cada grupo
                    if (!array_key_exists($course_id, $somatorio_total_modulo[$grupo_id])) {
                        $somatorio_total_modulo[$grupo_id][$course_id] = 0;
                    }

                    if ($dados_aluno->is_complete_activities($course_id)) {
                        $somatorio_total_modulo[$grupo_id][$course_id]++;
                    }
                }
                if (!array_key_exists($grupo_id, $somatorio_total_grupo)) {
                    $somatorio_total_grupo[$grupo_id] = 0;
                }
                if ($dados_aluno->is_complete_all_activities()) {
                    $somatorio_total_grupo[$grupo_id]++;
                }
            }
        }

        $somatorio = new stdClass();
        $somatorio->somatorio_modulos = $somatorio_total_modulo;
        $somatorio->somatorio_grupos = $somatorio_total_grupo;

        return $somatorio;
    }

}