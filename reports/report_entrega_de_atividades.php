<?php

defined('MOODLE_INTERNAL') || die;

class report_entrega_de_atividades extends Factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = true;
        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = true;
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

    public function render_report_graph($renderer, $porcentagem) {
        $this->mostrar_barra_filtragem = false;
        echo $renderer->build_graph($this, $porcentagem);
    }

    public function get_dados_grafico() {
        global $CFG;

        // Consultas
        $query_atividades = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();

        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(
                $query_atividades, $query_forum, $query_quiz);


        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            //variáveis soltas para melhor entendimento
            $count_entregue_no_prazo = 0;
            $count_pouco_atraso = 0;
            $count_muito_atraso = 0;
            $count_nao_entregue_mas_no_prazo = 0;
            $count_sem_prazo = 0;
            $count_nao_entregue_fora_prazo = 0;


            foreach ($array_dados as $id_aluno => $aluno) {

                foreach ($aluno as $atividade) {
                    $atraso = null;

                    // Não se aplica para este estudante
                    if (is_a($atividade, 'report_unasus_data_empty')) {
                        continue;
                    }

                    //Se atividade não tem data de entrega e nem nota
                    if (!$atividade->source_activity->has_deadline() && !$atividade->has_grade()) {
                        $count_sem_prazo++;
                    } else {

                        //Entrega atrasada
                        if ($atividade->is_submission_due()) {

                            if ($atividade->is_a_future_due()) {
                                //atividade com data de entrega no futuro, nao entregue mas dentro do prazo
                                $count_nao_entregue_mas_no_prazo++;
                            } else {
                                // Atividade nao entregue e atrasada
                                $count_nao_entregue_fora_prazo++;
                            }
                        }

                        $atraso = $atividade->submission_due_days();
                        if ($atraso) {
                            ($atraso > $CFG->report_unasus_prazo_maximo_avaliacao) ? $count_muito_atraso++ : $count_pouco_atraso++;
                        } else {
                            $count_entregue_no_prazo++;
                        }

                        //Offlines nao precisam de entrega
                        if (!$atividade->source_activity->has_submission()) {
                            $count_nao_entregue_mas_no_prazo++;
                        }
                    }
                }
            }
            $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] =
                    array($count_nao_entregue_mas_no_prazo,
                            $count_nao_entregue_fora_prazo,
                            $count_sem_prazo,
                            $count_entregue_no_prazo,
                            $count_pouco_atraso,
                            $count_muito_atraso,
                    );
        }

        return ($dados);
    }

    public function get_dados() {

        // Recupera dados auxiliares
        $nomes_estudantes = grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $grupos = grupos_tutoria::get_grupos_tutoria($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        $dados = array();

        // Para cada grupo de tutoria
        foreach ($grupos as $grupo) {
            $estudantes = array();
            foreach ($this->atividades_cursos as $courseid => $atividades) {
                foreach ($atividades as $atividade) {
                    $result = get_atividades(get_class($atividade), $atividade, $courseid, $grupo, $this);

                    foreach ($result as $r){

                        switch ($r->name_activity){
                            case 'assign_activity':
                                $data = new report_unasus_data_activity($atividade, $r);
                                break;
                            case 'forum_activity':
                                $data = new report_unasus_data_forum($atividade, $r);
                                break;
                            case 'quiz_activity':
                                $data = new report_unasus_data_quiz($atividade, $r);
                                break;
                        }

                        // Evita que o objeto do estudante seja criado em toda iteração do loop
                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                        $atraso = null;

                        // Se a atividade não foi entregue
                        if (!$data->has_submitted()) {

                            if (!$data->source_activity->has_deadline()) {
                                // E não tem entrega prazo
                                $tipo = dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA;
                            } elseif ($data->is_a_future_due()) {
                                //atividade com data de entrega no futuro, nao entregue mas dentro do prazo
                                $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_MAS_NO_PRAZO;
                            } else {
                                // Atividade nao entregue e atrasada
                                $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_FORA_DO_PRAZO;
                            }
                        } else {

                            // Entrega atrasada
                            if ($data->is_submission_due()) {
                                $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                            } else {
                                $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO;
                            }

                            $atraso = $data->submission_due_days();
                        }
                        $lista_atividades[$r->userid][$atividade->id] = new dado_entrega_de_atividades($tipo, $atividade->id, $atraso);
                    }

                    // Auxiliar para agrupar tutores corretamente
                    if(!empty($lista_atividades)){
                        $estudantes = $lista_atividades;
                    }
                }
            }

            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo->id)] = $estudantes;
            }

            $lista_atividades = null;
        }

        return $dados;
    }

    public function get_table_header($mostrar_nota_final = false, $mostrar_total = false) {
        $atividades_cursos = get_atividades_cursos($this->get_modulos_ids(), $mostrar_nota_final, $mostrar_total, false, true);
        $header = array();

        foreach ($atividades_cursos as $course_id => $atividades) {
            $course_url = new moodle_url('/course/view.php', array('id' => $course_id, 'target' => '_blank'));
            $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));

            $header[$course_link] = $atividades;
        }

        foreach ($header as $key => $modulo) {
            $course_id = $modulo[0]->course_id;

            if($course_id == constant('TCC-Turma-B') || $course_id == constant('TCC-Turma-A')){
                array_push($modulo, 'TCC');
                $header[$key] = $modulo;
            }
        }

        return $header;
    }

}
