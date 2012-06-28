<?php

// chamada do arquivo de filtro
require_once($CFG->dirroot . '/report/unasus/filter.php');

defined('MOODLE_INTERNAL') || die();

class report_unasus_renderer extends plugin_renderer_base {

    public function page_atividades_vs_notas_atribuidas() {
        $output = $this->header();
        $output .= $this->heading('Relatório de Atividades vs Notas Atribuídas');

        //barra de filtro
        $filter_form = new filter_tutor_polo();
        $output .= get_form_display($filter_form);

        //Criação da tabela
        $table = $this->table_atividade_vs_nota_atribuidas();
        $output .= html_writer::table($table);

        //footer é o footer + a barra de navegação lateral
        $output .= $this->footer();
        return $output;
    }

    protected function table_atividade_vs_nota_atribuidas() {
        //criacao da tabela
        $table = new html_table();
        $table->attributes['class'] = "relatorio-unasus atividades generaltable";
        $table->tablealign = 'center';

        //cabecalho
        $table->head = array("Estudante", "Atividade 1", "Atividade 2", "Atividade 3");
        $table->data = array();

        //Chamada dos valores que populam a tabela
        $dadostabela = get_dados_dos_alunos();
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
                        $cell->attributes = array('class' => $valor->get_css_class());
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

}
