<?php

class report_tcc_portfolio_consolidado extends Factory {

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

        $data_header = array('Tutores');
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
        $result_array = loop_atividades_e_foruns_sintese(null, null, null);

        /* Retorno da função loop_atividades */
        $total_alunos = $result_array['total_alunos'];
        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];

        /* Variaveis totais do relatorio */
        $total_nao_acessadas = new dado_somatorio_grupo();
        $total_tcc_completo = new dado_somatorio_grupo();

        /* Loop nas atividades para calcular os somatorios para sintese */
        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $aluno) {
                $bool_atividades = array();

                foreach ($aluno as $dado_atividade) {
                    /** @var report_unasus_data_lti $dado_atividade */
                    $id = $dado_atividade->source_activity->id;
                    if (!array_key_exists($id, $bool_atividades)) {
                        $bool_atividades[$id]['tcc_completo'] = true;
                        $bool_atividades[$id]['nao_acessado'] = true;
                        $bool_atividades[$id]['has_activity'] = false;
                    }

                    // Não se aplica para este estudante
                    if ($dado_atividade instanceof report_unasus_data_empty) {
                        continue;
                    }
                    $bool_atividades[$id]['has_activity'] = true;

                    /* Verificar se atividade foi avaliada */
                    if ($dado_atividade->has_evaluated()) {
                        if ($dado_atividade instanceof report_unasus_data_lti) {
                            /** @var dado_atividades_alunos $dado */

                            $dado =& $lista_atividade[$grupo_id][$id][$dado_atividade->source_activity->position];
                            $dado->incrementar();
                        }
                    } else {
                        /* Atividade nao completa entao tcc nao esta completo */
                        $bool_atividades[$id]['tcc_completo'] = false;
                    }

                    /* Verificar não acessado */
                    if ($dado_atividade->status != 'new') {
                        $bool_atividades[$id]['nao_acessado'] = false;
                    }
                }
                foreach ($bool_atividades as $id => $bool_atividade) {
                    $total_tcc_completo->inc($grupo_id, $id, $bool_atividade['has_activity'] && $bool_atividade['tcc_completo']);
                    $total_nao_acessadas->inc($grupo_id, $id, $bool_atividade['has_activity'] && $bool_atividade['nao_acessado']);
                }
            }
        }

        $dados = array();
        $total_atividades_concluidos = new dado_somatorio_grupo();
        $total_atividades_alunos = new dado_somatorio_grupo();

        foreach ($lista_atividade as $grupo_id => $grupo) {
            /* Coluna nome grupo tutoria */
            $data = array();
            $data[] = grupos_tutoria::grupo_tutoria_to_string($this->get_curso_ufsc(), $grupo_id);

            /* Grupo vazio, imprimir apenas o nome do tutor */
            if (empty($grupo)) {
                $dados[] = $data;
                continue;
            }

            foreach ($grupo as $ltiid => $lti) {
                /* Inserir mais 2 colunas de atividades no array do grupo para ser preenchido no foreach do lti */
                $lti['acessado'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_nao_acessadas->get($grupo_id, $ltiid));
                $lti['tcc'] = new dado_atividades_alunos($total_alunos[$grupo_id], $total_tcc_completo->get($grupo_id, $ltiid));

                /* Preencher relatorio */
                foreach ($lti as $id => $dado_atividade) {
                    /* Coluna não acessado e tcc para cada modulo dentro do grupo */
                    if ($dado_atividade instanceof dado_atividades_alunos) {
                        $data[] = $dado_atividade;

                        $total_atividades_concluidos->add($ltiid, $id, $dado_atividade->get_count());
                        $total_atividades_alunos->add($ltiid, $id, $dado_atividade->get_total());
                    }
                }
            }
            $dados[] = $data;
        }
        /* Linha total alunos com atividades concluidas  */
        $data_total = array(new dado_texto(html_writer::tag('strong', 'Total por curso'), 'total'));
        $count_alunos = $total_atividades_alunos->get();

        foreach ($total_atividades_concluidos->get() as $ltiid => $lti) {
            foreach ($lti as $id => $count) {
                $data_total[] = new dado_atividades_total($count_alunos[$ltiid][$id], $count);
            }
        }
        array_unshift($dados, $data_total);

        return $dados;
    }

    public function get_table_header() {
        $header = $this->get_table_header_tcc_portfolio_entrega_atividades();

        foreach ($header as $key => $modulo) {
            array_push($modulo, 'Não acessado');
            array_push($modulo, 'Concluído');

            $header[$key] = $modulo;
        }

        return $header;

    }


}