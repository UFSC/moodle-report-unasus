<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_concluido extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = false;

        // Filtro orientadores
        $this->mostrar_filtro_grupos_orientacao = true;
        $this->mostrar_filtro_orientadores = false;

        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = true;
        $this->mostrar_filtro_cohorts = true;
        $this->mostrar_filtro_modulos = true;
        $this->mostrar_filtro_intervalo_tempo = false;
        $this->mostrar_aviso_intervalo_tempo = false;
    }

    public function render_report_default($renderer) {
        echo $renderer->build_page();
    }

    public function render_report_table($renderer) {
        $this->mostrar_barra_filtragem = false;
        echo $renderer->build_report($this);
    }

    public function get_dados() {

        // Recupera dados auxiliares
        $modulos_ids = $this->get_modulos_ids();
        $atividades_config_curso = report_unasus_get_activities_config_report($this->get_categoria_turma_ufsc(), $modulos_ids);

        $nomes_estudantes = local_tutores_grupo_orientacao::get_estudantes($this->get_categoria_turma_ufsc());
        $this->atividades_cursos = report_unasus_get_atividades_cursos($this->modulos_selecionados, false, false, true);
        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */

        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(null, null, null, null, null, null, null, false, true);

        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();

            foreach ($array_dados as $id_aluno => $aluno) {
                // $aluno >> contém os dados do aluno para imprimir

                $lista_atividades[] = new report_unasus_student($nomes_estudantes[$id_aluno], $id_aluno, $this->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);
                foreach ($aluno as $atividade) {
                    // se a atividade está configurada para ser apresentada então...
                    if(array_search($atividade->source_activity->id, $atividades_config_curso) || empty($atividades_config_curso)){
                        if (is_a($atividade, 'report_unasus_data_empty')) {
                            $lista_atividades[] = new report_unasus_dado_nao_aplicado_render();
                            continue;
                        } else {
                            $status = $atividade->status;
                            foreach ($status as $chapter => $state) {
                                if ($atividade->has_evaluated_chapters($chapter))
                                    $tipo = report_unasus_dado_tcc_concluido_render::ATIVIDADE_CONCLUIDA;
                                else
                                    $tipo = report_unasus_dado_tcc_concluido_render::ATIVIDADE_NAO_CONCLUIDA;
                                $lista_atividades[] = new report_unasus_dado_tcc_concluido_render($tipo, $chapter);
                            }
                        }
                    }
                }
                $estudantes[] = $lista_atividades;
                $lista_atividades = null;
            }
            $dados[local_tutores_grupo_orientacao::grupo_orientacao_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] = $estudantes;
        }
        return ($dados);
    }

    public function get_table_header() {
//        $header = $this->get_table_header_tcc_atividades(true);
//
//        foreach ($header as $key => $modulo) {
//            array_unshift($modulo, 'Resumo');
//            $header[$key] = $modulo;
//        }
        $atividades_cursos = report_unasus_get_atividades_cursos($this->get_modulos_ids(), false, false, true, $this->get_categoria_turma_ufsc());

        $header = array();

        foreach ($atividades_cursos as $course_module_id => $atividades) {
            if (!empty($atividades)) {
                $course_url = new moodle_url('/course/view.php', array('id' => $atividades[0]->course_id, 'target' => '_blank'));
                $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));
                $atividades_curso_array = array();

                // passa por todas as $atividades do curso
                foreach ($atividades as $atividade) {

                    // se a $atividade for de TCC então
                    if (is_a($atividade, 'report_unasus_lti_activity_tcc')) {

                        // cria a atividade de resumo todo: buscar do Webservice
                        $resumo_atividade = new stdClass();

                        $resumo_atividade->id = 0;
                        $resumo_atividade->title = 'Resumo';
                        $resumo_atividade->position = 0;
                        $resumo_atividade->created_at = null;
                        $resumo_atividade->updated_at = null;

                        array_push($atividades_curso_array, new report_unasus_chapter_tcc_activity($resumo_atividade, $atividade));
                        // passa por todos os $capitulos do $tcc_definition e adiciona ao header
                        foreach ($atividade->tcc_definition->chapter_definitions as $capitulo_order => $chapter_definition) {
                            array_push($atividades_curso_array, new report_unasus_chapter_tcc_activity($chapter_definition->chapter_definition, $atividade));
                        }
                    }

                }
                $header[$course_link] = $atividades_curso_array;
            }
        }


        return $header;
    }

} 