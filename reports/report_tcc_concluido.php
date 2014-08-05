<?php

defined('MOODLE_INTERNAL') || die;

class report_tcc_concluido extends Factory {

    protected function initialize() {
        $this->mostrar_filtro_tutores = false;
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
        $nomes_estudantes = tutoria::get_estudantes_curso_ufsc($this->get_curso_ufsc());

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
                    $atraso = null;

                    if ($atividade instanceof report_unasus_data_empty) {
                        $lista_atividades[] = new dado_nao_aplicado();
                        continue;
                    }

                    // Se a atividade não foi entregue
                    if ($atividade->has_evaluated()) {
                        // E não tem entrega prazo
                        $tipo = dado_tcc_concluido::ATIVIDADE_CONCLUIDA;
                    } else {
                        //atividade com data de entrega no futuro, nao entregue mas dentro do prazo
                        $tipo = dado_tcc_concluido::ATIVIDADE_NAO_CONCLUIDA;
                    }
                    $lista_atividades[] = new dado_tcc_concluido($tipo, $atividade->source_activity->id, $atraso);
                }

                $chapter = 'abstract';

                for ($i = 0; $i <= 2; $i++) {

                    if ($aluno[0]->has_evaluated_chapters($chapter))
                        $tipo = dado_tcc_concluido::ATIVIDADE_CONCLUIDA;
                    else
                        $tipo = dado_tcc_concluido::ATIVIDADE_NAO_CONCLUIDA;

                    $lista_atividades[] = new dado_tcc_concluido($tipo, null, $atraso);

                    $chapter = ($i == 0) ? 'presentation' : 'final_considerations';
                }

                $estudantes[] = $lista_atividades;
                $lista_atividades = null;
            }
            $dados[tutoria::grupo_orientacao_to_string($this->get_curso_ufsc(), $grupo_id)] = $estudantes;
        }

        return ($dados);
    }

    public function get_table_header() {
        $header = $this->get_table_header_tcc_portfolio_entrega_atividades(true);

        foreach ($header as $key => $modulo) {
            array_push($modulo, 'Resumo');
            array_push($modulo, 'Introdução');
            array_push($modulo, 'Considerações Finais');

            $header[$key] = $modulo;
        }

        return $header;
    }

} 