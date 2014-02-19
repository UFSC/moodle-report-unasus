<?php

class report_boletim extends Factory {

    function __construct() {
    }

    public function initialize($factory, $filtro = true) {
        $factory->mostrar_barra_filtragem = $filtro;
        $factory->mostrar_botoes_grafico = false; //Botões de geração de gráfico removidos - não são utilizados
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
            $count_com_nota = 0;
            $count_sem_nota = 0;

            foreach ($array_dados as $id_aluno => $aluno) {

                foreach ($aluno as $atividade) {
                    $atraso = null;

                    //Atividade tem nota
                    if ($atividade->has_grade()) {
                        $count_com_nota++;
                    } else {
                        $count_sem_nota++;
                    }
                }
            }
            $dados[grupos_tutoria::grupo_tutoria_to_string($factory->get_curso_ufsc(), $grupo_id)] =
                    array($count_com_nota, $count_sem_nota);
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
        $query_nota_final = query_nota_final();

        // Recupera dados auxiliares
        $nomes_cohorts = get_nomes_cohorts($factory->get_curso_ufsc());
        $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($factory->get_curso_ufsc());
        $nomes_polos = get_polos($factory->get_curso_ufsc());

        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($query_alunos_grupo_tutoria, $query_forum, $query_quiz, false, $query_nota_final);

        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();
            foreach ($array_dados as $id_aluno => $aluno) {
                // FIXME: se o dado for do tipo 'report_unasus_data_nota_final' não possui 'cohort', corrigir a estrutura para suportar cohort.
                $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $factory->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);

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
                $estudantes[] = $lista_atividades;

                // Agrupamento dos estudantes pelo seu polo
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

            // Ou pelo grupo de tutoria do estudante
            if ($factory->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($factory->get_curso_ufsc(), $grupo_id)] = $estudantes;
            }
        }

        return $dados;
    }

    public function get_table_header($mostrar_nota_final = true, $mostrar_total = false){
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