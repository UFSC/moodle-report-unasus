<?php

// chamada do arquivo de filtro
require_once($CFG->dirroot . '/report/unasus/filter.php');

defined('MOODLE_INTERNAL') || die();

class report_unasus_renderer extends plugin_renderer_base {

    /**
     * Cria o cabeçalho padrão para os relatórios
     *
     * @param String $title titulo para a página
     * @return String cabeçalho, título da página e barra de filtragem
     */
    public function default_header($title = null) {
        $output = $this->header();
        $output .= $this->heading($title);

        //barra de filtro
        $form_attributes = array('class' => 'filter_form');
        $filter_form = new filter_tutor_polo(null, null, 'post', '', $form_attributes);
       
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
     * @param Array $dadostabela dados para alimentar a tabela
     * @param String $css_class classe css para aplicar a tabela
     * @return html_table
     */
    public function default_table($dadostabela, $css_class) {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $css_class generaltable";
        $table->tablealign = 'center';

        $header = array();
        $header['Módulo 1'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
        $header['Módulo 2'] = array('Atividade 1','Atividade 2','Atividade 3','Atividade 4');
        $table->build_double_header($header);

        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            $cel_tutor = new html_table_cell($tutor);
            $cel_tutor->attributes = array('class' => 'tutor');
            $cel_tutor->colspan = count($alunos[0]); // expande a célula com nome dos tutores
            $row_tutor = new html_table_row();
            $row_tutor->cells[] = $cel_tutor;
            $table->data[] = $row_tutor;

            //atividades de cada aluno daquele dado tutor
            foreach ($alunos as $aluno) {
                $row = new html_table_row();
                foreach ($aluno as $valor) {
                    if (is_a($valor, 'unasus_data')) {
                        $cell = new html_table_cell($valor);
                        $cell->attributes = array(
                            'class' => $valor->get_css_class());
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
     * Cria a página referente ao relatorio atividade vs notas atribuidas
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_atividades_vs_notas_atribuidas($css_class) {
        $output = $this->default_header('Relatório de Atividades vs Notas Atribuídas');

        //Criação da tabela
        $table = $this->default_table(get_dados_dos_alunos(), $css_class);
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }
    
    /**
     * Cria a página referente ao relatorio de entrega de atividades
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_entrega_de_atividades($css_class){
        $output = $this->default_header('Relatório de Acompanhamento de Entrega de Atividades');

        //Criação da tabela
        $table = $this->default_table(get_dados_entrega_atividades(), $css_class);
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }
    
    /**
     * Cria a página referente ao relatorio de acompanhamento de avaliacao de atividades
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_acompanhamento_de_avaliacao($css_class){
        $output = $this->default_header('Relatório de Acompanhamento de Avaliação de Atividades');

        //Criação da tabela
        $table = $this->default_table(get_dados_acompanhamento_de_avaliacao(),$css_class);
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }
    
    /**
     * Cria a página referente ao relatorio de Atividades Postadas e não Avaliadas
     * @param string $css_class classe css para aplicar na tabela
     * @return String
     */
    public function page_atividades_nao_avaliadas($css_class){
        $output = $this->default_header('Relatório de Atividades Postadas e Não Avaliadas');

        //Criação da tabela
        $table = $this->default_table(get_dados_atividades_nao_avaliadas(),$css_class);
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

}

