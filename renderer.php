<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/form/dateselector.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Class report_unasus_renderer
 *
 * Essa classe tem como objetivo renderizar as telas dos relatórios de acordo com o que foi
 * selecionado no arquivo index.php. Quando necessário renderizar uma tabela ou gráfico o
 * relatório é encaminhado para o arquivo /relatorios/relatorios.php
 *
 * build_page() -> tela inicial do relatorio
 * build_legend -> cria a legenda das tabelas
 * default_header -> cabeçalho da pagina, com ou sem o botao de ajuda
 * build_filter -> constroi a barra de filtragem
 * default_footer -> rodapé do moodle
 * default_table -> tabela para os relatorios
 * table_tutores -> tabela de sintese dos tutores
 * table_todo_list -> tabela dos relatorios de tarefas em atraso
 * page_avaliacoes_em_atraso -> renderizacao para os relatorio de Atividades Postadas e não Avaliadas
 * page_todo_list -> renderizacao para os relatorios de tarefas em atraso
 * build_report -> renderizacao padrão, utilizada na maioria dos relatorios
 * build_graph -> renderizacao dos gráficos de barra
 * build_dot_graph -> renderizacao dos gráficos de pontos (uso sistema do tutor)
 * build_warning -> barra de aviso caso alguma filtragem seja inválida
 *
 */
class report_unasus_renderer extends plugin_renderer_base {

    private $report_name;

    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        /** @var $factory report_unasus_factory */
        $factory = report_unasus_factory::singleton();

        // Carrega tipo de renderização (relatório ou gráfico)
        $relatorio = $factory->get_relatorio();
        if (!empty($relatorio)) {
            $this->report_name = $relatorio;
        } else {
            $this->report_name = $factory->get_modo_exibicao();
        }
    }

    /**
     * Cria a página sem os gráficos, tela inicial, para que o usuário possa filtrar sua busca antes de
     * gerar a tabela
     *
     * @return String
     */
    public function build_page() {
        /** @var $report report_unasus_factory */
        $report = report_unasus_factory::singleton();

        $output = $this->default_header();
        $output .= $this->build_filter();

        if ($report->mostrar_aviso_intervalo_tempo) {
            $output .= $this->build_warning('Intervalo de Tempo incorreto ou Formato de data inválido ');
        }
        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a barra de legenda para os relatórios
     *
     * @param array $legend itens da legenda, é do tipo ["classe_css"]=>["Descricao da legenda"]
     * @return String
     */
    public function build_legend($legend) {
        if ($legend === false) {
            return null;
        }
        $output = html_writer::start_tag('fieldset', array('class' => "generalbox fieldset relatorio-unasus {$this->report_name}"));
        $output .= html_writer::tag('legend', 'Legenda', array('class' => 'relatorio-unasus legend'));
        $output .= html_writer::start_tag('dl');

        foreach ($legend as $class => $description) {
            //$class é a mesma classe definida no styles.css
            $output .= html_writer::tag('dt', '', array('class' => "relatorio-unasus {$class}"));
            $output .= html_writer::tag('dd', "{$description}");
        }
        $output .= html_writer::end_tag('dl');
        $output .= html_writer::end_tag('fieldset');
        return $output;
    }

    /**
     * Cria o cabeçalho padrão para os relatórios
     *
     * @return String cabeçalho, título da página e barra de filtragem
     */
    public function default_header() {
        $output = $this->header();

        $title = get_string($this->report_name, 'report_unasus');

        if ($title != "[[$this->report_name]]") {
            $output .= $this->heading_with_help($title, $this->report_name, 'report_unasus');
        } else {
            $output .= $this->heading($title);
        }

        return $output;
    }

    /**
     * Cria a barra de Filtros
     *
     * @return string $output
     */
    public function build_filter() {
        global $CFG, $_POST;

        /** @var $report report_unasus_factory */
        $report = report_unasus_factory::singleton();

        // Inicio do Form
        $url_filtro = new moodle_url('/report/unasus/index.php', $report->get_page_params());
        $output = html_writer::start_tag('form', array('action' => $url_filtro,
                    'method' => 'post', 'accept-charset' => 'utf-8', 'id' => 'filter_form'));

        // Fieldset
        $output .= html_writer::start_tag('fieldset', array('class' => 'relatorio-unasus fieldset'));
        $output .= html_writer::nonempty_tag('legend', get_string('filter_header', 'report_unasus'));

        // Botao de ocultar/mostrar filtros, só aparece com javascript carregado
        $css_class = ($report->mostrar_barra_filtragem == true) ? 'hidden' : 'visible';
        $output .= html_writer::nonempty_tag('button', 'Mostrar Filtros', array('id' => 'button-mostrar-filtro', 'type' => 'button', 'class' => "relatorio-unasus botao-ocultar {$css_class}"));

        // Filtros
        $output .= html_writer::start_tag('div', array('class' => "relatorio-unasus conteudo-filtro", 'id' => 'div_filtro'));

        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'report_hidden', 'value' => $report->get_relatorio()));

//        Comentado, pois não está sendo usado! Deixar estas linhas para, se necessário no futuro, repor a
//          funcionalidade
//
//        if ($report->mostrar_filtro_polos) {
//            // Dropdown list
//            $output .= html_writer::label('Agrupar relatório por: ', 'select_estado');
//
//            if($report->mostrar_filtro_tutores){
//                $selecao_agrupar_post = array_key_exists('agrupar_tutor_polo_select', $_POST) ? $_POST['agrupar_tutor_polo_select'] : '';
//                $output .= html_writer::select(array('Tutores', 'Polos', 'Cohorts'), 'agrupar_tutor_polo_select', $selecao_agrupar_post, false, array('id' => 'select_estado'));
//            }
//
//            if($report->mostrar_filtro_orientadores){
//                $selecao_agrupar_post = array_key_exists('agrupar_tutor_polo_select', $_POST) ? $_POST['agrupar_tutor_polo_select'] : '';
//                $output .= html_writer::select(array('Orientadores', 'Polos', 'Cohorts'), 'agrupar_tutor_polo_select', $selecao_agrupar_post, false, array('id' => 'select_estado'));
//            }
//        }

        // Div para os 3 filtros
        $output .= html_writer::start_tag('div', array('id' => 'div-multiple'));

        // Filtro de modulo
        if ($report->mostrar_filtro_modulos) {
            $selecao_modulos_post = array_key_exists('modulos', $_POST) ? $_POST['modulos'] : '';
            $nome_modulos = report_unasus_get_nome_modulos($report->get_categoria_turma_ufsc());
            $filter_modulos = html_writer::label('Filtrar Modulos:', 'multiple_modulo');
            $filter_modulos .= html_writer::select($nome_modulos, 'modulos[]', $selecao_modulos_post, '', array('multiple' => 'multiple', 'id' => 'multiple_modulo'));
            $modulos_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_modulo', 'href' => '#'));
            $modulos_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_modulo', 'href' => '#'));
            $output .= html_writer::tag('div', $filter_modulos . $modulos_all . ' / ' . $modulos_none, array('class' => 'relatorio-unasus multiple_list'));
        }

        if (has_capability('report/unasus:view_all', $report->get_context())) {

            if ($report->mostrar_filtro_cohorts) {
                // Filtro de Cohorts
                $selecao_cohorts_post = array_key_exists('cohorts', $_POST) ? $_POST['cohorts'] : '';
                $filter_cohorts = html_writer::label('Filtrar Cohorts:', 'multiple_cohort');
                $filter_cohorts .= html_writer::select(report_unasus_get_nomes_cohorts($report->get_categoria_curso_ufsc()), 'cohorts[]', $selecao_cohorts_post, false, array('multiple' => 'multiple', 'id' => 'multiple_cohort'));
                $cohorts_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_cohort', 'href' => '#'));
                $cohorts_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_cohort', 'href' => '#'));
                $output .= html_writer::tag('div', $filter_cohorts . $cohorts_all . ' / ' . $cohorts_none, array('class' => 'relatorio-unasus multiple_list'));
            }

            $output .= html_writer::tag('div', '', array('class' => 'relatorio-unasus clear'));

            if ($report->mostrar_filtro_grupo_tutoria) {
                // Filtro de Tutores
                $selecao_tutores_post = array_key_exists('tutores', $_POST) ? $_POST['tutores'] : '';
                $filter_tutores = html_writer::label('Filtrar Grupos de Tutoria:', 'multiple_tutor');
                $filter_tutores .= html_writer::select(local_tutores_grupos_tutoria::get_grupos_tutoria_menu($report->get_categoria_turma_ufsc()), 'tutores[]', $selecao_tutores_post, false, array('multiple' => 'multiple', 'id' => 'multiple_tutor'));
                $tutores_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_tutor', 'href' => '#'));
                $tutores_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_tutor', 'href' => '#'));
                $output .= html_writer::tag('div', $filter_tutores . $tutores_all . ' / ' . $tutores_none, array('class' => 'relatorio-unasus multiple_list'));
            } elseif ($report->mostrar_filtro_tutores) {
                // Filtro de Tutores
                $selecao_tutores_post = array_key_exists('tutores', $_POST) ? $_POST['tutores'] : '';
                $filter_tutores = html_writer::label('Filtrar Tutores:', 'multiple_tutor');
                $filter_tutores .= html_writer::select(local_tutores_grupos_tutoria::get_tutores($report->get_categoria_turma_ufsc()), 'tutores[]', $selecao_tutores_post, false, array('multiple' => 'multiple', 'id' => 'multiple_tutor'));
                $tutores_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_tutor', 'href' => '#'));
                $tutores_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_tutor', 'href' => '#'));
                $output .= html_writer::tag('div', $filter_tutores . $tutores_all . ' / ' . $tutores_none, array('class' => 'relatorio-unasus multiple_list'));
            }

            if ($report->mostrar_filtro_grupos_orientacao) {
                // Filtro de Grupos de Orientação
                $selecao_orientadores_post = array_key_exists('orientadores', $_POST) ? $_POST['orientadores'] : '';
                $filter_orientadores = html_writer::label('Filtrar Grupos de Orientação:', 'multiple_tutor');
                $filter_orientadores .= html_writer::select(local_tutores_grupo_orientacao::get_orientadores_grupos($report->get_categoria_turma_ufsc()), 'orientadores[]', $selecao_orientadores_post, false, array('multiple' => 'multiple', 'id' => 'multiple_tutor'));
                $orientadores_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_tutor', 'href' => '#'));
                $orientadores_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_tutor', 'href' => '#'));
                $output .= html_writer::tag('div', $filter_orientadores . $orientadores_all . ' / ' . $orientadores_none, array('class' => 'relatorio-unasus multiple_list'));

            } elseif ($report->mostrar_filtro_orientadores) {
                // Filtro de Orientadores
                $selecao_orientadores_post = array_key_exists('orientadores', $_POST) ? $_POST['orientadores'] : '';
                $filter_orientadores = html_writer::label('Filtrar Orientadores:', 'multiple_tutor');
                $filter_orientadores .= html_writer::select(local_tutores_grupo_orientacao::get_orientadores($report->get_categoria_turma_ufsc()), 'orientadores[]', $selecao_orientadores_post, false, array('multiple' => 'multiple', 'id' => 'multiple_tutor'));
                $orientadores_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_tutor', 'href' => '#'));
                $orientadores_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_tutor', 'href' => '#'));
                $output .= html_writer::tag('div', $filter_orientadores . $orientadores_all . ' / ' . $orientadores_none, array('class' => 'relatorio-unasus multiple_list'));
            }

            if ($report->mostrar_filtro_polos) {
                // Filtro de Polo
                $selecao_polos_post = array_key_exists('polos', $_POST) ? $_POST['polos'] : '';
                $db_polos = report_unasus_get_polos($report->get_categoria_turma_ufsc());
                if (!empty($db_polos)) {
                    $filter_polos = html_writer::label('Filtrar Polos:', 'multiple_polo');
                    $filter_polos .= html_writer::select($db_polos, 'polos[]', $selecao_polos_post, false, array('multiple' => 'multiple', 'id' => 'multiple_polo'));
                    $polos_all = html_writer::tag('a', 'Selecionar Todos', array('id' => 'select_all_polo', 'href' => '#'));
                    $polos_none = html_writer::tag('a', 'Limpar Seleção', array('id' => 'select_none_polo', 'href' => '#'));
                    $output .= html_writer::tag('div', $filter_polos . $polos_all . ' / ' . $polos_none, array('class' => 'relatorio-unasus multiple_list'));
                }
            }

        }

        if ($report->mostrar_filtro_intervalo_tempo) {

            $data_fim = date('d/m/Y');
            $data_inicio = date('d/m/Y', strtotime('-1 months'));

            $data_inicio_param = $report->data_inicio;
            $data_fim_param = $report->data_fim;

            if (!is_null($data_inicio_param))
                $data_inicio = $data_inicio_param;

            if (!is_null($data_fim_param))
                $data_fim = $data_fim_param;

            $output .= html_writer::start_tag('div', array('class' => 'relatorio-unasus time_filter'));
            $output .= html_writer::tag('h3', 'Data Inicio:');
            $output .= html_writer::tag('input', null, array('type' => 'text', 'name' => 'data_inicio', 'value' => $data_inicio));
            $output .= html_writer::tag('h3', 'Data Fim:');
            $output .= html_writer::tag('input', null, array('type' => 'text', 'name' => 'data_fim', 'value' => $data_fim));
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::end_tag('div');

        // Radio para selecao do modo de busca, tabela e/ou gráficos
        $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'tabela', 'id' => 'radio_tabela', 'checked' => true));
        $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/table.png\">Tabela de Dados", 'radio_tabela', true, array('class' => 'relatorio-unasus radio'));

        if ($report->mostrar_botoes_grafico) {
            $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_valores', 'id' => 'radio_valores'));
            $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/chart.png\">Gráfico de Valores", 'radio_valores', true, array('class' => 'relatorio-unasus radio'));
            $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_porcentagens', 'id' => 'radio_porcentagem'));
            $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/pct.png\">Gráfico de Porcentagem", 'radio_porcentagem', true, array('class' => 'relatorio-unasus radio'));
        }

        if ($report->mostrar_botoes_dot_chart) {
            $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'grafico_pontos', 'id' => 'radio_dot'));
            $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/dot.png\">Gráfico de Horas", 'radio_dot', true, array('class' => 'relatorio-unasus radio'));
        }

        if($report->mostrar_botao_exportar_csv){
            // Exportar para CSV
            $output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => 'export_csv', 'id' => 'radio_csv'));
            $output .= html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/csv_icon.png\">Exportar para CSV", 'radio_csv', true, array('class' => 'relatorio-unasus radio'));
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
     *
     * @TODO construir uma simple table que não necessita ter divisões de tutor/polo barra azul
     * @param Array $dadostabela dados para alimentar a tabela
     * @param Array $header header para a tabela, pode ser um
     *              array('value1','value2','value3') ou um array de chaves valor
     *              array('modulo'=> array('value1','value2'))
     * @param string $tipo_cabecalho
     * @return html_table
     */
    public function default_table($dadostabela, $header, $table, $tipo_cabecalho = 'Estudante') {

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
        if (isset($header_keys[0]) && is_array($header[$header_keys[0]])) { // Double Header
        } else {
            $table->build_single_header($header);
        }

        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            $cel_tutor = new html_table_cell($tutor);
            $cel_tutor->attributes = array('class' => 'relatorio-unasus tutor');
            $cel_tutor->colspan = $ultimo_alvo + 1; // expande a célula com nome dos tutores

            $row_tutor = new html_table_row();
            $row_tutor->attributes = array('class' => 'relatorio-unasus r1');
            $row_tutor->cells[] = $cel_tutor;
            $table->data[] = $row_tutor;

            //atividades de cada aluno daquele dado tutor
            $count = 0;
            foreach ($alunos as $aluno) {
                $row = new html_table_row();
                $row_tutor->attributes = array('class' => 'relatorio-unasus r0');
                foreach ($aluno as $valor) {
                    if (is_a($valor, 'report_unasus_data_render')) {
                        $cell = new html_table_cell($valor);
                        if (in_array($count, $ultima_atividade_modulo)) {
                            // Aplica a classe CSS para criar o contorno dos modulos na tabela
                            $cell->attributes = array('class' => $valor->get_css_class() . " ultima_atividade relatorio-unasus c_body");
                        } else {
                            $cell->attributes = array('class' => $valor->get_css_class() . "relatorio-unasus c_body");
                        }
                    } else { // Aluno
                        $cell = new html_table_cell($valor);
                        $cell->header = true;
                        // $cell->attributes = array('class' => 'relatorio-unasus estudante ultima_atividade c_body');
                        $cell->attributes = array('class' => 'relatorio-unasus estudante');
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
     * @param Array $dadostabela
     * @param Array $header
     * @param string $relatorio
     * @return report_unasus_table
     */
    public function table_tutores($dadostabela, $header, $relatorio = '') {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report_name generaltable_without_stripes";
        $table->tablealign = 'center';

        $header_keys = array_keys($header);
        if (isset($header_keys[0]) && is_array($header[$header_keys[0]])) { // Double Header
            if($relatorio == 'report_tcc_consolidado'){
                $table->build_double_header($header, 'Orientadores');
            }  else{
                $table->build_double_header($header, 'Tutores');
            }
        } else {
            $table->build_single_header($header);
        }

        //atividades de cada aluno daquele dado tutor
        foreach ($dadostabela as $aluno) {
            $row = new html_table_row();
            foreach ($aluno as $valor) {
                $cell = new html_table_cell($valor);
                if (is_a($valor, 'report_unasus_data_render')) {
                    $cell->attributes = array(
                        'class' => "relatorio-unasus " . $valor->get_css_class());
                } else { // Aluno
                    $cell->header = true;
                    $cell->attributes = array('class' => 'relatorio-unasus estudante ');
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
     *
     * @TODO construir uma simple table que não necessita ter divisões de tutor/polo barra azul
     * @param Array $dadostabela dados para alimentar a tabela
     * @param Array $header_size header para a tabela, pode ser um
     *              array('value1','value2','value3') ou um array de chaves valor
     *              array('modulo'=> array('value1','value2'))
     * @return html_table
     */
    public function table_todo_list($dadostabela, $header_size) {
        //criacao da tabela
        $table = new report_unasus_table();
        $table->attributes['class'] = "relatorio-unasus $this->report_name generaltable_without_stripes";
        $table->tablealign = 'center';

        $table_title = get_string($this->report_name . "_table_header", 'report_unasus');
        $table->headspan = array(1, $header_size);

        $student = new html_table_cell('Estudantes');
        $student->header = true;
        $student->attributes = array('class' => 'relatorio-unasus title estudante');

        $heading1 = array();
        $heading1[] = $student;
        $heading1[] = $table_title;

        $table->head = $heading1;
       // passa por todos os tutores
        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            $cel_tutor = new html_table_cell($tutor);
            $cel_tutor->attributes = array('class' => 'relatorio-unasus tutor');
            $cel_tutor->colspan = $header_size + 1; // expande a célula com nome dos tutores
            $row_tutor = new html_table_row();
            $row_tutor->cells[] = $cel_tutor;
            $table->data[] = $row_tutor;
            //atividades de cada aluno daquele dado tutor
            foreach ($alunos as $aluno) {
                $row = new html_table_row();
                foreach ($aluno as $valor) {
                    if (is_a($valor, 'report_unasus_data_render')) {
                        $cell = new html_table_cell($valor);
                        $cell->attributes = array(
                            'class' => "relatorio-unasus " . $valor->get_css_class());

                    } else { // Aluno
                        // Se for um estudante
                        $cell = new html_table_cell($valor);
                        $cell->header = true;
                        $cell->attributes = array('class' => 'relatorio-unasus estudante');
                    }

                    if (!empty($cell)) {
                        $row->cells[] = $cell;
                    };
                }
                $table->data[] = $row;
            }
        }

        return $table;
    }

    /**
     * Cria a página referente ao relatorio de Atividades Postadas e não Avaliadas
     *
     * @TODO esse metodo não necessita de uma legenda e usa uma tabela diferente
     * @param $report
     * @throws Exception
     * @throws coding_exception
     * @return String
     */
    public function page_avaliacoes_em_atraso($report) {
        global $USER;
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter();

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->tutores_selecionados = self::get_grupos_tutoria_byuser_id($report, $USER->id);
        }

        // Se o usuário conectado tiver a permissão de visualizar como orientador apenas,
        // alteramos o que vai ser enviado para o filtro de orientador.
        if (has_capability('report/unasus:view_orientacao', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->orientadores_selecionados = self::get_grupos_orientacao_byuser_id($report, $USER->id);
        }

        $dados_method = $report->get_dados();
        $header_method = $report->get_table_header();

        $table = $this->table_tutores($dados_method, $header_method, get_class($report));
        $output .= html_writer::tag('div', html_writer::table($table), array('class' => 'relatorio-unasus relatorio-wrapper'));

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria a página referente ao Relatório de Estudantes sem Atividades Postadas (fora do prazo)
     * e Estudantes sem Atividades Avaliada
     *
     * @param $report
     * @throws Exception
     * @throws coding_exception
     * @return String
     */
    public function page_todo_list($report) {
        global $USER;
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter();

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->tutores_selecionados = self::get_grupos_tutoria_byuser_id($report, $USER->id);
        }

        // Se o usuário conectado tiver a permissão de visualizar como orientador apenas,
        // alteramos o que vai ser enviado para o filtro de orientador.
        if (has_capability('report/unasus:view_orientacao', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->orientadores_selecionados = self::get_grupos_orientacao_byuser_id($report, $USER->id);

        }

        $dados_method = $report->get_dados();
        $dados_atividades = $dados_method;

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
        $output .= html_writer::tag('div', html_writer::table($table), array('class' => 'relatorio-unasus relatorio-wrapper'));

        $output .= $this->default_footer();
        return $output;
    }

    private function get_grupos_tutoria_byuser_id($report, $userid) {
        $categoria_turma_ufsc = $report->get_categoria_turma_ufsc();
        $grupos_tutoria = local_tutores_grupos_tutoria::get_grupos_tutoria($categoria_turma_ufsc, $userid);
        $tutores_selecionados = array();
        foreach ($grupos_tutoria as $grupo_tutoria_id => $grupo_tutoria) {
            $tutores_selecionados[] = $grupo_tutoria_id;
        }
        return $tutores_selecionados;
    }

    private function get_grupos_orientacao_byuser_id($report, $userid) {
        $categoria_turma_ufsc = $report->get_categoria_turma_ufsc();
        $grupos_orientacao = local_tutores_grupo_orientacao::get_grupos_orientacao_new($categoria_turma_ufsc, $userid);
        $orientadores_selecionados = array();
        foreach ($grupos_orientacao as $grupo_orientacao_id => $grupo_orientacao) {
            $orientadores_selecionados[] = $grupo_orientacao_id;
        }
        return $orientadores_selecionados;
    }
    /**
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
     * @param $report
     * @throws coding_exception
     * @return String $output
     */
    public function build_report($report) {
        global $USER, $PAGE;
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter();

        //-----------------------------------------------------------------
        //ALTERAR esta 'estrutura_dados_relatorio' para o objeto relatório???

        $data_class = $report->get_estrutura_dados_relatorio();
        //-----------------------------------------------------------------

        $output .= html_writer::tag('div', $this->build_legend($data_class::get_legend()), array('class' => 'relatorio-unasus right_legend'));

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->tutores_selecionados = self::get_grupos_tutoria_byuser_id($report, $USER->id);
        }

        // Se o usuário conectado tiver a permissão de visualizar como orientador apenas,
        // alteramos o que vai ser enviado para o filtro de orientador.
        if (has_capability('report/unasus:view_orientacao', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->orientadores_selecionados = self::get_grupos_orientacao_byuser_id($report, $USER->id);
        }

        /* Ajustes para o cabeçalho duplo de alguns relatórios */

        $class = $report->get_relatorio() . ' generaltable_without_stripes divisao-por-modulos fixed';

        // Descobre se o cabeçalho é de 2 ou 1 linha, se for de 2 cria o header de duas linhas
        // que não existe no moodle API
        $header_keys = array_keys($report->get_table_header());

        $ultima_atividade_modulo = array();
        $ultimo_alvo = 0;
        $ultima_atividade_modulo[] = $ultimo_alvo;
        foreach ($report->get_table_header() as $module_name => $activities) {
            $ultimo_alvo += count($activities);
            $ultima_atividade_modulo[] = $ultimo_alvo;
        }
        if (isset($header_keys[0]) && is_array($report->get_table_header()[$header_keys[0]])) {

            /* Dados do cabeçalho */

            $output .= html_writer::start_tag('div', array('class' => 'relatorio-unasus relatorio-wrapper'));
            $output .= html_writer::start_tag('table', array('class' => "relatorio-unasus ".$class));
            $output .= html_writer::start_tag('thead');
            $output .= html_writer::start_tag('tr', array('class' => 'relatorio-unasus r0'));
            $output .= html_writer::tag('td', '', array('class' => 'relatorio-unasus blank'));

            foreach ($report->get_table_header() as $module_name => $activities) {
                $output .= html_writer::tag('th', $module_name, array('class' => 'relatorio-unasus modulo_header cell c1', 'colspan' => count($activities)));
            }

            $output .= html_writer::end_tag('tr');
            $output .= html_writer::start_tag('tr', array('class' => 'relatorio-unasus r1'));

            $output .= html_writer::tag('th', 'Estudante', array('class' => 'relatorio-unasus ultima_atividade title estudante_header'));

            foreach ($report->get_table_header() as $module_name => $activities) {
                $count_ = 1;
                foreach ($activities as $activity) {
                    if (! is_object($activity)){
                        $class = (is_numeric($activity[0]) AND !is_string($activity)) ? '' : 'relatorio-unasus rotate cell c_body';//' . $count_;
                    } else {
                        $class = 'relatorio-unasus rotate cell c_body';// . $count_;
                    }

                    $count_++;
                    $output .= html_writer::tag('th', $activity, array('class' => "relatorio-unasus ".$class));
                }
            }

            $output .= html_writer::end_tag('tr');

            $output .= html_writer::end_tag('thead');

            /* Dados da tabela */

            $output .= html_writer::start_tag('tbody', array('class' => "relatorio-unasus"));

            foreach ($report->get_dados() as $tutor => $alunos) {

                $output .= html_writer::start_tag('tr', array('class' => 'relatorio-unasus r0'));
                $output .= html_writer::tag('td', $tutor, array('class' => 'relatorio-unasus tutor', 'colspan' => $ultimo_alvo + 1));
                $output .= html_writer::end_tag('tr');

                $count = 0;
                $count_cell = 1;
                foreach ($alunos as $aluno) {
                    $output .= html_writer::start_tag('tr', array('class' => 'relatorio-unasus r1'));

                    foreach ($aluno as $valor) {
                        if (is_a($valor, 'report_unasus_data_render')) {
                            if (in_array($count, $ultima_atividade_modulo)) {
                                // Aplica a classe CSS para criar o contorno dos modulos na tabela
                                $output .= html_writer::tag('td', $valor, array('class' => "relatorio-unasus ".$valor->get_css_class() . " ultima_atividade cell c_body"));//" . $count_cell));
                            } else {
                                $output .= html_writer::tag('td', $valor, array('class' => "relatorio-unasus ".$valor->get_css_class() . ' cell c_body'));//' . $count_cell));
                            }
                        } else { // Aluno
                            $output .= html_writer::tag('th', $valor, array('class' => 'relatorio-unasus estudante position', 'scope' => 'row'));
                        }
                        $count++;
                        $count_cell++;
                    }
                    $output .= html_writer::end_tag('tr');
                    $count = 0;
                    $count_cell = 1;
                }
            }

            $output .= html_writer::end_tag('tbody');

            $output .= html_writer::end_tag('table');
            $output .= html_writer::end_tag('div');
        } else {
            $table = new report_unasus_table();
            $table->attributes['class'] = $class;

            $table = $this->default_table($report->get_dados(), $report->get_table_header(), $table, $class);
            $output .= html_writer::tag('div', html_writer::table($table), array('class' => 'relatorio-unasus relatorio-wrapper'));
        }

        $module = array(
            'name'      => 'gradereport_grader',
            'fullpath'  => '/grade/report/grader/module.js',
            'requires'  => array('base', 'dom', 'event', 'event-mouseenter', 'event-key', 'io-queue', 'json-parse', 'overlay')
        );

        $PAGE->requires->js_init_call('M.report_unasus.fixed_columns');

        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Cria o gráfico de stacked bars. Se porcentagem for true o gráfico é setado para o
     * modo porcentagem onde todos os valores sao mostrados em termos de porcentagens,
     * barras de 100%.
     *
     * @param $report report_unasus_factory
     * @param boolean $porcentagem
     * @throws Exception
     * @throws coding_exception
     * @global type $PAGE
     * @return String
     */
    public function build_graph($report, $porcentagem = false) {
        global $PAGE, $USER;
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();

        $PAGE->requires->js(new moodle_url("/report/unasus/graph/jquery.min.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/highcharts/js/highcharts.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/highcharts/js/modules/exporting.js"));

        $output .= $this->build_filter(true);

        // verifica se o gráfico foi implementado
        if (!$report->relatorio_possui_grafico($report)) {
            $output .= $this->box(get_string('unimplemented_graph_error', 'report_unasus'));
            $output .= $this->default_footer();
            return $output;
        }

        $dados_method = $report->get_dados_grafico();
        //-----------------------------------------------------------------
        //ALTERAR esta 'estrutura_dados_relatorio' para o objeto relatório???

        $dados_class = $report->get_estrutura_dados_relatorio();

        //-----------------------------------------------------------------

        $legend = call_user_func("$dados_class::get_legend");

        // Se o usuário conectado tiver a permissão de visualizar como tutor apenas,
        // alteramos o que vai ser enviado para o filtro de tutor.
        if (has_capability('report/unasus:view_tutoria', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->tutores_selecionados = self::get_grupos_tutoria_byuser_id($report, $USER->id);
        }

        // Se o usuário conectado tiver a permissão de visualizar como orientador apenas,
        // alteramos o que vai ser enviado para o filtro de orientador.
        if (has_capability('report/unasus:view_orientacao', $report->get_context()) && !has_capability('report/unasus:view_all', $report->get_context())) {
            $report->orientadores_selecionados = self::get_grupos_orientacao_byuser_id($report, $USER->id);

        }

        $PAGE->requires->js_init_call('M.report_unasus.init_graph', array(
            $dados_method,
            array_values($legend),
            get_string($this->report_name, 'report_unasus'), $porcentagem));

        $output .= '<div id="container" class="relatorio-unasus container relatorio-wrapper"></div>';
        $output .= $this->default_footer();

        return $output;
    }

    /**
     * Cria o gráfico de pontos para o relatório de acesso do tutor(horas)
     *
     * @param $report
     * @throws Exception
     * @throws coding_exception
     * @global type $PAGE
     * @return String
     */
    public function build_dot_graph($report) {
        global $PAGE;

        $output = $this->default_header();

        $PAGE->requires->js(new moodle_url("/report/unasus/graph/raphael-min.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/g.raphael-min.js"));
        $PAGE->requires->js(new moodle_url("/report/unasus/graph/g.dotufsc.js"));

        $output .= $this->build_filter();

        // verifica se o gráfico foi implementado
        if (!$report->relatorio_possui_grafico($report)) {
            $output .= $this->box(get_string('unimplemented_graph_error', 'report_unasus'));
            $output .= $this->default_footer();
            return $output;
        }

        $dados_method = $report->get_dados_grafico();

        // Se algum tutor logou ele gera o gráfico
        if (report_unasus_dot_chart_com_tutores_com_acesso($dados_method)) {
            $PAGE->requires->js_init_call('M.report_unasus.init_dot_graph', array($dados_method));
            $output .= '<div id="container" class="relatorio-unasus container relatorio-wrapper"></div>';
        } else {
            // Se nenhum tutor logou ele informa um erro em vez de gerar um gráfico vazio
            $output .= $this->build_warning('Nenhum tutor logou no moodle no intervalo de tempo selecionado');
        }

        $output .= $this->default_footer();

        return $output;
    }

    /**
     * Constroi um fieldset de warning de erro nos filtros
     *
     * @param string $msg Texto de aviso
     * @return string
     */
    public function build_warning($msg) {
        $output = html_writer::start_tag('fieldset', array('class' => 'relatorio-unasus fieldset warning'));
        $output .= html_writer::tag('legend', 'Erro', array('class' => 'relatorio-unasus legend'));
        $output .= $msg;
        $output .= html_writer::end_tag('fieldset');
        return $output;
    }
}
