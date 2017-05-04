<?php

defined('MOODLE_INTERNAL') || die;

class report_boletim extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_grupo_tutoria = true;
        $this->mostrar_filtro_tutores = false;
        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = true && report_unasus_verifica_middleware() ;
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

        $modulos_ids = $this->get_modulos_ids();

        $categoria_turma_ufsc = $this->get_categoria_turma_ufsc();
        $atividades_config_curso = report_unasus_get_activities_config_report($categoria_turma_ufsc, $modulos_ids);

        // Recupera dados auxiliares
        $nomes_estudantes = local_tutores_grupos_tutoria::get_estudantes($categoria_turma_ufsc);
        $grupos = local_tutores_grupos_tutoria::get_grupos_tutoria_new($categoria_turma_ufsc, $this->tutores_selecionados);

        $atividade_nota_final = new \StdClass();

        $dados = array();

        // Para cada grupo de tutoria
        foreach ($grupos as $group_id => $grupo) {
            $lista_atividades = array();
            $estudantes_grupo = local_tutores_grupos_tutoria::get_estudantes_grupo_tutoria($categoria_turma_ufsc,
                $group_id);

            foreach ($this->atividades_cursos as $courseid => $atividades) {
                array_push($atividades, $atividade_nota_final);

                foreach ($atividades as $atividade) {

                    $result = report_unasus_get_atividades(get_class($atividade), $atividade, $courseid, $grupo, $this, true);

                    // verifica se está faltando algun estudante nos resultados
                    $estudantes_adicionar = array_diff_key($estudantes_grupo, $result);

                    // Se estiver adiciona
                    foreach ($estudantes_adicionar as $estudante) {
                        $estudante->userid = $estudante->id;
                        $result[$estudante->id] = $estudante;
                        $result[$estudante->id]->name_activity = substr(get_class($atividade), 14);
                    }

                    foreach ($result as $r){
                        // Evita que o objeto do estudante seja criado em toda iteração do loop
                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                        $nota = null;
                        $grademax = (isset($r->grademax)) ? $r->grademax : 100;

                        if (isset($r->is_student) && ($r->is_student === "0")) {
                            // Se não for estudante do curso
                            $tipo = report_unasus_dado_boletim_render::ATIVIDADE_NAO_APLICADO;
                        }

                        //Atividade tem nota
                        else if ( !isset($r->grade) || $r->grade == -1) {
                            $tipo = report_unasus_dado_boletim_render::ATIVIDADE_SEM_NOTA;
                        } else {
                            $tipo = report_unasus_dado_boletim_render::ATIVIDADE_COM_NOTA;
                            $nota = $r->grade;
                        }

                        if ($r->name_activity == 'nota_final_activity') {
                            $lista_atividades[$r->userid][] = new report_unasus_dado_nota_final_render($tipo, $nota, $grademax);
                        } else if (isset($atividade->course_id)) {
                            if (!array_search($atividade->id, $atividades_config_curso)){
                                $lista_atividades[$r->userid][] = new report_unasus_dado_boletim_render($tipo, $atividade->id, $nota, $grademax);
                            }
                        }
                    }

                    // Auxiliar para agrupar tutores corretamente
                    $estudantes = $lista_atividades;
                }
            }

            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[local_tutores_grupos_tutoria::grupo_tutoria_to_string($categoria_turma_ufsc, $grupo->id)] = $estudantes;
            }
        }

        return $dados;
    }

    public function get_table_header($mostrar_nota_final = true, $mostrar_total = false) {

        $categoria_turma_ufsc = $this->get_categoria_turma_ufsc();
        $atividades_cursos = report_unasus_get_atividades_cursos($this->modulos_selecionados, $mostrar_nota_final, $mostrar_total, false, $categoria_turma_ufsc);
        $header = array();

        foreach ($atividades_cursos as $course_id => $atividades) {
            if(isset($atividades[0]->course_name)){
                $course_url = new moodle_url('/course/view.php', array('id' => $course_id, 'target' => '_blank'));
                $course_link = html_writer::link($course_url, $atividades[0]->course_name, array('target' => '_blank'));
                $header[$course_link] = $atividades;
            }
        }

        return $header;
    }

}