<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_entrega_atividades extends Factory {

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
        $nomes_estudantes = grupo_orientacao::get_estudantes($this->get_categoria_turma_ufsc());

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
                        $lista_atividades[] = new dado_nao_aplicado();
                        continue;
                    }
                }

                $j = 0;

                for ($i = 0; $i <= 4; $i++) {

                    if($j == 0){

                        switch ($aluno[$j]->status_abstract){
                            case 'null': // Não concluiu atividades do moodle para fazer o abstract ainda
                                $tipo = dado_tcc_entrega_atividades::ATIVIDADE_NAO_APLICADO;
                                break;
                            case 'review':
                                $tipo = dado_tcc_entrega_atividades::ATIVIDADE_REVISAO;
                                if($aluno[$j]->state_date_abstract != 'null'){
                                    $datetime1 = new DateTime($aluno[$i]->state_date_abstract);
                                    $atraso = $datetime1->diff($datetime)->days;
                                }
                                break;
                            case 'draft':
                                $tipo = dado_tcc_entrega_atividades::ATIVIDADE_RASCUNHO;
                                if($aluno[$j]->state_date_abstract != 'null'){
                                    $datetime1 = new DateTime($aluno[$i]->state_date_abstract);
                                    $atraso = $datetime1->diff($datetime)->days;
                                }
                                break;
                            default:
                                $tipo = dado_tcc_entrega_atividades::ATIVIDADE_AVALIADO;
                                break;
                        }

                        $lista_atividades[] = new dado_tcc_entrega_atividades($tipo, 'abstract', $atraso);
                        $j+=5;
                    }

                    $status_chapter = 'status_chapter1';

                    switch ($aluno[$i]->$status_chapter){
                        case 'review':
                            $tipo = dado_tcc_entrega_atividades::ATIVIDADE_REVISAO;
                            if($aluno[$i]->state_date != 'null'){
                                $datetime1 = new DateTime($aluno[$i]->state_date);
                                $atraso = $datetime1->diff($datetime)->days;
                            }
                            break;
                        case 'draft':
                            $tipo = dado_tcc_entrega_atividades::ATIVIDADE_RASCUNHO;
                            if($aluno[$i]->state_date != 'null'){
                                $datetime1 = new DateTime($aluno[$i]->state_date);
                                $atraso = $datetime1->diff($datetime)->days;
                            }
                            break;
                        case 'done':
                            $tipo = dado_tcc_entrega_atividades::ATIVIDADE_AVALIADO;
                            break;
                        default: // Não concluiu atividades do moodle para fazer o abstract ainda
                            $tipo = dado_tcc_entrega_atividades::ATIVIDADE_NAO_APLICADO;
                            break;
                    }

                    $lista_atividades[] = new dado_tcc_entrega_atividades($tipo, $status_chapter, $atraso);

                    if($status_chapter == 'status_chapter1'){
                        $status_chapter = 'status_chapter2';
                    } else if ($status_chapter == 'status_chapter2'){
                        $status_chapter = 'status_chapter3';
                    } else if ($status_chapter == 'status_chapter3'){
                        $status_chapter = 'status_chapter4';
                    } else
                        $status_chapter = 'status_chapter5';
                }

                $estudantes[] = $lista_atividades;
                $lista_atividades = null;

            }
            $dados[grupo_orientacao::grupo_orientacao_to_string($this->get_categoria_turma_ufsc(), $grupo_id)] = $estudantes;
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