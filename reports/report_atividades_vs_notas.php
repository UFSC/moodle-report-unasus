<?php

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

    public function render_report_default($renderer){
        echo $renderer->build_page();
    }

    public function render_report_table($renderer, $report) {
        $this->mostrar_barra_filtragem = false;
        echo $renderer->build_report($report);
    }

    public function render_report_graph($renderer, $report, $porcentagem){
        $this->mostrar_barra_filtragem = false;
        echo $renderer->build_graph($report, $porcentagem);
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

        foreach($header as $h){
            if(isset($h[0]->course_name)){
                $course_name = $h[0]->course_name;
                $first_line[] = $course_name;
            }
            $n = count($h);
            for($i=0;$i < $n; $i++ ){
                if(isset($h[$i]->name)){
                    $element = $h[$i]->name;
                    $data_header[] = $element;
                }
                //Insere o nome do módulo na célula acima da primeira atividade daquele módulo
                if($i<$n-1){
                    $first_line[] = '';
                } else
                    continue;
            }
        }

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        foreach($dados as $dat){

            if($count < $n_names){
                file_put_contents('php://output', $name_tutor[$count]);
                fputcsv($fp, $tutor_name);
            }
            foreach($dat as $d){
                $output = array_map("Factory::eliminate_html", $d);
                fputcsv($fp, $output);
            }

            $count++;
        }
        fclose($fp);
    }

    public function get_dados_grafico(){
        global $CFG;

        // Consultas
        $query_alunos_grupo_tutoria = query_atividades();
        $query_quiz = query_quiz();
        $query_forum = query_postagens_forum();


        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo($query_alunos_grupo_tutoria, $query_forum, $query_quiz);


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

            $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_curso_ufsc(), $grupo_id)] =
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

    /**
     * Geração de dados dos tutores e seus respectivos alunos.
     *
     * @return array Array[tutores][aluno][unasus_data]
     */

    public function get_dados(){
        // Dado Auxiliar
        $nomes_cohorts = get_nomes_cohorts($this->get_curso_ufsc());
        $nomes_estudantes = grupos_tutoria::get_estudantes_curso_ufsc($this->get_curso_ufsc());
        $nomes_polos = get_polos($this->get_curso_ufsc());

        // Consultas
        $query_alunos_grupo_tutoria = query_atividades();
        $query_forum = query_postagens_forum();
        $query_quiz = query_quiz();

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

                $lista_atividades[] = new estudante($nomes_estudantes[$id_aluno], $id_aluno, $this->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);


                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    $atraso = null;

                    // Não se aplica para este estudante
                    if (is_a($atividade, 'report_unasus_data_empty')) {
                        $lista_atividades[] = new dado_nao_aplicado();
                        continue;
                    }

                    //Se atividade não tem data de entrega, não tem entrega e nem nota
                    if (!$atividade->source_activity->has_deadline() && !$atividade->has_submitted() && !$atividade->has_grade()) {
                        $tipo = dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA;
                    } else {

                        //Atividade pro futuro
                        if ($atividade->is_a_future_due()) {
                            $tipo = dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA;
                        }

                        //Entrega atrasada
                        if ($atividade->is_submission_due()) {
                            $tipo = dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE;
                        }

                        //Atividade entregue e necessita de nota
                        if ($atividade->is_grade_needed()) {
                            $atraso = $atividade->grade_due_days();
                            $tipo = dado_atividades_vs_notas::CORRECAO_ATRASADA;
                        }

                        //Atividade tem nota
                        if ($atividade->has_grade()) {
                            $atraso = $atividade->grade_due_days();

                            //Verifica se a correcao foi dada com ou sem atraso
                            if ($atraso > get_prazo_avaliacao()) {
                                $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA_COM_ATRASO;
                            } else {
                                $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA_SEM_ATRASO;
                            }
                        }
                    }

                    $lista_atividades[] = new dado_atividades_vs_notas($tipo, $atividade->source_activity->id, $atividade->grade, $atraso);
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

                $lista_atividades = null;
            }
            // Ou unir os alunos de acordo com o tutor dele
            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_curso_ufsc(), $grupo_id)] = $estudantes;
            }
        }

        return $dados;
    }

    /**
     *  Cabeçalho de duas linhas para os relatórios
     *  Primeira linha módulo1, modulo2
     *  Segunda linha ativ1_mod1, ativ2_mod1, ativ1_mod2, ativ2_mod2
     *
     * @return array
     */

    public function get_table_header($mostrar_nota_final = false, $mostrar_total = false){
        $atividades_cursos = get_atividades_cursos($this->get_modulos_ids(), $mostrar_nota_final, $mostrar_total);
        $header = array();

        foreach ($atividades_cursos as $course_id => $atividades) {
            $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
            $course_link = html_writer::link($course_url, $atividades[0]->course_name);

            $header[$course_link] = $atividades;
        }

        return $header;
    }

}