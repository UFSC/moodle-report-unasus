<?php

defined('MOODLE_INTERNAL') || die;

class report_boletim extends Factory {

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
        echo $renderer->build_report($this);
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
        $data_header[] = 'Média Final';

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        $name = array_map("Factory::eliminate_html", array_keys($dados));
        $count = 0;
        $n = count($name);

        foreach ($dados as $dat) {
            if ($count < $n) {
                file_put_contents('php://output', $name[$count]);
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

    public function get_dados() {
        // Consultas
        $query_atividades = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();
        $query_nota_final = query_nota_final();

        // Recupera dados auxiliares
        $nomes_cohorts = get_nomes_cohorts($this->get_categoria_curso_ufsc());
        $nomes_estudantes = grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $nomes_polos = get_polos($this->get_categoria_turma_ufsc());

        $grupos = grupos_tutoria::get_grupos_tutoria($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($query_atividades, $query_forum, $query_quiz, $query_nota_final);

        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();
            foreach ($array_dados as $id_aluno => $aluno) {
                // FIXME: se o dado for do tipo 'report_unasus_data_nota_final' não possui 'cohort', corrigir a estrutura para suportar cohort.
                $lista_atividades[] = new report_unasus_student($nomes_estudantes[$id_aluno], $id_aluno, $this->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);

                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    $nota = null;

                    // Não se aplica para este estudante
                    if (is_a($atividade, 'report_unasus_data_empty')) {
                        $lista_atividades[] = new dado_nao_aplicado();
                        continue;
                    }

                    //Atividade tem nota
                    if ($atividade->has_grade()) {
                        $tipo = dado_boletim::ATIVIDADE_COM_NOTA;
                        $nota = $atividade->grade;
                    } else {
                        $tipo = dado_boletim::ATIVIDADE_SEM_NOTA;
                    }

                    if (is_a($atividade, 'report_unasus_data_nota_final')) {
                        $lista_atividades[] = new dado_nota_final($tipo, $nota);
                    } else {
                        $lista_atividades[] = new dado_boletim($tipo, $atividade->source_activity->id, $nota);
                    }
                }

                $tam_lista_atividades = sizeof($lista_atividades);
                $lti_query_object = new LtiPortfolioQuery();

                foreach($grupos as $grupo){
                    foreach ($this->atividades_cursos as $courseid => $atividades) {
                        foreach ($atividades as $activity) {

                            if (is_a($activity, 'report_unasus_lti_activity') && sizeof($lista_atividades) <= $tam_lista_atividades) {
                                $result = $lti_query_object->get_report_data($activity, $grupo->id);

                                foreach ($result as $l) {
                                    $grade = null;

                                    if(isset($l->grade_tcc)){
                                        $type = dado_boletim::ATIVIDADE_COM_NOTA;
                                        $grade = $l->grade_tcc;
                                    } else {
                                        $type = dado_boletim::ATIVIDADE_SEM_NOTA;
                                    }
                                }
                                $lista_atividades[] = new dado_boletim($type, $activity->id, $grade);
                                break;
                            }
                        }
                    }
                }

                $estudantes[] = $lista_atividades;

                // Agrupamento dos estudantes pelo seu polo
                if ($this->agrupar_relatorios == AGRUPAR_POLOS) {
                    $dados[$nomes_polos[$lista_atividades[0]->polo]][] = $lista_atividades;
                }

                // Unir os alunos de acordo com o cohort deles
                if ($this->agrupar_relatorios == AGRUPAR_COHORTS) {
                    $key = isset($lista_atividades[0]->cohort) ? $nomes_cohorts[$lista_atividades[0]->cohort] : get_string('cohort_empty', 'report_unasus');
                    $dados[$key][] = $lista_atividades;
                }

                $lista_atividades = null;
            }

            // Ou pelo grupo de tutoria do estudante
            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] = $estudantes;
            }
        }

        return $dados;
    }

    public function get_table_header($mostrar_nota_final = true, $mostrar_total = false) {
        $atividades_cursos = get_atividades_cursos($this->get_modulos_ids(), $mostrar_nota_final, $mostrar_total, false);
        $header = array();

        foreach ($atividades_cursos as $course_id => $atividades) {

            if(isset($atividades[0]->course_name)){
                $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
                $course_link = html_writer::link($course_url, $atividades[0]->course_name);
                $header[$course_link] = $atividades;
            }
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