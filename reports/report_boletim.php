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

        global $DB;

        $query = '';
        $params = '';

        // Recupera dados auxiliares
        $nomes_cohorts = get_nomes_cohorts($this->get_categoria_curso_ufsc());
        $nomes_estudantes = grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $nomes_polos = get_polos($this->get_categoria_turma_ufsc());

        $grupos = grupos_tutoria::get_grupos_tutoria($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        $relationship = grupos_tutoria::get_relationship_tutoria($this->get_categoria_turma_ufsc());
        $cohort_estudantes = grupos_tutoria::get_relationship_cohort_estudantes($relationship->id);

        // Para cada grupo de tutoria
        foreach ($grupos as $grupo) {
            foreach ($this->atividades_cursos as $courseid => $atividades) {
                foreach ($atividades as $atividade) {

                    switch (get_class($atividade)) {
                        case 'report_unasus_assign_activity':
                            $params = array(
                                'courseid' => $courseid,
                                'enrol_courseid' => $courseid,
                                'assignmentid' => $atividade->id,
                                'assignmentid2' => $atividade->id,
                                'relationship_id' => $relationship->id,
                                'cohort_relationship_id' => $cohort_estudantes->id,
                                'grupo' => $grupo->id);
                            $query = query_atividades();
                            break;
                        case 'report_unasus_forum_activity':
                            $params = array(
                                'courseid' => $courseid,
                                'enrol_courseid' => $courseid,
                                'relationship_id' => $relationship->id,
                                'cohort_relationship_id' => $cohort_estudantes->id,
                                'grupo' => $grupo->id,
                                'forumid' => $atividade->id);
                            $query = query_postagens_forum();
                            break;
                        case 'report_unasus_quiz_activity':
                            $params = array(
                                'assignmentid' => $atividade->id,
                                'assignmentid2' => $atividade->id,
                                'courseid' => $courseid,
                                'enrol_courseid' => $courseid,
                                'relationship_id' => $relationship->id,
                                'cohort_relationship_id' => $cohort_estudantes->id,
                                'grupo' => $grupo->id,
                                'forumid' => $atividade->id);
                            $query = query_quiz();
                    }

                    $result = $DB->get_records_sql($query, $params);

                    foreach ($result as $r){
                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                        $nota = null;
                        $grademax = (isset($r->grademax)) ? $r->grademax : 100;

                        //Atividade tem nota
                        if (isset($r->grade)) {
                            $tipo = dado_boletim::ATIVIDADE_COM_NOTA;
                            $nota = $r->grade;
                        } else {
                            $tipo = dado_boletim::ATIVIDADE_SEM_NOTA;
                        }
                        #fixme: Falta inserir atividade com nota final

                        $lista_atividades[$r->userid][$atividade->id] = new dado_boletim($tipo, $atividade->id, $nota, $grademax);
                    }
                }
            }
        }

        echo '<pre>';
        die(print_r($lista_atividades));

        $dados = array();

        $estudantes[] = $lista_atividades;

        $lista_atividades = null;

        // Ou pelo grupo de tutoria do estudante
        if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
            $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] = $estudantes;
        }

        return $dados;
    }

    public function get_table_header($mostrar_nota_final = true, $mostrar_total = false) {
        $atividades_cursos = get_atividades_cursos($this->get_modulos_ids(), $mostrar_nota_final, $mostrar_total, false, true);
        $header = array();

        foreach ($atividades_cursos as $course_id => $atividades) {
            if(isset($atividades[0]->course_name)){
                $course_url = new moodle_url('/course/view.php', array('id' => $course_id, 'target' => '_blank'));
                $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));
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