<?php

defined('MOODLE_INTERNAL') || die();

class report_unasus_renderer extends plugin_renderer_base {

    private $cursos;
    private $curso_ativo;
    private $report;
    private $context;

    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Carrega tipo de renderização (relatório ou gráfico)
        $relatorio = optional_param('relatorio', null, PARAM_ALPHANUMEXT);
        if (!empty($relatorio)) {
            $this->report = $relatorio;
        } else {
            $this->report = optional_param('grafico', null, PARAM_ALPHANUMEXT);
        }

        // Carrega informações sobre cursos UFSC
        $this->cursos = get_cursos_ativos_list();
        $this->curso_ativo = get_course_id();
        $this->curso_ufsc = get_curso_ufsc_id();

        // Contexto
        $courseid = get_course_id();
        $this->context = context_course::instance($courseid);
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
     * - Classe de dados: dado_{NOME DO REPORT}
     * - Método que faz a busca no banco de dados: get_dados_{NOME DO REPORT}
     * - Método que pega o array do cabeçalho da tabela: get_table_header_{NOME DO REPORT}
     *
     * @return String $output
     */

    public function build_report($graficos = true, $dot_chart = false, $tipo_cabecalho = 'Estudantes') {
        global $USER;
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter(true, $graficos, $dot_chart);

        $data_class = "dado_{$this->report}";

        $output .= html_writer::tag('div', $this->build_legend(call_user_func("{$data_class}::get_legend")), array('class' => 'relatorio-unasus right_legend'));

        $dados_method = "get_dados_{$this->report}";
        $header_method = "get_table_header_{$this->report}";

        $modulos_raw = optional_param_array('modulos', null, PARAM_INT);
        $tutores_raw = optional_param_array('tutores', null, PARAM_INT);

        $modulos = get_atividades_cursos(get_modulos_validos($modulos_raw));

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $this->context) && !has_capability('report/unasus:view_all', $this->context)) {
            $tutores_raw = array($USER->id);
        }

        $table = $this->default_table($dados_method($this->curso_ufsc, $this->curso_ativo, $modulos, $tutores_raw), $header_method($modulos_raw), $tipo_cabecalho);
        $output .= html_writer::tag('div', html_writer::table($table), array('class' => 'relatorio-wrapper'));

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página sem os gráficos, para que o usuário possa filtrar sua busca antes de
     * gerar a tabela
     *
     * @param boolean $graficos
     * @param boolean $dot_chart
     * @return String
     */
    public function build_page($graficos = true, $dot_chart = false) {
        $output = $this->default_header();
        $output .= $this->build_filter(false, $graficos, $dot_chart);
        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a barra de legenda para os relatórios
     * @param array $legend itens da legenda, é do tipo ["classe_css"]=>["Descricao da legenda"]
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
            //$class é a mesma classe definida no styles.css
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
     * @return string $output
     */
    public function build_filter($hide_filter = false, $grafico = true, $dot_chart = false) {
        global $CFG;

        //$dados_tutores = grupos_tutoria::get_chave_valor_grupos_tutoria($this->curso_ufsc);


        // Inicio do Form
        $url_filtro = new moodle_url('/report/unasus/index.php', array('relatorio' => $this->report, 'course' => $this->curso_ativo));
        $output = html_writer::start_tag('form', array('action' => $url_filtro,
                  'method' => 'post', 'accept-charset' => 'utf-8', 'id' => 'filter_form'));

        // Fieldset
        $output .= html_writer::start_tag('fieldset', array('class' => 'relatorio-unasus fieldset'));
        $output .= html_writer::nonempty_tag('legend', 'Filtrar Estudantes');

        // Botao de ocultar/mostrar filtros, só aparece com javascript carregado
        $css_class = ($hide_filter == true) ? 'visible hidden' : 'hidden';
        $output .= html_writer::nonempty_tag('button', 'Mostrar Filtros', array('id' => 'button-mostrar-filtro', 'type' => 'button', 'class' => "relatorio-unasus botao-ocultar {$css_class}"));

        // Filtros
        $output .= html_writer::start_tag('div', array('class' => "relatorio-unasus conteudo-filtro", 'id' => 'div_filtro'));

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'report_hidden', 'value' => "$this->report"));

        // Dropdown list
        $output .= html_writer::label('Estado da Atividade: ', 'select_estado');
        $output .= html_writer::select(array('Em Aberto', 'Em Dia', 'Expirado', 'Fora do Prazo'), 'prazo_select', '', false, array('id' => 'select_estado'));

        // Div para os 3 filtros
        $output .= html_writer::start_tag('div', array('id' => 'div-multiple'));

        // Filtro de modulo
        $nome_modulos = get_id_nome_modulos();
        $filter_modulos = html_writer::label('Filtrar Modulos:', 'multiple_modulo');
        $filter_modulos .= html_writer::select($nome_modulos, 'modulos[]', array_keys($nome_modulos), false, array('multiple' => 'multiple', 'id' => 'multiple_modulo'));
        $modulos_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_modulo', 'href' => '#'));
        $modulos_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_modulo', 'href' => '#'));
        $output .= html_writer::tag('div', $filter_modulos . $modulos_all . ' / ' . $modulos_none, array('class' => 'multiple_list'));

        if (has_capability('report/unasus:view_all', $this->context)) {
            // Filtro de Polo
            $filter_polos = html_writer::label('Filtrar Polos:', 'multiple_polo');
            $filter_polos .= html_writer::select(get_polos($this->curso_ufsc), 'multiple_polo', '', false, array('multiple' => 'multiple', 'id' => 'multiple_polo'));
            $polos_all = html_writer::tag('a', 'Selecionar Todos', array('id'=>'select_all_polo','href'=>'#'));
            $polos_none = html_writer::tag('a', 'Limpar Seleção', array('id'=>'select_none_polo','href'=>'#'));
            $output .= html_writer::tag('div', $filter_polos.$polos_all.' / '.$polos_none, array('class' => 'multiple_list'));

            // Filtro de Tutores
            $filter_tutores = html_writer::label('Filtrar Tutores:', 'multiple_tutor');
            $filter_tutores .= html_writer::select(get_tutores_menu($this->curso_ufsc), 'tutores[]', '', false, array('multiple' => 'multiple', 'id' => 'multiple_tutor'));
            $tutores_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_tutor', 'href' => '#'));
            $tutores_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_tutor', 'href' => '#'));
            $output .= html_writer::tag('div', $filter_tutores . $tutores_all . ' / ' . $tutores_none, array('class' => 'multiple_list'));
        }

        $output .= html_writer::end_tag('div');

        // Radio para selecao do modo de busca, tabela e/ou gráficos
        $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'tabela', 'id' => 'radio_tabela', 'checked' => true));
        $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/table.png\">Tabela de Dados", 'radio_tabela', true, array('class' => 'radio'));

        if ($grafico) {
            $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_valores', 'id' => 'radio_valores'));
            $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/chart.png\">Gráfico de Valores", 'radio_valores', true, array('class' => 'radio'));
            $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_porcentagens', 'id' => 'radio_porcentagem'));
            $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/pct.png\">Gráfico de Porcentagem", 'radio_porcentagem', true, array('class' => 'radio'));
        }

        if ($dot_chart) {
            $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_pontos', 'id' => 'radio_dot'));
            $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/dot.png\">Gráfico de Horas", 'radio_dot', true, array('class' => 'radio'));
        }

        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Gerar relatório'));

        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('fieldset');
        $output .= html_writer::end_tag('form');
        return $output;
    }

    /**
     * @return string barra lateral de navegação e footer
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
    public function default_table($dadostabela, $header, $tipo_cabecalho = 'Estudante') {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report generaltable";
        $table->tablealign = 'center';


        // varre o header em busca da ultima atividade de cada módulo
        // utilizada na iteraçao das ativides para aplicar classe CSS que desenha a borda em torno dos módulos
        $ultima_atividade_modulo = array();
        $ultimo_alvo = 0;
        $ultima_atividade_modulo[] = $ultimo_alvo;
        foreach ($header as $activities) {
            $ultimo_alvo += count($activities);
            $ultima_atividade_modulo[] = $ultimo_alvo;
        }

        // Descobre se o cabeçalho é de 2 ou 1 linha, se for de 2 cria o header de duas linhas
        // que não existe no moodle API
        $header_keys = array_keys($header);
        if (is_array($header[$header_keys[0]])) { // Double Header
            $table->build_double_header($header, $tipo_cabecalho);
            $table->attributes['class'] .= " divisao-por-modulos";
        } else {
            $table->build_single_header($header);
        }

        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            $cel_tutor = new html_table_cell($tutor);
            $cel_tutor->attributes = array('class' => 'tutor');
            $cel_tutor->colspan = $ultimo_alvo + 1; // expande a célula com nome dos tutores

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
                            // Aplica a classe CSS para criar o contorno dos modulos na tabela
                            $cell->attributes = array(
                                'class' => $valor->get_css_class() . " ultima_atividade");
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
    public function table_todo_list($dadostabela, $header_size) {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report generaltable";
        $table->tablealign = 'center';


        $table_title = get_string($this->report . "_table_header", 'report_unasus');
        $table->headspan = array(1, $header_size);
        $table->head = array('Estudante', $table_title);


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
        global $USER;
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter(true, false);

        $modulos_raw = optional_param_array('modulos', null, PARAM_INT);
        $tutores_raw = optional_param_array('tutores', null, PARAM_INT);

        $modulos = get_atividades_cursos(get_modulos_validos($modulos_raw));

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $this->context) && !has_capability('report/unasus:view_all', $this->context)) {
            $tutores_raw = array($USER->id);
        }

        $dados_method = "get_dados_{$this->report}";
        $header_method = "get_table_header_{$this->report}";

        $table = $this->table_tutores($dados_method($this->curso_ufsc, $this->curso_ativo, $modulos, $tutores_raw), $header_method($modulos_raw));
        $output .= html_writer::tag('div', html_writer::table($table), array('class' => 'relatorio-wrapper'));

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de Estudantes sem Atividades Postadas (fora do prazo)
     * @return String
     */
    public function page_todo_list() {
        global $USER;
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter(false, false);

        $modulos_raw = optional_param_array('modulos', null, PARAM_INT);
        $tutores_raw = optional_param_array('tutores', null, PARAM_INT);

        $modulos = get_atividades_cursos(get_modulos_validos($modulos_raw));

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $this->context) && !has_capability('report/unasus:view_all', $this->context)) {
            $tutores_raw = array($USER->id);
        }

        $dados_method = "get_dados_{$this->report}";
        $dados_atividades = $dados_method($this->curso_ufsc, $this->curso_ativo, $modulos, $tutores_raw);



        // Varre os dados em busca do estudante com maior numero de atividades não feitas
        // Isso é utilizado para definir o tamanho do cabeçalho e da divisao por tutor.
        $max_size = 0;
        foreach ($dados_atividades as $tutor) {
            foreach ($tutor as $atividades) {
                if ($max_size < count($atividades))
                    $max_size = count($atividades);
            }
        }

        $table = $this->table_todo_list($dados_atividades, $max_size);
        $output .= html_writer::tag('div', html_writer::table($table), array('class' => 'relatorio-wrapper'));

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria o gráfico de stacked bars. Se porcentagem for true o gráfico é setado para o
     * modo porcentagem onde todos os valores sao mostrados em termos de porcentagens,
     * barras de 100%.
     *
     * @global type $PAGE
     * @param boolean $porcentagem
     * @return String
     */
    public function build_graph($porcentagem = false) {
        global $PAGE, $USER;
        raise_memory_limit(MEMORY_EXTRA);

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

        $modulos_raw = optional_param_array('modulos', null, PARAM_INT);
        $tutores_raw = optional_param_array('tutores', null, PARAM_INT);

        $modulos = get_atividades_cursos(get_modulos_validos($modulos_raw));

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $this->context) && !has_capability('report/unasus:view_all', $this->context)) {
            $tutores_raw = array($USER->id);
        }

        $PAGE->requires->js_init_call('M.report_unasus.init_graph', array(
            $dados_method($this->curso_ufsc, $modulos, $tutores_raw),
            array_values($legend),
            get_string($this->report, 'report_unasus'), $porcentagem));

        $output .= '<div id="container" class="container relatorio-wrapper"></div>';
        $output .= $this->default_footer();

        return $output;
    }

    /**
     * Cria o gráfico de pontos para o relatório de acesso do tutor(horas)
     *
     * @global type $PAGE
     * @return String
     */
    public function build_dot_graph() {
        global $PAGE;

        $output = $this->default_header();

        $PAGE->requires->js(new moodle_url("/report/unasus/graph/raphael-min.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/g.raphael-min.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/g.dotufsc.js"));

        $output .= $this->build_filter(true, false, true);

        $dados_method = "get_dados_grafico_{$this->report}";

        $modulos = optional_param_array('modulos', null, PARAM_INT);
        $tutores = optional_param_array('tutores', null, PARAM_INT);

        $PAGE->requires->js_init_call('M.report_unasus.init_dot_graph', array($dados_method($modulos, $tutores, $this->curso_ufsc)));

        $output .= '<div id="container" class="container relatorio-wrapper"></div>';
        $output .= $this->default_footer();

        return $output;
    }

}