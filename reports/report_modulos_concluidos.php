<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/datalib.php');

class report_modulos_concluidos extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_grupo_tutoria = true;
        $this->mostrar_filtro_tutores = false;
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

    public function get_dados() {
        $modulos = $this->get_modulos_ids();

        $atividades_config_curso = report_unasus_get_activities_config_report($this->get_categoria_turma_ufsc(), $modulos);

        // Recupera dados auxiliares
        $nomes_estudantes = local_tutores_grupos_tutoria::get_estudantes($this->get_categoria_turma_ufsc());
        $grupos = local_tutores_grupos_tutoria::get_grupos_tutoria_new($this->get_categoria_turma_ufsc(), $this->tutores_selecionados);

        $dados = array();

        // Para cada grupo de tutoria
        foreach ($grupos as $grupo) {
            $estudantes = array();

            // para curso/modulo pega a lista de atividades
            foreach ($this->atividades_cursos as $courseid => $atividades) {

                // para a lista de atividades pega cada atividade
                foreach ($atividades as $atividade) {
                    $result = report_unasus_get_atividades(get_class($atividade), $atividade, $courseid, $grupo, $this, true);

                    // para a lista de atividadeas dos alunos pega cada atividade (do aluno)
                    foreach ($result as $r){

                        // Evita que o objeto do estudante seja criado em toda iteração do loop
                        if (!(isset($lista_atividades[$r->userid][0]))) {
                            $lista_atividades[$r->userid][] = new report_unasus_student($nomes_estudantes[$r->userid], $r->userid, $this->get_curso_moodle(), $r->polo, $r->cohort);
                        }

                            //Variável usada para armazenar o valor retornado da consulta utilizada para verificar se é estudante ou não.
                            //Usada posteiormente na criação do obejto de renderizção dos elementos da tabela (report_unasus_dado_modulos_concluidos_render)
                            $is_studant = $r->is_student;

                        if ( isset($atividade->course_id)) {
                            $full_grade[$r->userid] = grade_get_course_grade($r->userid, $atividade->course_id);

                            if(isset($full_grade[$r->userid])){
                                $final_grade = $full_grade[$r->userid]->str_grade;
                            } else if(empty($final_grade)){
                                $final_grade = 'Não há atividades avaliativas para o módulo';
                            }

                            // se a atividade for setada para ser apresentada,
                                // então continua com as outras checagens
                            if ( array_search($atividade->id, $atividades_config_curso) ) {
                                if (!isset($lista_atividades[$r->userid][$atividade->course_id])) {
                                    $lista_atividades[$r->userid][$atividade->course_id] = new report_unasus_dado_modulos_concluidos_render(sizeof($modulos), $final_grade, $is_studant);
                                }
                                $lista_atividades[$r->userid][$atividade->course_id]->add_atividade($atividade);
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
                // processa estudantes de um grupo
                foreach ($lista_atividades as $userid => $courses) {
                    $userid;

                    // passa pelos cursos de um estudante
                    foreach ($courses as $courseid => $course) {
                        if ($courseid > 0) {
                            $course_instance = get_course($courseid);
                            $info = new completion_info($course_instance);
                            $uid = 'u.id=' . $userid;
                            $progress = $info->get_progress_all($uid);

                            //Se não houver dados para o aluno, ele não faz aquele módulo
                            if(isset($progress[$userid])) {

                                // passa por todas as atividades configuradas daquele módulo
                                foreach ($course->get_atividades() as $atividade_modulo_aluno) {
                                    $pendente = true;
                                    $has_progress = isset($progress[$userid]->progress[$atividade_modulo_aluno->coursemoduleid]);
                                    if ( $has_progress ) {
                                        $pendente = $progress[$userid]->progress[$atividade_modulo_aluno->coursemoduleid]->completionstate == COMPLETION_INCOMPLETE;
                                    }
                                    if ($pendente) {
                                        $course->add_atividade_pendente($atividade_modulo_aluno);
                                    }
                                }
                            }
                        }
                    }
                }

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