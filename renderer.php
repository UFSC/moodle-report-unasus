<?php

// chamada da biblioteca local
require_once($CFG->dirroot . '/report/unasus/locallib.php');
require_once($CFG->dirroot . '/report/unasus/filter.php');

defined('MOODLE_INTERNAL') || die();

class report_unasus_renderer extends plugin_renderer_base {

    public function index_page() {
        $OUTPUT = $this->header();
        $OUTPUT .= $this->heading('Relatório Atividade vs Nota');

        
        //barra de filtro
        $filter_form = new filter_tutor_polo();
        $OUTPUT .= get_form_display($filter_form);

        //Criação da tabela
        $tabela1 = $this->tabela_at_vs_nota();
        $OUTPUT .= html_writer::table($tabela1);

        //footer é o footer + a barra de navegação lateral
        $OUTPUT .= $this->footer();
        return $OUTPUT;
    }

    protected function tabela_at_vs_nota() {
        //criacao da tabela
        $table = new html_table();
        $table->attributes["class"] = "relatorio-unasus atividades generaltable";
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
            $cel_tutor->attributes = array("class" => "nome_tutor");
            $cel_tutor->colspan = 4;
            $row_tutor = new html_table_row();
            $row_tutor->cells[] = $cel_tutor;
            $table->data[] = $row_tutor;

            //atividades de cada aluno daquele dado tutor
            foreach ($alunos as $aluno) {
                $row = new html_table_row();
                foreach ($aluno as $valor) {
                    $cell;
                    if (is_a($valor, 'Avaliacao')) {
                        $cell = new html_table_cell($valor->to_string());
                        $cell->attributes = array("class" => $valor->get_css_class());
                    } else {
                        $cell = new html_table_cell($valor);
                    }

                    $row->cells[] = $cell;
                }
                $table->data[] = $row;
            }
        }

        return $table;
    }

}
