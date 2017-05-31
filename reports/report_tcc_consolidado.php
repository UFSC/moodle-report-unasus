<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_consolidado extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = false;

        // Filtro orientadores
        $this->mostrar_filtro_grupos_orientacao = true;
        $this->mostrar_filtro_orientadores = false;

        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = true && report_unasus_verifica_middleware();
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
        $modulos_ids = $this->get_modulos_ids();

        $this->visiveis_atividades_cursos = report_unasus_get_atividades_cursos(
            $modulos_ids,
            false,
            false,
            true
        );

        $result_array = loop_atividades_e_foruns_sintese(null, null, null, null, null, true);

        /* Retorno da função loop_atividades */
        $total_alunos = $result_array['total_alunos'];

        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];

        /* Variaveis totais do relatorio */
        $total_nao_acessado = new report_unasus_dado_somatorio_grupo_lti_render();
        $total_tcc_completo = new report_unasus_dado_somatorio_grupo_lti_render();

        /* Loop nas atividades para calcular os somatorios para sintese */
        foreach ($associativo_atividade as $grupo_id => $array_dados) {


            // $grupo_id >> contém o grupo de orientação
            // $array_dados >> contém a lista de alunos
            foreach ($array_dados as $aluno_id => $aluno_ltis) {
                $bool_atividades = array();

                // $aluno_ltis >> contém os dados (atividades) do aluno para imprimir
                // $atividade >>contém cada atividade de LTI
                foreach ($aluno_ltis as $atividade) {

                    // se a atividade de LTI está configurada para ser apresentada então...
//                    if (array_search($atividade->source_activity->id, $atividades_config_curso) || empty($atividades_config_curso)) {

                        if (is_a($atividade, 'report_unasus_data_empty')) {
                            $lista_atividades[] = new report_unasus_dado_nao_aplicado_render();
                            continue;
                        } else {

                            if (!isset($lista_atividade[$grupo_id][$atividade->source_activity->id]['tcc_completo'])) {
                                $lista_atividade[$grupo_id][$atividade->source_activity->id]['tcc_completo'] =
                                    new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], 0);
                            }
                            if (!isset($lista_atividade[$grupo_id][$atividade->source_activity->id]['nao_acessado'])) {
                                $lista_atividade[$grupo_id][$atividade->source_activity->id]['nao_acessado'] =
                                    new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], 0);
                            }
                            if (!isset($lista_atividade[$grupo_id][$atividade->source_activity->id]['avaliados'])) {
                                $lista_atividade[$grupo_id][$atividade->source_activity->id]['avaliados'] =
                                    new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id], 0);
                            }
                            $bool_atividades[$grupo_id][$atividade->source_activity->id]['tcc_completo'] = 1;
                            $bool_atividades[$grupo_id][$atividade->source_activity->id]['nao_acessado'] = 1;

                            // $status >> contém a lista de capítulos do TCC (resumo + capítulos)

                            $status = $atividade->status;

                            if ($atividade->grade_tcc != null) {
                                $lista_atividade[$grupo_id][$atividade->source_activity->id]['avaliados']->incrementar();
                            }

                            foreach ($status as $chapter => $state) {

                                // Verifica se os capítulos foram avaliados, ou seja, estado 'done'
                                if ($atividade->has_evaluated_chapters($chapter)) {

                                    // TODO: Ao invés de usar o switch iterar sobre o $status e colocar num array
                                    $lista_atividade[$grupo_id][$atividade->source_activity->id][$chapter]->incrementar();
                                    $bool_atividades[$grupo_id][$atividade->source_activity->id]['nao_acessado'] = 0;
                                } else {
                                    $bool_atividades[$grupo_id][$atividade->source_activity->id]['tcc_completo'] = 0;

                                    // Verifica se os capítulos foram submetidos para avaliação mas não avaliados, ou seja, estados 'review' e 'draft'.
                                    if ($atividade->has_submitted_chapters($chapter)) {
                                        $bool_atividades[$grupo_id][$atividade->source_activity->id]['nao_acessado'] = 0;
                                    }
                                }
                            }
                        }

//                    } // se é para apresentar a atividade

                } // para cada atividade

                // percorre o totalizador das atividades para totalizar o grupo
                foreach ($bool_atividades as $bool_group_id => $bool_ltis) {
                    foreach ($bool_ltis as $bool_lti_id => $count_chapter) {
                        $total_tcc_completo->inc($bool_group_id, $bool_lti_id, $count_chapter['tcc_completo']);
                        $total_nao_acessado->inc($bool_group_id, $bool_lti_id, $count_chapter['nao_acessado']);

                    }
                }

            } //para cada aluno

            if (is_array($total_tcc_completo->get($grupo_id))) {
                // calcula total de tccs completos para o grupo de orientação
                foreach ($total_tcc_completo->get($grupo_id) as $t_lti_id => $t_valores) {
                    $count_group = isset($t_valores[1]) ? $t_valores[1] : 0;
                    $lista_atividade[$grupo_id][$t_lti_id]['tcc_completo']->set_count(
                        $lista_atividade[$grupo_id][$t_lti_id]['tcc_completo']->get_count() +
                        $count_group);
                };
            }

            if (is_array($total_nao_acessado->get($grupo_id))) {
                // calcula total de tccs não acessados para o grupo de orientação
                foreach ($total_nao_acessado->get($grupo_id) as $t_lti_id => $t_valores) {
                    $count_group = isset($t_valores[1]) ? $t_valores[1] : 0;
                    $lista_atividade[$grupo_id][$t_lti_id]['nao_acessado']->set_count(
                        $lista_atividade[$grupo_id][$t_lti_id]['nao_acessado']->get_count() +
                        $count_group);
                };
            }

        } //para cada orientador

        $dados = array();

        $total_atividades_concluidos_lti = new report_unasus_dado_somatorio_grupo_lti_render();
        $total_atividades_alunos_lti = new report_unasus_dado_somatorio_grupo_lti_render();

        foreach ($lista_atividade as $grupo_id => $grupo) {

            /* Coluna nome orientador */
            $data = array();
            $data[] = local_tutores_grupo_orientacao::grupo_orientacao_to_string($this->get_categoria_turma_ufsc(), $grupo_id);

            foreach ($grupo as $lti_id => $total_chapter) {
//                $bool_lti_print =  (array_search($lti_id, $atividades_config_curso) || empty($atividades_config_curso));
                $bool_lti_print = true;
                $lti = array();
                if (isset($total_alunos[$grupo_id]) && $bool_lti_print) {

                    foreach ($total_chapter as $chapter_id => $count) {
                        $lti[$chapter_id] = new report_unasus_dado_atividades_alunos_render($total_alunos[$grupo_id],
                            $lista_atividade[$grupo_id][$lti_id][$chapter_id]->get_count() );
                    }

                    /* Preencher relatorio */
                    foreach ($lti as $id => $dado_atividade) {

                        /* Coluna não acessado e concluído para cada modulo dentro do grupo */
                        if ($dado_atividade instanceof report_unasus_dado_atividades_alunos_render) {
                            $data[] = $dado_atividade;

                            $total_atividades_concluidos_lti->add($grupo_id, $lti_id, $id, $dado_atividade->get_count());
                            $total_atividades_alunos_lti->add($grupo_id, $lti_id ,$id, $dado_atividade->get_total());

                        }
                    }
                }
            } // passa por cada atividade de TCC
            $dados[] = $data;

        } // passa pela $lista_atividade, para cada gruop de orientação

        /* Linha total alunos com atividades concluidas  */
        $data_total = array(new report_unasus_dado_texto_render(html_writer::tag('strong', 'Total por curso'), 'total'));
        $count_alunos_lti = $total_atividades_alunos_lti->get();
        $concluidos_lti = $total_atividades_concluidos_lti->get();

        foreach ($concluidos_lti as $grupo_id => $ltis) {
            foreach ($ltis as $lti_id => $total_chapter) {
//                $bool_lti_print =  (array_search($lti_id, $atividades_config_curso) || empty($atividades_config_curso));
                $bool_lti_print = true;
                if ($bool_lti_print) {
                    foreach ($total_chapter as $chapter_id => $count) {
                        if(isset($data_total[$lti_id.'-'.$chapter_id])) {
                            $data_total[$lti_id.'-'.$chapter_id]->set_total($data_total[$lti_id.'-'.$chapter_id]->get_total() +
                                $count_alunos_lti[$grupo_id][$lti_id][$chapter_id]);
                            $data_total[$lti_id.'-'.$chapter_id]->set_count($data_total[$lti_id.'-'.$chapter_id]->get_count() +
                                $concluidos_lti[$grupo_id][$lti_id][$chapter_id]);
                        } else {
                            $data_total[$lti_id.'-'.$chapter_id] = new report_unasus_dado_atividades_alunos_render($count_alunos_lti[$grupo_id][$lti_id][$chapter_id], $count);
                        }
                    }
                }
            }
        }

        array_unshift($dados, $data_total);

        return $dados;
    }

    private function array_push_header(&$atividades_curso_array, &$atividade, $title, $position = null) {
        // cria a atividade de resumo todo: buscar do Webservice
        $resumo_atividade = new stdClass();

        $resumo_atividade->id = null;
        $resumo_atividade->title = $title;
        $resumo_atividade->position = $position;
        $resumo_atividade->created_at = null;
        $resumo_atividade->updated_at = null;

        array_push($atividades_curso_array, new report_unasus_chapter_tcc_activity($resumo_atividade, $atividade));

    }

    public function get_table_header() {

        $header = array();

        foreach ($this->visiveis_atividades_cursos as $course_module_id => $atividades) {
            if (!empty($atividades)) {
                $course_url = new moodle_url('/course/view.php', array('id' => $atividades[0]->course_id, 'target' => '_blank'));
                $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));
                $atividades_curso_array = array();

                // passa por todas as $atividades do curso
                foreach ($atividades as $atividade) {

                    // se a $atividade for de TCC então
                    if (is_a($atividade, 'report_unasus_lti_activity_tcc')) {

                        $this->array_push_header($atividades_curso_array, $atividade, 'Resumo');

                        // passa por todos os $capitulos do $tcc_definition e adiciona ao header
                        foreach ($atividade->tcc_definition->chapter_definitions as $capitulo_order => $chapter_definition) {
                            array_push($atividades_curso_array, new report_unasus_chapter_tcc_activity($chapter_definition->chapter_definition, $atividade));
                        }

                        $this->array_push_header($atividades_curso_array, $atividade, 'Atividades Concluídas');
                        $this->array_push_header($atividades_curso_array, $atividade, 'Não acessado');
                        $this->array_push_header($atividades_curso_array, $atividade, 'Avaliado');
                    }

                }
                $header[$course_link] = $atividades_curso_array;
            }
        }


        return $header;
    }

} 