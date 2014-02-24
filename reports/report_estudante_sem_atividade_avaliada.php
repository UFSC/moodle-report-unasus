<?php

class report_estudante_sem_atividade_avaliada extends Factory {

    function __construct() {
    }

    public function initialize($factory, $filtro = true) {
        $factory->mostrar_barra_filtragem = $filtro;
        $factory->mostrar_botoes_grafico = false;
        $factory->mostrar_botoes_dot_chart = false;
        $factory->mostrar_filtro_polos = true;
        $factory->mostrar_filtro_cohorts = true;
        $factory->mostrar_filtro_modulos = true;
        $factory->mostrar_filtro_intervalo_tempo = false;
        $factory->mostrar_aviso_intervalo_tempo = false;
        $factory->mostrar_botao_exportar_csv = false;
    }

    public function render_report_default($renderer){
        echo $renderer->build_page();
    }

    public function render_report_table($renderer, $object, $factory = null) {
        $this->initialize($factory, false);
        echo $renderer->page_todo_list($object);
    }

    /**
     * @param $renderer report_unasus_renderer
     * @param $object
     * @param null $factory
     */
    public function render_report_csv($renderer, $object, $factory = null) {
        $this->initialize($factory, false);
        echo $renderer->page_todo_list($object);
    }

    public function get_dados(){
        /** @var $factory Factory */
        $factory = Factory::singleton();

        // Recupera dados auxiliares
        $nomes_cohorts = get_nomes_cohorts($factory->get_curso_ufsc());
        $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($factory->get_curso_ufsc());
        $nomes_polos = get_polos($factory->get_curso_ufsc());
        $foruns_modulo = query_forum_courses($factory->get_modulos_ids());

        $listagem_forum = new GroupArray();
        foreach ($foruns_modulo as $forum) {
            $listagem_forum->add($forum->course_id, $forum);
        }

        $query_alunos_grupo_tutoria = query_atividades_nao_postadas();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();

        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(
            $query_alunos_grupo_tutoria, $query_forum, $query_quiz);


        $dados = array();

        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();
            foreach ($array_dados as $id_aluno => $aluno) {

                $atividades_modulos = new GroupArray();

                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    $tipo_avaliacao = 'atividade';
                    $nome_atividade = null;
                    $atividade_sera_listada = false;

                    // Não se aplica para este estudante
                    if (is_a($atividade, 'report_unasus_data_empty')) {
                        continue;
                    }

                    if ($factory->get_relatorio() == 'estudante_sem_atividade_postada' && !$atividade->has_submitted() && $atividade->source_activity->has_submission()) {
                        $atividade_sera_listada = true;
                    }

                    if ($factory->get_relatorio() == 'estudante_sem_atividade_avaliada' && !$atividade->has_grade() && $atividade->is_grade_needed()) {
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

                    $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $factory->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);

                    foreach ($ativ_mod as $key => $modulo) {
                        $lista_atividades[] = new dado_modulo($key, $modulo[0]['atividade']->source_activity->course_name);
                        foreach ($modulo as $atividade) {
                            $lista_atividades[] = new dado_atividade($atividade['atividade']);
                        }
                    }

                    $estudantes[] = $lista_atividades;
                    // Unir os alunos de acordo com o polo deles
                    if ($factory->agrupar_relatorios == AGRUPAR_POLOS) {
                        $dados[$nomes_polos[$lista_atividades[0]->polo]][] = $lista_atividades;
                    }

                    // Unir os alunos de acordo com o cohort deles
                    if ($factory->agrupar_relatorios == AGRUPAR_COHORTS) {
                        $key = isset($lista_atividades[0]->cohort) ? $nomes_cohorts[$lista_atividades[0]->cohort] : get_string('cohort_empty', 'report_unasus');
                        $dados[$key][] = $lista_atividades;
                    }
                }
                $lista_atividades = null;
            }

            // Ou unir os alunos de acordo com o tutor dele
            if ($factory->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($factory->get_curso_ufsc(), $grupo_id)] = $estudantes;
            }
        }
        return $dados;
    }

    public function get_table_header($mostrar_nota_final = false, $mostrar_total = false) {
        $header = get_table_header_modulos_atividades($mostrar_nota_final, $mostrar_total);
        $header[''] = array('Média');
        return $header;
    }
}