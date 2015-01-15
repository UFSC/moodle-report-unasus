<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_consolidado extends Factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = false;

        // Filtro orientadores
        $this->mostrar_filtro_orientadores = true;

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

    public function render_report_default($renderer) {
        echo $renderer->build_page();
    }

    public function render_report_table($renderer) {
        $this->mostrar_barra_filtragem = false;
        echo $renderer->page_avaliacoes_em_atraso($this);
    }

    #fixme: Ajustar para o novo TCC
    public function render_report_csv($name_report) {

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=relatorio ' . $name_report . '.csv');
        readfile('php://output');

        $dados = $this->get_dados();
        $header = $this->get_table_header();

        $fp = fopen('php://output', 'w');

        $data_header = array('Orientadores');
        $first_line = array('');

        foreach ($header as $h) {

            if (isset($h[0]->course_name)) {
                $course_name = $h[0]->course_name;
                $first_line[] = $course_name;
            }
            $n = count($h);
            for ($i = 0; $i < $n; $i++) {
                if (isset($h[$i]->name)) {
                    $element = $h[$i]->name;
                    $data_header[] = $element;
                }
                //Insere o nome do módulo na célula acima da primeira atividade daquele módulo
                if ($i < $n - 1) {
                    $first_line[] = '';
                } else
                    continue;

                if ($i == $n - 2) {
                    $data_header[] = 'Não Acessado';
                    $data_header[] = 'Concluído';
                    $data_header[] = 'Resumo';
                    $data_header[] = 'Introdução';
                    $data_header[] = 'Considerações Finais';
                }
            }
        }

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        foreach ($dados as $d) {
            $output = array_map("Factory::eliminate_html", $d);
            fputcsv($fp, $output);
        }
        fclose($fp);
    }

    public function get_dados() {
        /* Resultados */
        $result_array = loop_atividades_e_foruns_sintese(null, null, null, null, true);

        /* Retorno da função loop_atividades */
        $total_alunos = $result_array['total_alunos'];
        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];

        /* Variaveis totais do relatorio */
        $total_nao_acessadas = new dado_somatorio_grupo();
        $total_tcc_completo = new dado_somatorio_grupo();

        $total_abstract = new dado_somatorio_grupo();
        $total_chapter1 = new dado_somatorio_grupo();
        $total_chapter2 = new dado_somatorio_grupo();
        $total_chapter3 = new dado_somatorio_grupo();
        $total_chapter4 = new dado_somatorio_grupo();
        $total_chapter5 = new dado_somatorio_grupo();

        /* Loop nas atividades para calcular os somatorios para sintese */
        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $aluno) {
                $bool_atividades = array();

                $chapter = 'chapter1';

                $bool_atividades[$grupo_id]['tcc_completo'] = 1;
                $bool_atividades[$grupo_id]['nao_acessado'] = 1;

                $j = 0;

                for ($i = 0; $i <= 4; $i++) {

                    if($j == 0){
                        if ($aluno[$j]->has_evaluated_chapters('abstract')){
                            $total_abstract->inc($grupo_id, $aluno[$i]->source_activity->position);
//                            $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                        } else {
                            $bool_atividades[$grupo_id]['tcc_completo'] = 0;

                            if($aluno[$j]->has_submitted()){
                                $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                            }
                        }
                        $j+=5;
                    }

                    if ($aluno[$i]->has_evaluated_chapters($chapter)) {
//                        $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                        switch ($chapter) {
                            case 'chapter1':
                                $total_chapter1->inc($grupo_id, $aluno[$i]->source_activity->position);
                                break;
                            case 'chapter2':
                                $total_chapter2->inc($grupo_id, $aluno[$i]->source_activity->position);
                                break;
                            case 'chapter3':
                                $total_chapter3->inc($grupo_id, $aluno[$i]->source_activity->position);
                                break;
                            case 'chapter4':
                                $total_chapter4->inc($grupo_id, $aluno[$i]->source_activity->position);
                                break;
                            case 'chapter5':
                                $total_chapter5->inc($grupo_id, $aluno[$i]->source_activity->position);
                                break;
                        }
                    } else {
                        $bool_atividades[$grupo_id]['tcc_completo'] = 0;

                        if($aluno[$i]->has_submitted($chapter)){
                            $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                        }
                    }

                    if($chapter == 'chapter1'){
                        $chapter = 'chapter2';
                    } else if ($chapter == 'chapter2'){
                        $chapter = 'chapter3';
                    } else if ($chapter == 'chapter3'){
                        $chapter = 'chapter4';
                    } else
                        $chapter = 'chapter5';
                }

                foreach ($bool_atividades as $id => $bool_atividade) {
                    $total_tcc_completo->inc($grupo_id, $bool_atividade['tcc_completo']);
                    $total_nao_acessadas->inc($grupo_id, $id, $bool_atividades[$grupo_id]['nao_acessado']);
                }
            }
        }

        $dados = array();

        $total_atividades_concluidos = new dado_somatorio_grupo();
        $total_atividades_alunos = new dado_somatorio_grupo();

        foreach ($lista_atividade as $grupo_id => $grupo) {

            /* Coluna nome orientador */
            $data = array();
            $data[] = grupo_orientacao::grupo_orientacao_to_string($this->get_categoria_turma_ufsc(), $grupo_id);

            if (isset($total_alunos[$grupo_id])) {
                $lti['abstract'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_abstract->get($grupo_id)[1]);
                $lti['chapter1'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_chapter1->get($grupo_id)[1]);
                $lti['chapter2'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_chapter2->get($grupo_id)[1]);
                $lti['chapter3'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_chapter3->get($grupo_id)[1]);
                $lti['chapter4'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_chapter4->get($grupo_id)[1]);
                $lti['chapter5'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_chapter5->get($grupo_id)[1]);

                $lti['tcc'] = (!isset($total_tcc_completo->get($grupo_id)[1])) ? new dado_atividades_alunos($total_alunos[$grupo_id], 0)
                                                                               : new dado_atividades_alunos($total_alunos[$grupo_id], $total_tcc_completo->get($grupo_id)[1]);

                $lti['acessado'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_nao_acessadas->get($grupo_id)[$grupo_id]);

                $i = 2;
                /* Preencher relatorio */
                foreach ($lti as $id => $dado_atividade) {

                    /* Coluna não acessado e concluído para cada modulo dentro do grupo */
                    if ($dado_atividade instanceof dado_atividades_alunos) {
                        $data[$i] = $dado_atividade;

                        $total_atividades_concluidos->add($grupo_id, $id, $dado_atividade->get_count());
                        $total_atividades_alunos->add($grupo_id, $id, $dado_atividade->get_total());
                    }
                    $i++;
                }
            }

            $dados[] = $data;
        }

        /* Linha total alunos com atividades concluidas  */
        $data_total = array(new dado_texto(html_writer::tag('strong', 'Total por curso'), 'total'));
        $count_alunos = $total_atividades_alunos->get();
        $concluidos = $total_atividades_concluidos->get();

        foreach ($total_atividades_concluidos->get() as $ltiid => $lti) {
            foreach ($lti as $id => $count) {
                if(sizeof($data_total) == sizeof($lti)+1){
                    $data_total[$id]->set_total($data_total[$id]->get_total() + $count_alunos[$ltiid][$id]);
                    $data_total[$id]->set_count($data_total[$id]->get_count() + $concluidos[$ltiid][$id]);
                } else {
                    $data_total[$id] = new dado_atividades_alunos($count_alunos[$ltiid][$id], $count);
                }
            }
        }

        array_unshift($dados, $data_total);

        return $dados;
    }

    public function get_table_header() {
        $header = $this->get_table_header_tcc_portfolio_entrega_atividades(true);

        foreach ($header as $key => $modulo) {
            array_unshift($modulo, 'Resumo');
            array_push($modulo, 'Concluído');
            array_push($modulo, 'Não acessado');

            $header[$key] = $modulo;
        }

        return $header;
    }

} 