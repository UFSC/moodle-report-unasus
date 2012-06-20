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
                    Situação: <select style="margin-bottom: 10px;">
                                <option> Aberto </option>
                                <option>Em Dia</option>
                                <option>Expirado</option>
                                <option>Fora do Prazo</option>
                              </select><br>
                    Filtrar Estudantes: 
                        <input type="radio" class="radiofilter" name="filtroestudante" value="tutor">por Tutor
                        <input type="radio" class="radiofilter" name="filtroestudante" value="polo">por Polo
                        <br>';
        $output .= $this->multiplebox_tutor();
        $output .= '<input type="submit" value="Filtrar">
                </fieldset></form>';
        return $output;
    }
    
    protected function multiplebox_tutor(){
        $output = '<select multiple="multiple" name="tutormultiple" class="tutor_multiple">
                        <option value="tut1"> Tutor 1 - alfa </option>
                        <option value="tut2"> Tutor 2 - beta </option>
                        <option value="tut3"> Tutor 3 - gama </option>
                   </select><br>';
        return $output;
    }
    
    protected function javascript_tutor_polo_filter(){
        
    }

}
