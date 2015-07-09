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

        // Recupera dados auxiliares
        $nomes_estudantes = grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $grupos = grupos_tutoria::get_grupos_tutoria($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        $atividade_nota_final = new \StdClass();

        $dados = array();

        // Para cada grupo de tutoria
        foreach ($grupos as $grupo) {
            $estudantes = array();

            foreach ($this->atividades_cursos as $courseid => $atividades) {
                array_push($atividades, $atividade_nota_final);

                /*echo '<pre>';
                die(print_r($atividades));*/

                foreach ($atividades as $atividade) {

                    $result = get_atividades(get_class($atividade), $atividade, $courseid, $grupo, $this, true);

                    foreach ($result as $r){
                        // Evita que o objeto do estudante seja criado em toda iteração do loop
                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                        $nota = null;
                        $grademax = (isset($r->grademax)) ? $r->grademax : 100;

                        //Atividade tem nota
                        if (isset($r->grade)) {
                            $tipo = dado_boletim::ATIVIDADE_COM_NOTA;
                            $nota = $r->grade;
                        } else if(isset($r->completionstate)) {
                            $tipo = $r->completionstate;
                        } else {
                            $tipo = dado_boletim::ATIVIDADE_SEM_NOTA;
                        }

                        if (isset($atividade->id)) {
                            $lista_atividades[$r->userid][$atividade->id] = new dado_boletim($tipo, $atividade->id, $nota, $grademax);
                        } else {
                            $lista_atividades[$r->userid]['nota_final'] = new dado_nota_final($tipo, $nota, $grademax);
                        }
                    }

                    /*echo '<pre>';
                    die(print_r($atividades));*/

                    // Auxiliar para agrupar tutores corretamente
                    $estudantes = $lista_atividades;
                }
            }

            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo->id)] = $estudantes;
            }

            $lista_atividades = null;
        }

        echo '<pre>';
        die(print_r($dados));

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