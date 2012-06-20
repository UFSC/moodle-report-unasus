<?php

// chamada da biblioteca local
require_once($CFG->dirroot . '/report/atividadevsnota/locallib.php');

defined('MOODLE_INTERNAL') || die();

class report_atividadevsnota_renderer extends plugin_renderer_base {

    public function index_page() {
        $OUTPUT = $this->header();
        $OUTPUT .= $this->heading('Relatório Atividade vs Nota');

        //filter bar
        $OUTPUT .= $this->filter_bar();
        
        $OUTPUT .= "<div class='tutor_tabela'>".get_dados_tutor()->to_string()."</div>";
        
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

        //cabecalho
        $table->head = array("Estudante", "Atividade 1", "Atividade 2", "Atividade 3");
        $table->data = array();
        
        $table->tablealign = 'center';

        //Chamada dos valores que populam a tabela
        $dadostabela1 = get_dados_dos_alunos();
        foreach ($dadostabela1 as $aluno) {
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

        return $table;
    }

    protected function filter_bar() {
        $output = '<form method="post" class="mform"><fieldset class="clearfix" id="newfilter">
                <legend class="ftoggler">Novo Filtro:</legend>
                    Situação: <input type="radio" class="radiofilter" name="situacao" value="Aberto" />Aberto
                              <input type="radio" class="radiofilter" name="situacao" value="Em dia" />Em Dia
                              <input type="radio" class="radiofilter" name="situacao" value="Expirado" />Expirado
                              <input type="radio" class="radiofilter" name="situacao" value="Fora do prazo" />Fora do prazo<br />
                    Tutor: <input type="text" size="30" style="margin-bottom: 10px"/><br />
                    <input type="submit" value="Filtrar">
                </fieldset></form>';
        return $output;
    }

}
