<?php

class report_entrega_de_atividades extends Factory {

    function __construct() {
    }

    public function initialize($factory, $filtro = true) {
        $factory->mostrar_barra_filtragem = $filtro;
        $factory->mostrar_botoes_grafico = true;
        $factory->mostrar_botoes_dot_chart = false;
        $factory->mostrar_filtro_polos = true;
        $factory->mostrar_filtro_cohorts = true;
        $factory->mostrar_filtro_modulos = true;
        $factory->mostrar_filtro_intervalo_tempo = false;
        $factory->mostrar_aviso_intervalo_tempo = false;
    }

    public function render_report_default($renderer){
        echo $renderer->build_page();
    }

    public function render_report_table($renderer, $object, $factory = null) {
        $this->initialize($factory, false);
        echo $renderer->build_report($object);
    }

    public function render_report_graph($renderer, $object, $porcentagem, $factory = null){
        $this->initialize($factory, false);
        echo $renderer->build_graph($object, $porcentagem);
    }

    public function get_dados_grafico(){
        global $CFG;
        /** @var $factory Factory */
        $factory = Factory::singleton();

        // Consultas
        $query_alunos_grupo_tutoria = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();

        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(
            $query_alunos_grupo_tutoria, $query_forum, $query_quiz);


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
            $dados[grupos_tutoria::grupo_tutoria_to_string($factory->get_curso_ufsc(), $grupo_id)] =
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

    public function get_dados(){
        /** @var $factory Factory */
        $factory = Factory::singleton();

        // Consultas
        $query_alunos_grupo_tutoria = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();

        // Recupera dados auxiliares
        $nomes_cohorts = get_nomes_cohorts($factory->get_curso_ufsc());
        $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($factory->get_curso_ufsc());
        $nomes_polos = get_polos($factory->get_curso_ufsc());

        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(
            $query_alunos_grupo_tutoria, $query_forum, $query_quiz);

        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();
            foreach ($array_dados as $id_aluno => $aluno) {
                $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $factory->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);

                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    $atraso = null;

                    // Não se aplica para este estudante
                    if (is_a($atividade, 'report_unasus_data_empty')) {
                        $lista_atividades[] = new dado_nao_aplicado();
                        continue;
                    }

                    // Se a atividade não foi entregue
                    if (!$atividade->has_submitted()) {

                        if (!$atividade->source_activity->has_deadline()) {
                            // E não tem entrega prazo
                            $tipo = dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA;
                        } elseif ($atividade->is_a_future_due()) {
                            //atividade com data de entrega no futuro, nao entregue mas dentro do prazo
                            $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_MAS_NO_PRAZO;
                        } else {
                            // Atividade nao entregue e atrasada
                            $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE_FORA_DO_PRAZO;
                        }
                    } else {

                        // Entrega atrasada
                        if ($atividade->is_submission_due()) {
                            $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                        } else {
                            $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO;
                        }

                        $atraso = $atividade->submission_due_days();
                    }
                    $lista_atividades[] = new dado_entrega_de_atividades($tipo, $atividade->source_activity->id, $atraso);
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
                $lista_atividades = null;
            }
            // Ou unir os alunos de acordo com o tutor dele
            if ($factory->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($factory->get_curso_ufsc(), $grupo_id)] = $estudantes;
            }
        }

        return ($dados);
    }

    public function get_table_header($mostrar_nota_final = false, $mostrar_total = false){
        /** @var $factory Factory */
        $factory = Factory::singleton();

        $atividades_cursos = get_atividades_cursos($factory->get_modulos_ids(), $mostrar_nota_final, $mostrar_total);
        $header = array();

        foreach ($atividades_cursos as $course_id => $atividades) {
            $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
            $course_link = html_writer::link($course_url, $atividades[0]->course_name);

            $header[$course_link] = $atividades;
        }
        return $header;
    }

}