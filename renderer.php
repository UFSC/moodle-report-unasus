<?php

// chamada do arquivo de filtro
require_once($CFG->dirroot . '/report/unasus/filter.php');

defined('MOODLE_INTERNAL') || die();

class report_unasus_renderer extends plugin_renderer_base {

    const RELATORIO_ATIVIDADE_VS_NOTA = 0;
    const RELATORIO_ENTREGA_ATIVIDADE = 1;
    const RELATORIO_ACOMPANHAMENTO_DE_AVALIACAO = 2;

    /**
     *  Cria o cabeçalho padrão para os relatórios
     * 
     * @param [$title] titulo para a página
     * @return Form cabeçalho, título da página e barra de filtragem
     */
    public function default_header($title = null) {
        $output = $this->header();
        $output .= $this->heading($title);

        //barra de filtro
        $filter_form = new filter_tutor_polo();
        $output .= get_form_display($filter_form);
        return $output;
    }

    /**
     * @return Form barra lateral de navegação e footer 
     */
    public function default_footer() {
        return $this->footer();
    }

    /**
     * Cria a tabela dos relatorios, a aplicacao do css irá depender de qual foi 
     * o relatório que invocou esta funcao
     *  
     * @param int $tipo_relatorio deve ser uma das constantes $RELATORIO_ATIVIDADE_VS_NOTA,
     * $RELATORIO_ENTREGA_ATIVIDADE ou $RELATORIO_ACOMPANHAMENTO_DE_AVALIACAO;
     * @param array() $dadostabela dados para alimentar a tabela
     * 
     * 
     * @return html_table
     */
    public function default_table($tipo_relatorio, $dadostabela) {
        //criacao da tabela
        $table = new html_table();
        $table->attributes['class'] = $this->get_css_table_class($tipo_relatorio);
        $table->tablealign = 'center';

        
        //Com a api default de criacao de tabelas é impossivel ter uma header
        //com duas linhas, no caso de uma linha a criação seria a seguinte:
        //$table->head = array('Estudante', 'Atividade 1', 'Atividade 2', 'Atividade 3');

        $table->data = array();
        
        $heading1 = array(
            new html_table_cell(),
            new html_table_cell('Modulo 1'),
            new html_table_cell(),
            new html_table_cell('Modulo 2'));
        
        $heading2 = array(
            new html_table_cell('Estudante'), 
            new html_table_cell('Atividade 1'), 
            new html_table_cell('Atividade 2'), 
            new html_table_cell('Atividade 1'));
        
        for ($index = 0; $index < 4; $index++) {
            $heading1[$index]->text ? $heading1[$index]->header = true : 
                                      $heading1[$index]->attributes = array('class'=>'blank');
            $heading2[$index]->header = true;
        }
        $table->data[] = new html_table_row($heading1);
        $table->data[] = new html_table_row($heading2);
        
        

        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            $cel_tutor = new html_table_cell($tutor);
            $cel_tutor->attributes = array('class' => 'tutor');
            $cel_tutor->colspan = 4;
            $row_tutor = new html_table_row();
            $row_tutor->cells[] = $cel_tutor;
            $table->data[] = $row_tutor;

            //atividades de cada aluno daquele dado tutor
            foreach ($alunos as $aluno) {
                $row = new html_table_row();
                foreach ($aluno as $valor) {
                    if (is_a($valor, 'Avaliacao')) {
                        $cell = new html_table_cell($valor->to_string());
                        $cell->attributes = array(
                            'class' => $this->get_css_cell_class($tipo_relatorio, $valor));
                    } else { // Aluno
                        $cell = new html_table_cell($valor);
                        $cell->header = true;
                        $cell->attributes = array('class' => 'estudante');
                    }

                    $row->cells[] = $cell;
                }
                $table->data[] = $row;
            }
        }

        return $table;
    }

    /**
     * @param local_const $tipo_relatorio
     * @return string
     */
    private function get_css_table_class($tipo_relatorio) {
        switch ($tipo_relatorio) {
            case report_unasus_renderer::RELATORIO_ATIVIDADE_VS_NOTA:
                return "relatorio-unasus atividades generaltable";
            default:
                break;
        }
    }

    /**
     * @param type $tipo_relatorio
     * @param Avaliacao $avaliacao
     * @return string 
     */
    private function get_css_cell_class($tipo_relatorio, $avaliacao) {
        switch ($tipo_relatorio) {
            case report_unasus_renderer::RELATORIO_ATIVIDADE_VS_NOTA:
                return $avaliacao->get_css_class();
            default:
                break;
        }
    }

    /**
     * Cria a página referente ao relatorio atividade vs notas atribuidas
     * 
     * @return Form 
     */
    public function page_atividades_vs_notas_atribuidas() {
        $output = $this->default_header('Relatório de Atividades vs Notas Atribuídas');

        //Criação da tabela
        $table = $this->default_table(report_unasus_renderer::RELATORIO_ATIVIDADE_VS_NOTA, get_dados_dos_alunos());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

}

