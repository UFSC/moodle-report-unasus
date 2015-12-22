<?php

defined('MOODLE_INTERNAL') || die;

class report_estudante_sem_atividade_postada extends report_unasus_factory {

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
        $query_quiz = query_quiz_from_users();
        $query_forum = query_postagens_forum_from_users();

        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(
                $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

        $dados = array();

        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();
            foreach ($array_dados as $id_aluno => $aluno) {

                $atividades_modulos = new report_unasus_GroupArray();

                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    $tipo_avaliacao = 'atividade';
                    $nome_atividade = null;
                    $atividade_sera_listada = false;

                    // Não se aplica para este estudante
                    if (is_a($atividade, 'report_unasus_data_empty')) {
                        continue;
                    }

                    if ($this->get_relatorio() == 'estudante_sem_atividade_postada' && !$atividade->has_submitted() && $atividade->source_activity->has_submission()) {
                        $atividade_sera_listada = true;
                    }

                    if ($this->get_relatorio() == 'estudante_sem_atividade_avaliada' && !$atividade->has_grade() && $atividade->is_grade_needed()) {
                        $atividade_sera_listada = true;
                    }

                    if (is_a($atividade, 'report_unasus_data_forum')) {
                        $tipo_avaliacao = 'forum';
                    }


                    if ($atividade_sera_listada) {
                        $atividades_modulos->add($atividade->source_activity->course_id, array('atividade' => $atividade, 'tipo' => $tipo_avaliacao));
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

    public function get_table_header($size) {
        $content = array();
        for ($index = 0; $index < $size - 1; $index++) {
            $content[] = '';
        }
        $header['Atividades não resolvidas'] = $content;
        return $header;
    }
}