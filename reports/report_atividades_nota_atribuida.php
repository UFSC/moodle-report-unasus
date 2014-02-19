<?php

class report_atividades_nota_atribuida extends Factory {

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
    }

    public function render_report_default($renderer){
        echo $renderer->build_page();
    }

    public function render_report_table($renderer, $object, $factory = null) {
        $this->initialize($factory, false);
        echo $renderer->page_atividades_nao_avaliadas($object);
    }

    public function get_dados(){
        /** @var $factory Factory */
        $factory = Factory::singleton();

        // Consulta
        $query_alunos_grupo_tutoria = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();

        $result_array = loop_atividades_e_foruns_sintese($query_alunos_grupo_tutoria, $query_forum, $query_quiz);

        $total_alunos = $result_array['total_alunos'];
        $total_atividades = $result_array['total_atividades'];
        $lista_atividade = $result_array['lista_atividade'];
        $associativo_atividade = $result_array['associativo_atividade'];


        $somatorio_total_atrasos = array();
        $atividades_alunos_grupos = get_dados_alunos_atividades_concluidas($associativo_atividade)->somatorio_grupos;

        foreach ($associativo_atividade as $grupo_id => $array_dados) {
            foreach ($array_dados as $aluno) {

                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    if (!array_key_exists($grupo_id, $somatorio_total_atrasos)) {
                        $somatorio_total_atrasos[$grupo_id] = 0;
                    }


                    if ($atividade->has_grade() && $atividade->is_grade_needed()) {

                        /** @var dado_atividades_alunos $dado */
                        unset($dado); // estamos trabalhando com ponteiro, não podemos atribuir null ou alteramos o array.

                        if (is_a($atividade, 'report_unasus_data_activity')) {
                            $dado =& $lista_atividade[$grupo_id]['atividade_' . $atividade->source_activity->id];
                        } elseif (is_a($atividade, 'report_unasus_data_forum')) {
                            $dado =& $lista_atividade[$grupo_id]['forum_' . $atividade->source_activity->id];
                        } elseif (is_a($atividade, 'report_unasus_data_quiz')) {
                            $dado =& $lista_atividade[$grupo_id]['quiz_' . $atividade->source_activity->id];
                        } elseif (is_a($atividade, 'report_unasus_data_lti')) {
                            $dado =& $lista_atividade[$grupo_id][$atividade->source_activity->id][$atividade->source_activity->position];
                        }
                        $dado->incrementar();
                        $somatorio_total_atrasos[$grupo_id]++;
                    }

                    $total_atividades++;
                }
            }
        }

        //soma atividades concluidas
        $dados = array();
        $somatorio_total_alunos = 0;
        $somatorio_total_alunos_atividades_concluidas = 0;

        foreach ($lista_atividade as $grupo_id => $grupo) {
            $data = array();
            $data[] = grupos_tutoria::grupo_tutoria_to_string($factory->get_curso_ufsc(), $grupo_id);
            foreach ($grupo as $atividades) {
                if (is_array($atividades)) {
                    foreach ($atividades as $atividade) {
                        $data[] = $atividade;
                    }
                } else {
                    $data[] = $atividades;
                }
            }

            /* Coluna  N° Alunos com atividades concluídas */
            $somatorioalunosgrupos = isset($atividades_alunos_grupos[$grupo_id]) ? $atividades_alunos_grupos[$grupo_id] : 0;
            $data[] = new dado_media($somatorioalunosgrupos, $total_alunos[$grupo_id]);

            $dados[] = $data;
            $somatorio_total_alunos_atividades_concluidas += $somatorioalunosgrupos;
            $somatorio_total_alunos += $total_alunos[$grupo_id];
        }

        /* Linha total alunos com atividades concluidas  */
        $data_total = array(html_writer::tag('strong', 'Total alunos com atividade concluida / Total alunos'));
        $count = count($data) - 2;
        for ($i = 0; $i < $count; $i++) {
            $data_total[] = '';
        }
        $data_total[] = new dado_media($somatorio_total_alunos_atividades_concluidas, $somatorio_total_alunos);

        $dados[] = $data_total;
        return $dados;
    }

    public function get_table_header(){
        $header = get_table_header_modulos_atividades(false, true);
        $header[''] = array(get_string('column_aluno_atividade_concluida', 'report_unasus'));
        return $header;
    }

}