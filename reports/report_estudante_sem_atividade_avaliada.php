<?php

defined('MOODLE_INTERNAL') || die;

class report_estudante_sem_atividade_avaliada extends report_unasus_factory {

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
        $this->mostrar_botao_exportar_csv = false;
    }

    public function render_report_default($renderer) {
        echo $renderer->build_page();
    }

    public function render_report_table($renderer) {
        $this->mostrar_barra_filtragem = false;
        echo $renderer->page_todo_list($this);
    }

    public function get_dados() {

        // Recupera dados auxiliares
        $nomes_cohorts = report_unasus_get_nomes_cohorts($this->get_categoria_curso_ufsc());
        $nomes_estudantes = local_tutores_grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $nomes_polos = report_unasus_get_polos($this->get_categoria_turma_ufsc());

        $foruns_modulo = report_unasus_query_forum_courses($this->get_modulos_ids());

        $listagem_forum = new report_unasus_GroupArray();
        foreach ($foruns_modulo as $forum) {
            $listagem_forum->add($forum->course_id, $forum);
        }

        $query_alunos_grupo_tutoria = query_atividades_from_users();
        $query_quiz                 = query_quiz_from_users();
        $query_forum                = query_postagens_forum_from_users();
        $query_lti                  = query_lti_from_users();
        //$query_db                   = query_database_from_users();
        $query_database             = query_database_adjusted_from_users();
        $query_scorm                = query_scorm_from_users();

        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(
                $query_alunos_grupo_tutoria, $query_forum, $query_quiz, $query_lti, $query_database, $query_scorm);

        $modulos_ids = $this->get_modulos_ids();

        $atividades_cursos = report_unasus_get_atividades_cursos($modulos_ids, false, false, false, $this->get_categoria_turma_ufsc());
        $atividades_config_curso = report_unasus_get_activities_config_report($this->get_categoria_turma_ufsc(), $modulos_ids);

        $dados = array();

        // para todas os grupos ($grupo_id), pega os dados (user_id => atividades)
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();

            // para todas os estudantes ($id_aluno), pega os dados (report_activities),
                // as atividades do estudante com os dados dele
            foreach ($array_dados as $id_aluno => $aluno) {

                $atividades_modulos = new report_unasus_GroupArray();

                // paga cada atividade com os dados do estudante
                foreach ($aluno as $atividade) {

                    foreach ($atividades_cursos as $act) {
                        foreach ($act as $a) {
                            if ($a->id == $atividade->source_activity->id){
                                $atividade->source_activity->config = NULL;
                                if (!empty($a->config)) {
                                    $atividade->source_activity->config = $a->config;
                                };
                            }
                        }
                    }

                    // se a atividade for setada para ser apresentada,
                        // então continua com as outras checagens
                    if ( array_search($atividade->source_activity->id, $atividades_config_curso) ) {

                        $nome_atividade = null;
                        $atividade_sera_listada = false;

                        // Não se aplica para este estudante
                        if (is_a($atividade, 'report_unasus_data_empty')) {
                            continue;
                        }

                        if ($this->get_relatorio() == 'estudante_sem_atividade_avaliada' && !$atividade->has_grade() && $atividade->is_grade_needed()) {
                            $atividade_sera_listada = true;
                        }

                        if ($atividade_sera_listada) {
                            $atividades_modulos->add($atividade->source_activity->course_id, array('atividade' => $atividade));
                        }
                    }
                }


                $ativ_mod = $atividades_modulos->get_assoc();

                if (!empty($ativ_mod)) {

                    $lista_atividades[] = new report_unasus_student($nomes_estudantes[$id_aluno], $id_aluno, $this->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);

                    foreach ($ativ_mod as $key => $modulo) {
                        $lista_atividades[] = new report_unasus_dado_modulo_render($key, $modulo[0]['atividade']->source_activity->course_name);
                        foreach ($modulo as $atividade) {
                            $lista_atividades[] = new report_unasus_dado_atividade_render($atividade['atividade']);
                        }
                    }

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

    public function get_table_header($mostrar_nota_final = false, $mostrar_total = false) {
        $header = $this->get_table_header_modulos_atividades($mostrar_nota_final, $mostrar_total);
        $header[''] = array('Média');
        return $header;
    }
}