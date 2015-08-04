<?php

defined('MOODLE_INTERNAL') || die;

class report_atividades_vs_notas extends Factory {

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
        $this->mostrar_botao_exportar_csv = true;
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

    public function render_report_csv($name_report) {

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=relatorio ' . $name_report . '.csv');
        readfile('php://output');

        $dados = $this->get_dados();
        $header = $this->get_table_header();

        $fp = fopen('php://output', 'w');

        $data_header = array('Estudante');
        $first_line = array('');
        $tutor_name = array();

        $name_tutor = array_map("Factory::eliminate_html", array_keys($dados));
        $count = 0;
        $n_names = count($name_tutor);

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
            }
        }

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        foreach ($dados as $dat) {

            if ($count < $n_names) {
                file_put_contents('php://output', $name_tutor[$count]);
                fputcsv($fp, $tutor_name);
            }
            foreach ($dat as $d) {
                $output = array_map("Factory::eliminate_html", $d);
                fputcsv($fp, $output);
            }

            $count++;
        }
        fclose($fp);
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
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($query_atividades, $query_forum, $query_quiz);


//  Ordem dos dados nos gráficos
//        'nota_atribuida'
//        'nota_atribuida_atraso'
//        'pouco_atraso'
//        'muito_atraso'
//        'nao_entregue'
//        'nao_realizada'
//        'sem_prazo'

        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            //variáveis soltas para melhor entendimento
            $count_nota_atribuida = 0;
            $count_pouco_atraso = 0;
            $count_muito_atraso = 0;
            $count_nao_entregue = 0;
            $count_nao_realizada = 0;
            $count_sem_prazo = 0;
            $count_nota_atribuida_atraso = 0;

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

                        //Atividade pro futuro
                        if ($atividade->is_a_future_due()) {
                            $count_nao_realizada++;
                        }

                        //Entrega atrasada
                        if ($atividade->is_submission_due()) {
                            $count_nao_entregue++;
                        }

                        //Atividade entregue e necessita de nota
                        if ($atividade->is_grade_needed()) {
                            $atraso = $atividade->grade_due_days();
                            ($atraso > $CFG->report_unasus_prazo_maximo_avaliacao) ? $count_muito_atraso++ : $count_pouco_atraso++;
                        }

                        //Atividade tem nota
                        if ($atividade->has_grade()) {
                            $atraso = $atividade->grade_due_days();

                            //Verifica se a correcao foi dada com ou sem atraso
                            ($atraso > get_prazo_avaliacao()) ? $count_nota_atribuida_atraso++ : $count_nota_atribuida++;
                        }
                    }
                }
            }

            $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] =
                    array($count_nota_atribuida,
                            $count_nota_atribuida_atraso,
                            $count_pouco_atraso,
                            $count_muito_atraso,
                            $count_nao_entregue,
                            $count_nao_realizada,
                            $count_sem_prazo);
        }
        return $dados;
    }

    public function get_dados() {

        // Recupera dados auxiliares
        $nomes_estudantes = grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $grupos = grupos_tutoria::get_grupos_tutoria($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        $dados = array();
        $atraso = 0;

        $atividade_nota_final = new \StdClass();

        // Para cada grupo de tutoria
        foreach ($grupos as $grupo) {
            $estudantes = array();
            foreach ($this->atividades_cursos as $courseid => $atividades) {
                array_push($atividades, $atividade_nota_final);

                $database_courses = ($courseid == 129 || $courseid == 130 || $courseid == 131);

                foreach ($atividades as $atividade) {
                    $result = get_atividades(get_class($atividade), $atividade, $courseid, $grupo, $this, true);

                    foreach ($result as $r) {

                        switch ($r->name_activity) {
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

                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                        if ($r->name_activity == 'nota_final_activity' && !isset($atividade->id)
                           && ($database_courses)){

                            $nota = null;

                            if (isset($r->grade)) {
                                $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA_SEM_ATRASO;
                                $nota = $r->grade;
                            } else {
                                $tipo = dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA;
                            }

                            $lista_atividades[$r->userid][] = new dado_atividades_vs_notas($tipo, 0, $nota);

                        } else if ( !($database_courses)) {

                            //Se atividade não tem data de entrega, não tem entrega e nem nota
                            if (!$data->has_submitted() && !$data->has_grade()) {
                                $tipo = dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA;
                            } else {

                                //Atividade pro futuro
                                if ($data->is_a_future_due()) {
                                    $tipo = dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA;
                                }

                                //Entrega atrasada
                                if ($data->is_submission_due()) {
                                    $tipo = dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE;
                                }

                                //Atividade entregue e necessita de nota
                                if ($data->is_grade_needed()) {
                                    $atraso = $data->grade_due_days();
                                    $tipo = dado_atividades_vs_notas::CORRECAO_ATRASADA;
                                }

                                //Atividade tem nota
                                if ($data->has_grade()) {
                                    $atraso = $data->grade_due_days();

                                    //Verifica se a correcao foi dada com ou sem atraso
                                    if ($atraso > get_prazo_avaliacao()) {
                                        $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA_COM_ATRASO;
                                    } else {
                                        $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA_SEM_ATRASO;
                                    }
                                }
                            }

                            if(isset($atividade->id)){
                                $lista_atividades[$r->userid][$atividade->id] = new dado_atividades_vs_notas($tipo, $atividade->id, $data->grade, $atraso);
                            }
                        }
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

    /**
     *  Cabeçalho de duas linhas para os relatórios
     *  Primeira linha módulo1, modulo2
     *  Segunda linha ativ1_mod1, ativ2_mod1, ativ1_mod2, ativ2_mod2
     *
     * @param bool $mostrar_nota_final
     * @param bool $mostrar_total
     * @return array
     */
    public function get_table_header($mostrar_nota_final = false, $mostrar_total = false) {

        $atividades_cursos = get_atividades_cursos($this->get_modulos_ids(), $mostrar_nota_final, $mostrar_total, false);
        $header = array();

        foreach ($atividades_cursos as $course_id => $atividades) {

            if(isset($atividades[0]->course_name)){
                $course_url = new moodle_url('/course/view.php', array('id' => $course_id, 'target' => '_blank'));
                $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));
                // Módulo de 'Controle Acadêmico', 'Ambiente de Tutoria' e 'Apresentação do Curso' só apresentam média final pois não possuem atividades com nota
                if($course_id ==  131 || $course_id ==  129 || $course_id ==  130) {
                    $header[$course_link][] = 'Média Final';
                } else {
                    $header[$course_link] = $atividades;
                }
            }
        }

        foreach ($header as $key => $modulo) {
            if (!isset($modulo[0]->course_id)){
                break;
            }
            $course_id = $modulo[0]->course_id;

            if($course_id == constant('TCC-Turma-B') || $course_id == constant('TCC-Turma-A')){
                array_push($modulo, 'TCC');
                $header[$key] = $modulo;
            }
        }

        return $header;
    }

}