<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');


class report_modulos_concluidos extends Factory {

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
        $nomes_estudantes = grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $grupos = grupos_tutoria::get_grupos_tutoria($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        $dados = array();

        // Para cada grupo de tutoria
        foreach ($grupos as $grupo) {
            $estudantes = array();

            foreach ($this->atividades_cursos as $courseid => $atividades) {
                foreach ($atividades as $atividade) {

                    $result = get_atividades(get_class($atividade), $atividade, $courseid, $grupo, $this);

                    foreach ($result as $r){
                        // Evita que o objeto do estudante seja criado em toda iteração do loop
                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                        $full_grade[$r->userid] = grade_get_course_grade($r->userid, $atividade->course_id);

                        if(isset($full_grade[$r->userid])){
                            $final_grade = $full_grade[$r->userid]->str_grade;
                        } else if(empty($final_grade)){
                            $final_grade = 'Não há atividades avaliativas para o módulo';
                        }
                        if (!array_key_exists($atividade->course_id, $lista_atividades)) {
                            $lista_atividades[$r->userid][$atividade->course_id] = new dado_modulos_concluidos(sizeof($modulos), $final_grade, $atividade);
                        }
                    }

                    // Auxiliar para agrupar tutores corretamente
                    $estudantes = $lista_atividades;
                }
            }

            if ($this->agrupar_relatorios == AGRUPAR_TUTORES) {
                $dados[grupos_tutoria::grupo_tutoria_to_string($this->get_categoria_turma_ufsc(), $grupo->id)] = $estudantes;
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
            $header[] = new dado_modulo($h[0]->course_id, $h[0]->course_name);
        }

        return $header;
    }
}