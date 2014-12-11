<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_concluido extends Factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = false;

        // Filtro orientadores
        $this->mostrar_filtro_orientadores = true;

        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = false;
        $this->mostrar_filtro_cohorts = false;
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

        $dados = array();
        foreach ($associativo_atividades as $grupo_id => $array_dados) {
            $estudantes = array();

            foreach ($array_dados as $id_aluno => $aluno) {
                $lista_atividades[] = new report_unasus_student($nomes_estudantes[$id_aluno], $id_aluno, $this->get_curso_moodle(), $aluno[0]->polo, $aluno[0]->cohort);
                foreach ($aluno as $atividade) {
                    /** @var report_unasus_data $atividade */

                    if ($atividade instanceof report_unasus_data_empty) {
                        $lista_atividades[] = new dado_nao_aplicado();
                        continue;
                    }
                }

                $chapter = 'chapter1';
                $j = 0;

                for ($i = 0; $i <= 4; $i++) {

                    if($j == 0){
                        if ($aluno[$j]->has_evaluated_chapters('abstract'))
                            $tipo = dado_tcc_concluido::ATIVIDADE_CONCLUIDA;
                        else
                            $tipo = dado_tcc_concluido::ATIVIDADE_NAO_CONCLUIDA;

                        $lista_atividades[] = new dado_tcc_concluido($tipo, 'abstract');
                        $j+=5;
                    }

                    if ($aluno[$i]->has_evaluated_chapters($chapter))
                        $tipo = dado_tcc_concluido::ATIVIDADE_CONCLUIDA;
                    else
                        $tipo = dado_tcc_concluido::ATIVIDADE_NAO_CONCLUIDA;

                    $lista_atividades[] = new dado_tcc_concluido($tipo, $chapter);

                    if($chapter == 'chapter1'){
                        $chapter = 'chapter2';
                    } else if ($chapter == 'chapter2'){
                        $chapter = 'chapter3';
                    } else if ($chapter == 'chapter3'){
                        $chapter = 'chapter4';
                    } else
                        $chapter = 'chapter5';
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