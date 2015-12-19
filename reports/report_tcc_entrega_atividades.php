<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_entrega_atividades extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = false;

        // Filtro orientadores
        $this->mostrar_filtro_orientadores = true;

        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = true;
        $this->mostrar_filtro_cohorts = true;
        $this->mostrar_filtro_modulos = true;
        $this->mostrar_filtro_intervalo_tempo = false;
        $this->mostrar_aviso_intervalo_tempo = false;
    }

    public function render_report_default($renderer) {
        echo $renderer->build_page();
    }

    public function render_report_table($renderer) {
        $this->mostrar_barra_filtragem = false;
        echo $renderer->build_report($this);
    }

    public function get_dados() {
        // Recupera dados auxiliares
        $nomes_estudantes = local_tutores_grupo_orientacao::get_estudantes($this->get_categoria_turma_ufsc());

        /*  associativo_atividades[modulo][id_aluno][atividade]
         *
         * Para cada módulo ele lista os alunos com suas respectivas atividades (atividades e foruns com avaliação)
         */
        $associativo_atividades = loop_atividades_e_foruns_de_um_modulo(null, null, null, null, false, true);

        $atraso = 0;
        $datetime = new DateTime(date('Y-m-d'));

        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();

            foreach ($array_dados as $id_aluno => $aluno) {
                $lista_atividades[] = new report_unasus_student($nomes_estudantes[$id_aluno], $id_aluno, $this->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);
                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */
                    $atraso = null;

                    if ($atividade instanceof report_unasus_data_empty) {
                        $lista_atividades[] = new report_unasus_dado_nao_aplicado_render();
                        continue;
                    }
                }

                $status = $aluno[0]->status;
                $state_date = $aluno[0]->state_date;

                foreach ($status as $chapter => $state) {
                    switch ($state){
                        case 'done':
                            $tipo = report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_AVALIADO;
                            break;
                        case 'review':
                            $tipo = report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_REVISAO;
                            if($state_date[$chapter] != 'null'){
                                $datetime1 = new DateTime($state_date[$chapter]);
                                $atraso = $datetime1->diff($datetime)->days;
                            }
                            break;
                        case 'draft':
                            $tipo = report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_RASCUNHO;
                            if($state_date[$chapter] != 'null'){
                                $datetime1 = new DateTime($state_date[$chapter]);
                                $atraso = $datetime1->diff($datetime)->days;
                            }
                            break;
                        default: // Estado ou 'null' ou 'empty'
                            $tipo = report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_NAO_APLICADO;
                            break;
                    }

                    $lista_atividades[] = new report_unasus_dado_tcc_entrega_atividades_render($tipo, $chapter, $atraso);
                }

            $estudantes[] = $lista_atividades;
            $lista_atividades = null;

            }
            $dados[local_tutores_grupo_orientacao::grupo_orientacao_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] = $estudantes;
        }

        return ($dados);
    }

    public function get_table_header() {
        $header = $this->get_table_header_tcc_portfolio_entrega_atividades(true);

        foreach ($header as $key => $modulo) {
            array_unshift($modulo, 'Resumo');
            $header[$key] = $modulo;
        }

        return $header;
    }

} 