<?php

defined('MOODLE_INTERNAL') || die;

class report_potenciais_evasoes extends report_unasus_factory {

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
        $this->texto_cabecalho = 'Tutores';
        echo $renderer->build_report($this);
    }

    public function render_report_csv($name_report) {

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=relatorio ' . $name_report . '.csv');
        readfile('php://output');

        $dados = $this->get_dados();
        $header = $this->get_table_header();

        $fp = fopen('php://output', 'w');

        $tutor_name = array('');
        $n = count($header);

        for ($i = 0; $i < $n; $i++) {
            $data_header[] = strip_tags($header[$i]);
        }

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
        global $CFG;

        $modulos = $this->atividades_cursos;
        // Consulta
        $query_atividades = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();


        // Recupera dados auxiliares
        $nomes_cohorts = report_unasus_get_nomes_cohorts($this->get_categoria_curso_ufsc());
        $nomes_estudantes = local_tutores_grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $nomes_polos = report_unasus_get_polos($this->get_categoria_turma_ufsc());

        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($query_atividades, $query_forum, $query_quiz);

        //pega a hora atual para comparar se uma atividade esta atrasada ou nao
        $timenow = time();
        $dados = array();

        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();
            foreach ($array_dados as $id_aluno => $aluno) {
                $dados_modulos = array();
                $lista_atividades[] = new report_unasus_student($nomes_estudantes[$id_aluno], $id_aluno, $this->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);
                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */

                    // para cada novo modulo ele cria uma entrada de dado_potenciais_evasoes com o maximo de atividades daquele modulo
                    if (!array_key_exists($atividade->source_activity->course_id, $dados_modulos)) {
                        $dados_modulos[$atividade->source_activity->course_id] = new report_unasus_dado_potenciais_evasoes_render(sizeof($modulos[$atividade->source_activity->course_id]));
                    }

                    // para cada atividade nao feita ele adiciona uma nova atividade nao realizada naquele modulo
                    if ($atividade->source_activity->has_submission() && !$atividade->has_submitted() && !$atividade->is_a_future_due()) {
                        $dados_modulos[$atividade->source_activity->course_id]->add_atividade_nao_realizada();
                    }
                }

                $atividades_nao_realizadas_do_estudante = 0;
                foreach ($dados_modulos as $key => $modulo) {
                    $lista_atividades[] = $modulo;
                    $atividades_nao_realizadas_do_estudante += $modulo->get_total_atividades_nao_realizadas();
                }

                if ($atividades_nao_realizadas_do_estudante >= $CFG->report_unasus_tolerancia_potencial_evasao) {
                    $estudantes[] = $lista_atividades;
                    // Unir os alunos de acordo com o polo deles
                    if ($this->agrupar_relatorios == AGRUPAR_POLOS) {
                        $dados[$nomes_polos[$lista_atividades[0]->polo]][] = $lista_atividades;
                    }
                    // Unir os alunos de acordo com o cohort deles
                    if ($this->agrupar_relatorios == AGRUPAR_COHORTS) {
                        $key = isset($lista_atividades[0]->cohort) ? $nomes_cohorts[$lista_atividades[0]->cohort] : get_string('cohort_empty', 'report_unasus');
                        $dados[$key][] = $lista_atividades;
                    }
                }
                $lista_atividades = null;
            }

            // Ou unir os alunos de acordo com o tutor dele
            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[local_tutores_grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] = $estudantes;
            }
        }
        return $dados;
    }

    /**
     * FIXME Nada faz sentido no código abaixo. Verificar e corrigir o que for necessário (utilizar os metodos da factory)
     * @return array
     */
    public function get_table_header() {
        $modulos = $this->get_modulos_ids();

        $nome_modulos = report_unasus_get_id_nome_modulos($this->get_categoria_turma_ufsc());
        if (is_null($this->modulos_selecionados)) {
            $modulos = report_unasus_get_id_modulos();
        }

        $header = array();
        $header[] = 'Estudantes';
        foreach ($modulos as $modulo) {
            $header[] = new report_unasus_dado_modulo_render($modulo, $nome_modulos[$modulo]);
        }
        return $header;
    }


}