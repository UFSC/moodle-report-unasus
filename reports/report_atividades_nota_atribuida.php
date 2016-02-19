<?php

defined('MOODLE_INTERNAL') || die;

class report_atividades_nota_atribuida extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_grupo_tutoria = true;
        $this->mostrar_filtro_tutores = false;
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
                    $data_header[] = 'Atividades Concluídas';
                }
            }
        }
        $data_header[] = 'N° Alunos com atividades concluídas';

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        foreach ($dados as $d) {
            $output = array_map("Factory::eliminate_html", $d);
            fputcsv($fp, $output);
        }
        fclose($fp);
    }

    public function get_dados() {
        $modulos_ids = $this->get_modulos_ids();

        $atividades_config_curso = report_unasus_get_activities_config_report($this->get_categoria_turma_ufsc(), $modulos_ids);

        // Consulta
        $query_atividades   = query_atividades_from_users();
        $query_quiz         = query_quiz_from_users();
        $query_forum        = query_postagens_forum_from_users();
        $query_database     = query_database_adjusted_from_users();
        $query_scorm        = query_scorm_from_users();
        $query_lti          = query_lti_from_users();

        $result_array = loop_atividades_e_foruns_sintese($query_atividades, $query_forum, $query_quiz, $query_lti,
            null, false, $query_database, $query_scorm, $atividades_config_curso);

        $total_alunos = $result_array['total_alunos'];
        $total_atividades = $result_array['total_atividades'];
        $lista_atividade = $result_array['lista_atividade'];

        $associativo_atividade = $result_array['associativo_atividade'];

        $atividades_alunos = $this->get_dados_alunos_atividades_concluidas($associativo_atividade);

        // Total do modulo
        $atividades_alunos_modulos = $atividades_alunos->somatorio_modulos;

        // Total do grupo (tutor ou polo)
        $atividades_alunos_grupos = $atividades_alunos->somatorio_grupos;

        // passa pelos dados de cada atividade, de cada aluno e agrupado (polo ou tutor)
        foreach ($associativo_atividade as $grupo_id => $array_dados) {

            // passa pelos alunos do grupo
            foreach ($array_dados as $aluno_id => $aluno_activities) {
                $courseid = '';
                $progress = null;

                // passa por todas as atividades de cada aluno
                foreach ($aluno_activities as $atividade) {

                    if (isset($atividade) &&
                        $courseid != $atividade->source_activity->course_id) {

                        $courseid = $atividade->source_activity->course_id;

                        $course_instance = get_course($courseid);
                        $info = new completion_info($course_instance);
                        $uid = 'u.id=' . $aluno_id;
                        $progress = $info->get_progress_all($uid);

                    }

                    //Se não houver dados para o aluno, ele não faz aquele módulo
                    if ( isset($progress) &&
                        isset($progress[$aluno_id]) ) {

                        // passa por todas as atividades configuradas daquele módulo
                            $pendente = true;
                            $has_progress = isset($progress[$aluno_id]->progress[$atividade->source_activity->coursemoduleid]);
                            if ( $has_progress ) {
                                $pendente = $progress[$aluno_id]->progress[$atividade->source_activity->coursemoduleid]->completionstate == COMPLETION_INCOMPLETE;
                            }
                    } else {
                        // se não achou a completude, então pega a regra geral, se a nota foi dada quando precisar de nota
                        $pendente = ($atividade->has_grade() && $atividade->is_grade_needed());
                    }

                    if (!$pendente) {

                        /** @var report_unasus_dado_atividades_alunos_render $dado * */
                        unset($dado); // estamos trabalhando com ponteiro, não podemos atribuir null ou alteramos o array.

                        if (is_a($atividade, 'report_unasus_data_activity')) {
                            $dado =& $lista_atividade[$grupo_id]['atividade_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_forum')) {
                            $dado =& $lista_atividade[$grupo_id]['forum_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_quiz')) {
                            $dado =& $lista_atividade[$grupo_id]['quiz_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_db')) {
                            $dado =& $lista_atividade[$grupo_id]['database_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_scorm')) {
                            $dado =& $lista_atividade[$grupo_id]['scorm_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_lti')) {
                            $dado =& $lista_atividade[$grupo_id]['lti_' . $atividade->source_activity->id];

                        } elseif (is_a($atividade, 'report_unasus_data_lti_tcc')) {
                            $dado =& $lista_atividade[$grupo_id][$atividade->source_activity->id][$atividade->source_activity->position];
                        }
                        if (!empty($dado)) {
                            $dado->incrementar();
                        };
                    }

                    $total_atividades++;
                } // para cada atividade do estudante
            } // para cada estudante
        } // para cada grupo de tutoria

        //soma atividades concluidas
        $dados = array();
        $somatorio_total_alunos = 0;
        $somatorio_total_alunos_atividades_concluidas = 0;

        foreach ($lista_atividade as $grupo_id => $grupo) {
            $data = array();
            $data[] = local_tutores_grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo_id);

            foreach ($grupo as $key => $atividades) {
                $initial_key = substr($key, 0, 6);

                // Array contêm os capítulos do TCC
                if (is_array($atividades)) {
                    $data[] = $atividade;
                    break;
                } else {
                    // se for uma coluna de totalização do módulo,
                        // então pega os dados da totalização
                    if($initial_key == 'modulo') {
                        $key_value = substr($key, 7);
                        $atividades->set_count($atividades_alunos_modulos[$grupo_id][$key_value]);
                    }
                    $atividade = $atividades;
                    $data[] = $atividades;
                }
            }

            $data_sliced = array_slice($data, 1);

            foreach ($data_sliced as $activity) {
                $somatorio_total_alunos_atividades_concluidas_modulo[$grupo_id][] = empty($activity) ? 0 : $activity->get_count();
            }

            /* Coluna  total de Alunos com atividades concluídas */
            $somatorioalunosgrupos = isset($atividades_alunos_grupos[$grupo_id]) ? $atividades_alunos_grupos[$grupo_id] : 0;
            $data[] = new report_unasus_dado_media_render($somatorioalunosgrupos, $total_alunos[$grupo_id]);

            $dados[] = $data;
            $somatorio_total_alunos_atividades_concluidas += $somatorioalunosgrupos;
            $somatorio_total_alunos += $total_alunos[$grupo_id];
        } // para cada grupo de tutoria

        //////////////////////////////

        /* Linha total alunos com atividades concluidas  */
        $data_total = array(html_writer::tag('strong', 'Total alunos com atividade concluida / Total alunos'));

        // soma o total de alunos com atividades concluídas por atividade
        $total_activities_modulo[] = 0;
        foreach ($somatorio_total_alunos_atividades_concluidas_modulo as $modulos) {
            $count = sizeof($modulos);
            for($i = 0; $i < $count; $i++){
                if(!isset($total_activities_modulo[$i])){
                    $total_activities_modulo[$i] = $modulos[$i];
                } else {
                    $total_activities_modulo[$i] += $modulos[$i];
                }
            }
        }

        $num_columns = sizeof($total_activities_modulo);

        /* Linha de totalização das atividades */
        /* Colunas de somatório das atividades dos módulos */
        for($i = 0; $i < $num_columns; $i++){
            $data_total[] = new report_unasus_dado_media_render($total_activities_modulo[$i], $somatorio_total_alunos);
        }

        // adiciona o total geral de alunos com atividades concluídas
        $data_total[] = new report_unasus_dado_media_render($somatorio_total_alunos_atividades_concluidas, $somatorio_total_alunos);

        $dados[] = $data_total;

        return $dados;
    }

    public function get_table_header() {
        $header = $this->get_table_header_modulos_atividades(false, true);
        $htitle = get_string('column_aluno_atividade_concluida', 'report_unasus');
        $header[$htitle] = array('Total');
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
        $alunos_grupo = array();

        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $dados_aluno) {
                $alunos_grupo[$grupo_id][] = new report_unasus_dado_atividades_nota_atribuida_alunos_render($dados_aluno);
            }
        }

        foreach ($alunos_grupo as $grupo_id => $alunos_por_grupo) {
            $somatorio_total_modulo[$grupo_id] = array();

            foreach ($alunos_por_grupo as $dados_aluno) {
                /** @var report_unasus_dado_atividades_nota_atribuida_alunos_render $dados_aluno */

                foreach ($this->atividades_cursos as $course_id => $activities) {
                    // Inicializa o contador pra cada curso e pra cada grupo
                    if (!array_key_exists($course_id, $somatorio_total_modulo[$grupo_id])) {
                        $somatorio_total_modulo[$grupo_id][$course_id] = 0;
                    }

                    // Se todas as atividades do aluno estão completas, no módulo atual,
                    //   então incrementa total do módulo (curso)
                    if ($dados_aluno->is_complete_activities($course_id)) {
                        $somatorio_total_modulo[$grupo_id][$course_id]++;
                    }
                }
                if (!array_key_exists($grupo_id, $somatorio_total_grupo)) {
                    $somatorio_total_grupo[$grupo_id] = 0;
                }

                // Se todas as atividades do aluno estão completas, em todos os módulos,
                //   então incrementa total do grupo (tutor ou polo)
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