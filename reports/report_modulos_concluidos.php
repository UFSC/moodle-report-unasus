<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');


class report_modulos_concluidos extends report_unasus_factory {

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

    public function render_report_default($renderer){
        echo $renderer->build_page();
    }

    public function render_report_table($renderer){
        $this->mostrar_barra_filtragem = false;
        $this->texto_cabecalho = 'Tutores';
        echo $renderer->build_report($this);
    }

    public function get_dados(){

        $modulos = $this->modulos_selecionados;

        // Recupera dados auxiliares
        $nomes_estudantes = local_tutores_grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $grupos = local_tutores_grupos_tutoria::get_grupos_tutoria($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        $dados = array();

        $atividade_nota_final = new \StdClass();

        // Para cada grupo de tutoria
        foreach ($grupos as $grupo) {
            $estudantes = array();

            foreach ($this->atividades_cursos as $courseid => $atividades) {

                array_push($atividades, $atividade_nota_final);

                $database_courses = ($courseid == 129 || $courseid == 130 || $courseid == 131);

                foreach ($atividades as $atividade) {

                    $result = report_unasus_get_atividades(get_class($atividade), $atividade, $courseid, $grupo, $this, true);

                    foreach ($result as $r){
                        // Evita que o objeto do estudante seja criado em toda iteração do loop
                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                        if ($r->name_activity == 'nota_final_activity' && !isset($atividade->id) && ($database_courses)){
                            $grade = null;

                            if (isset($r->grade)) {
                                $nota = $r->grade;
                                $grade = substr($nota, 0, strpos($nota, '.') + 3);
                            }

                            $lista_atividades[$r->userid][] = new report_unasus_dado_modulos_concluidos_render(sizeof($modulos), $grade, $atividade);

                        } else if ( !($database_courses) && isset($atividade->course_id)) {
                            $full_grade[$r->userid] = grade_get_course_grade($r->userid, $atividade->course_id);

                            if(isset($full_grade[$r->userid])){
                                $final_grade = $full_grade[$r->userid]->str_grade;
                            } else if(empty($final_grade)){
                                $final_grade = 'Não há atividades avaliativas para o módulo';
                            }
                            if (!array_key_exists($atividade->course_id, $lista_atividades)) {
                                $lista_atividades[$r->userid][$atividade->course_id] = new report_unasus_dado_modulos_concluidos_render(sizeof($modulos), $final_grade, $atividade);
                            }
                        }
                    }

                    // Auxiliar para agrupar tutores corretamente
                    if(!empty($lista_atividades)){
                        $estudantes = $lista_atividades;
                    }
                }
            }

            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[local_tutores_grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo->id)] = $estudantes;
            }

            $lista_atividades = null;
        }

        return $dados;
    }

    public function get_table_header(){

        $activities = $this->get_table_header_modulos_atividades();

        $header = array();
        $header[] = 'Estudantes';
        foreach ($activities as $h) {
            $header[] = new report_unasus_dado_modulo_render($h[0]->course_id, $h[0]->course_name);
        }

        return $header;
    }
}