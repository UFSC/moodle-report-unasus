<?php
defined('MOODLE_INTERNAL') || die();

class report_unasus_renderer extends plugin_renderer_base {

    private $report;

    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        if (optional_param('relatorio', null, PARAM_ALPHANUMEXT) !== null) {
            $this->report = optional_param('relatorio', null, PARAM_ALPHANUMEXT);
        } else {
            $this->report = optional_param('grafico', null, PARAM_ALPHANUMEXT);
        }
    }

    /*
     * Função responsável pela construção do relatório de forma dinâmica.
     * Ele primeiramente cria o cabeçalho da página, depois o filtro e a legenda
     * e por ultimo a tabela.
     *
     * O titulo da página está nas internationalization strings /unasus/lang/IDIOMA/report_unasus
     * e sua busca é feita pelo get_string da moodle API
     *
     * Todos os métodos e classes possuem seu nome de acordo com o report:
     * -Classe de dados: dado_{NOME DO REPORT}
     * -Método que faz a busca no banco de dados: get_dados_{NOME DO REPORT}
     * -Método que pega o array do cabeçalho da tabela: get_table_header_{NOME DO REPORT}
     *
     * @return String $output
     */

    public function build_report() {
        global $CFG;
        $output = $this->default_header();
        $output .= $this->build_filter(true);

        $data_class = "dado_{$this->report}";

        $output .= html_writer::start_tag('div', array('class' => 'relatorio-unasus right_legend'));

        $output .= $this->build_legend(call_user_func("{$data_class}::get_legend"));

        $output .= html_writer::end_tag('div');
        //end link

        $dados_method = "get_dados_{$this->report}";
        $header_method = "get_table_header_{$this->report}";

        $table = $this->default_table($dados_method(), $header_method());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    public function build_page() {
        global $CFG;
        $output = $this->default_header();
        $output .= $this->build_filter();
        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a barra de legenda para os relatórios
     * @param array $legend itens da legenda, é do tipo ["classe_css"]=>["Descricao da legenda]
     * @return String
     */
    public function build_legend($legend) {
        if ($legend === false) {
            return null;
        }
        $output = html_writer::start_tag('fieldset', array('class' => "generalbox fieldset relatorio-unasus {$this->report}"));
        $output .= html_writer::tag('legend', 'Legenda', array('class' => 'legend'));
        $output .= html_writer::start_tag('dl');
        foreach ($legend as $class => $description) {
            $output .= html_writer::tag('dt', '', array('class' => "{$class}"));
            $output .= html_writer::tag('dd', "{$description}");
        }
        $output .= html_writer::end_tag('dl');
        $output .= html_writer::end_tag('fieldset');
        return $output;
    }

    /**
     * Cria o cabeçalho padrão para os relatórios
     * @return String cabeçalho, título da página e barra de filtragem
     */
    public function default_header() {
        $output = $this->header();
        //$output .= $this->help_icon('ativ','report_unasus');
        //$output .= $this->heading($title);

        $title = get_string($this->report, 'report_unasus');

        if ($title != "[[$this->report]]") {
            $output .= $this->heading_with_help($title, $this->report, 'report_unasus');
        } else {
            $output .= $this->heading($title);
        }

        return $output;
    }

    /**
     * Cria a barra de Filtros
     * @return filter_tutor_polo $output
     */
    public function build_filter($hide_filter = false) {
        global $CFG;
        $output = html_writer::start_tag('form', array('action' => "{$CFG->wwwroot}/report/unasus/index.php?relatorio={$this->report}",
                  'method' => 'post', 'accept-charset' => 'utf-8', 'id' => 'filter_form'));

        $output .= html_writer::start_tag('fieldset', array('class' => 'relatorio-unasus fieldset'));
        $output .= html_writer::nonempty_tag('legend', 'Filtrar Estudantes');

        $css_class = ($hide_filter == true) ?'visible hidden':'hidden';
        $output .= html_writer::nonempty_tag('button','Mostrar Filtros',
              array('id'=>'button-mostrar-filtro', 'type'=>'button', 'class'=>"relatorio-unasus botao-ocultar {$css_class}"));

        $output .= html_writer::start_tag('div', array('class'=>"relatorio-unasus conteudo-filtro", 'id' => 'div_filtro'));

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'report_hidden', 'value' => "$this->report"));

        $output .= html_writer::label('Estado da Atividade: ', 'select_estado');
        $output .= html_writer::select(array('Em Aberto', 'Em Dia', 'Expirado', 'Fora do Prazo'), 'prazo_select', '', false, array('id' => 'select_estado'));

        $output .= html_writer::start_tag('div', array('id'=>'div-multiple'));

        $filter_modulos = html_writer::label('Filtrar Modulos:', 'multiple_modulo');
        $filter_modulos .= html_writer::select(get_nomes_modulos(), 'multiple_modulo', '', false, array('multiple' => 'multiple', 'id' => 'multiple_modulo'));
        $output .= html_writer::tag('div', $filter_modulos, array('class' => 'multiple_list'));

        $filter_polos = html_writer::label('Filtrar Polos:', 'multiple_polo');
        $filter_polos .= html_writer::select(get_nomes_polos(), 'multiple_polo', '', false, array('multiple' => 'multiple', 'id' => 'multiple_polo'));
        $output .= html_writer::tag('div', $filter_polos, array('class' => 'multiple_list'));

        $filter_tutores = html_writer::label('Filtrar Tutores:', 'multiple_tutor');
        $filter_tutores .= html_writer::select(get_nomes_tutores(), 'multiple_tutor', '', false, array('multiple' => 'multiple', 'id' => 'multiple_tutor'));
        $output .= html_writer::tag('div', $filter_tutores, array('class' => 'multiple_list'));

        $output .= html_writer::end_tag('div');


        $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'tabela', 'id' => 'radio_tabela'));
        $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/table.png\">Tabela de Dados", 'radio_tabela', true, array('class' => 'radio'));
        $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_valores', 'id' => 'radio_valores'));
        $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/chart.png\">Gráfico de Valores", 'radio_valores', true, array('class' => 'radio'));
        $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_porcentagens', 'id' => 'radio_porcentagem'));
        $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/pct.png\">Gráfico de Porcentagem", 'radio_porcentagem', true, array('class' => 'radio'));

        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Gerar relatório'));

        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('fieldset');
        $output .= html_writer::end_tag('form');
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
     * @TODO construir uma simple table que não necessita ter divisões de tutor/polo barra azul
     * @param Array $dadostabela dados para alimentar a tabela
     * @param Array $header header para a tabela, pode ser um
     *              array('value1','value2','value3') ou um array de chaves valor
     *              array('modulo'=> array('value1','value2'))
     * @return html_table
     */
    public function default_table($dadostabela, $header) {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report generaltable";
        $table->tablealign = 'center';

        $ultima_atividade_modulo = array();
        $ultimo_alvo = 0;
        $ultima_atividade_modulo[] = $ultimo_alvo;
        foreach ($header as $activities) {
            $ultimo_alvo += count($activities);
            $ultima_atividade_modulo[] = $ultimo_alvo;
        }

        $header_keys = array_keys($header);
        if (is_array($header[$header_keys[0]])) { // Double Header
            $table->build_double_header($header);
            $table->attributes['class'] .= " divisao-por-modulos";
        } else {
            $table->build_single_header($header);
        }

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
            $count = 0;
            foreach ($alunos as $aluno) {
                $row = new html_table_row();
                foreach ($aluno as $valor) {
                    if (is_a($valor, 'unasus_data')) {
                        $cell = new html_table_cell($valor);
                        if (in_array($count, $ultima_atividade_modulo)) {
                            $cell->attributes = array(
                                'class' => $valor->get_css_class()." ultima_atividade");
                        } else {
                            $cell->attributes = array(
                                'class' => $valor->get_css_class());
                        }
                    } else { // Aluno
                        $cell = new html_table_cell($valor);
                        $cell->header = true;
                        $cell->attributes = array('class' => 'estudante ultima_atividade');
                    }

                    $row->cells[] = $cell;
                    $count++;
                }
                $table->data[] = $row;
                $count = 0;
            }
        }

        return $table;
    }

    /**
     *
     * @TODO REFATORAR com default_table
     * @param type $dadostabela
     * @param type $header
     * @return report_unasus_table
     */
    public function table_tutores($dadostabela, $header) {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report generaltable";
        $table->tablealign = 'center';

        $header_keys = array_keys($header);
        if (is_array($header[$header_keys[0]])) { // Double Header
            $table->build_double_header($header, 'Tutores');
        } else {
            $table->build_single_header($header);
        }

        //atividades de cada aluno daquele dado tutor
        foreach ($dadostabela as $aluno) {
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


        return $table;
    }

    /**
     * Cria a tabela dos relatorios, a aplicacao do css irá depender de qual foi
     * o relatório que invocou esta funcao
     * @TODO construir uma simple table que não necessita ter divisões de tutor/polo barra azul
     * @param Array $dadostabela dados para alimentar a tabela
     * @param Array $header header para a tabela, pode ser um
     *              array('value1','value2','value3') ou um array de chaves valor
     *              array('modulo'=> array('value1','value2'))
     * @return html_table
     */
    public function table_atividade_nao_postada($dadostabela, $header_size) {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report generaltable";
        $table->tablealign = 'center';

        $table->headspan = array(1, $header_size);
        $table->head = array('Estudante', 'Atividades não Postadas');


        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            $cel_tutor = new html_table_cell($tutor);
            $cel_tutor->attributes = array('class' => 'tutor');
            $cel_tutor->colspan = $header_size + 1; // expande a célula com nome dos tutores
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
     * Cria a página referente ao relatorio de Atividades Postadas e não Avaliadas
     * @TODO esse metodo não necessita de uma legenda e usa uma tabela diferente
     * @return String
     */
    public function page_atividades_nao_avaliadas() {
        $output = $this->default_header();
        $output .= $this->build_filter();

        $table = $this->table_tutores(get_dados_atividades_nao_avaliadas(), get_header_modulo_atividade_geral());
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de Estudantes sem Atividades Postadas (fora do prazo)
     * @TODO esse metodo não usa uma estrutura de dados definida pois é totalmente adverso ao resto.
     * @return String
     */
    public function page_estudante_sem_atividade_postada() {
        $output = $this->default_header();
        $output .= $this->build_filter();

        $dados_atividades = get_dados_estudante_sem_atividade_postada();
        $max_size = 0;
        foreach ($dados_atividades as $tutor) {
            foreach ($tutor as $atividades) {
                if ($max_size < count($atividades))
                    $max_size = count($atividades);
            }
        }

        $table = $this->table_atividade_nao_postada($dados_atividades, $max_size);
        $output .= html_writer::table($table);

        $output .= $this->default_footer();
        return $output;
    }

    public function build_graph($porcentagem = false) {
        global $PAGE, $CFG;

        $output = $this->default_header();

        $PAGE->requires->js(new moodle_url("/report/unasus/graph/jquery.min.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/highcharts/js/highcharts.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/highcharts/js/modules/exporting.js"));

        $output .= $this->build_filter(true);

        $dados_method = "get_dados_grafico_{$this->report}";
        $dados_class = "dado_{$this->report}";

        // verifica se o gráfico foi implementado
        if (!function_exists($dados_method)) {
            $output .= $this->box(get_string('unimplemented_graph_error', 'report_unasus'));
            $output .= $this->default_footer();
            return $output;
        }

        $legend = call_user_func("$dados_class::get_legend");

        $PAGE->requires->js_init_call('M.report_unasus.init_graph', array($dados_method(),
            array_values($legend),
            get_string($this->report, 'report_unasus'), $porcentagem));

        $output .= '<div id="container" class="container"></div>';
        $output .= $this->default_footer();

        return $output;
    }

}

