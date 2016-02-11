<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_consolidado extends report_unasus_factory {

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

    public function render_report_csv($name_report) {

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=relatorio ' . $name_report . '.csv');
        readfile('php://output');

        $dados = $this->get_dados();
        $header = $this->get_table_header();

        $fp = fopen('php://output', 'w');

        $data_header = array('Orientadores');
        $data_header[1] = 'Resumo';
        $first_line = array();

        foreach ($header as $h) {

            if (isset($h[1]->course_name)) {
                $course_name = $h[1]->course_name;
                $first_line[] = $course_name;
            }
            $n = count($h);
            for ($i = 1; $i < $n+1; $i++) {
                if (isset($h[$i]->name)) {
                    $element = $h[$i]->name;
                    $data_header[] = $element;
                }

                if ($i == $n - 2) {
                    $data_header[] = 'Atividades Concluídas';
                    $data_header[] = 'Não Acessado';
                    $data_header[] = 'Avaliado';
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
        $result_array = loop_atividades_e_foruns_sintese(null, null, null, null, null, true);

        /* Retorno da função loop_atividades */
        $total_alunos = $result_array['total_alunos'];
        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];

        /* Variaveis totais do relatorio */
        $total_nao_acessadas = new report_unasus_dado_somatorio_grupo_render();
        $total_tcc_completo = new report_unasus_dado_somatorio_grupo_render();

        $total_abstract = new report_unasus_dado_somatorio_grupo_render();
        $total_chapter1 = new report_unasus_dado_somatorio_grupo_render();
        $total_chapter2 = new report_unasus_dado_somatorio_grupo_render();
        $total_chapter3 = new report_unasus_dado_somatorio_grupo_render();
        $total_chapter4 = new report_unasus_dado_somatorio_grupo_render();
        $total_chapter5 = new report_unasus_dado_somatorio_grupo_render();

        $total_avaliados = new report_unasus_dado_somatorio_grupo_render();

        /* Loop nas atividades para calcular os somatorios para sintese */
        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $aluno) {
                $bool_atividades = array();

                $bool_atividades[$grupo_id]['tcc_completo'] = 1;
                $bool_atividades[$grupo_id]['nao_acessado'] = 1;

                $status = $aluno[0]->status;

                if($aluno[0]->grade_tcc != null){
                    $total_avaliados->inc($grupo_id, 0);
                }

                foreach ($status as $chapter => $state) {

                    // Verifica se os capítulos foram avaliados, ou seja, estado 'done'
                    if ($aluno[0]->has_evaluated_chapters($chapter)) {
                        switch ($chapter) {
                            case 0:
                                $total_abstract->inc($grupo_id, $chapter);
                                $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                                break;
                            case 1:
                                $total_chapter1->inc($grupo_id, $chapter);
                                $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                                break;
                            case 2:
                                $total_chapter2->inc($grupo_id, $chapter);
                                $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                                break;
                            case 3:
                                $total_chapter3->inc($grupo_id, $chapter);
                                $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                                break;
                            case 4:
                                $total_chapter4->inc($grupo_id, $chapter);
                                $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                                break;
                            case 5:
                                $total_chapter5->inc($grupo_id, $chapter);
                                $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                                break;
                        }
                    } else {
                        $bool_atividades[$grupo_id]['tcc_completo'] = 0;

                        // Verifica se os capítulos foram submetidos para avaliação mas não avaliados, ou seja, estados 'review' e 'draft'.
                        if ($aluno[0]->has_submitted_chapters($chapter)) {
                            $bool_atividades[$grupo_id]['nao_acessado'] = 0;
                        }
                    }
                }

                foreach ($bool_atividades as $id => $bool_atividade) {
                    $total_tcc_completo->inc($grupo_id, $bool_atividade['tcc_completo']);
                    $total_nao_acessadas->inc($grupo_id, $id, $bool_atividades[$grupo_id]['nao_acessado']);
                }
            }
        }

        $dados = array();

        $total_atividades_concluidos = new report_unasus_dado_somatorio_grupo_render();
        $total_atividades_alunos = new report_unasus_dado_somatorio_grupo_render();

        foreach ($lista_atividade as $grupo_id => $grupo) {

            /* Coluna nome orientador */
            $data = array();
            $data[] = local_tutores_grupo_orientacao::grupo_orientacao_to_string($this->get_categoria_turma_ufsc(), $grupo_id);

            if (isset($total_alunos[$grupo_id])) {
                $lti['abstract'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_abstract->get($grupo_id)[0]);
                $lti['chapter1'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_chapter1->get($grupo_id)[1]);
                $lti['chapter2'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_chapter2->get($grupo_id)[2]);
                $lti['chapter3'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_chapter3->get($grupo_id)[3]);
                $lti['chapter4'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_chapter4->get($grupo_id)[4]);
                $lti['chapter5'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_chapter5->get($grupo_id)[5]);

                $lti['tcc'] = (!isset($total_tcc_completo->get($grupo_id)[1])) ? new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], 0)
                                                                               : new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_tcc_completo->get($grupo_id)[1]);

                $lti['acessado'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_nao_acessadas->get($grupo_id)[$grupo_id]);

                $lti['avaliado'] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], $total_avaliados->get($grupo_id)[0]);

                /* Preencher relatorio */
                foreach ($lti as $id => $dado_atividade) {

                    /* Coluna não acessado e concluído para cada modulo dentro do grupo */
                    if ($dado_atividade instanceof report_unasus_dado_atividades_alunos_render) {
                        $data[] = $dado_atividade;

                        $total_atividades_concluidos->add($grupo_id, $id, $dado_atividade->get_count());
                        $total_atividades_alunos->add($grupo_id, $id, $dado_atividade->get_total());
                    }
                }
            }

            $dados[] = $data;
        }

        /* Linha total alunos com atividades concluidas  */
        $data_total = array(new report_unasus_dado_texto_render(html_writer::tag('strong', 'Total por curso'), 'total'));
        $count_alunos = $total_atividades_alunos->get();
        $concluidos = $total_atividades_concluidos->get();

        foreach ($total_atividades_concluidos->get() as $ltiid => $lti) {
            foreach ($lti as $id => $count) {
                if(sizeof($data_total) == sizeof($lti)+1){
                    $data_total[$id]->set_total($data_total[$id]->get_total() + $count_alunos[$ltiid][$id]);
                    $data_total[$id]->set_count($data_total[$id]->get_count() + $concluidos[$ltiid][$id]);
                } else {
                    $data_total[$id] = new report_unasus_dado_atividades_alunos_render($count_alunos[$ltiid][$id], $count);
                }
            }
        }

        array_unshift($dados, $data_total);

        return $dados;
    }

    public function get_table_header() {
        $header = $this->get_table_header_tcc_atividades(true);

        foreach ($header as $key => $modulo) {
            array_unshift($modulo, 'Resumo');
            array_push($modulo, 'Atividades Concluídas');
            array_push($modulo, 'Não acessado');
            array_push($modulo, 'Avaliado');

            $header[$key] = $modulo;
        }

        return $header;
    }

} 