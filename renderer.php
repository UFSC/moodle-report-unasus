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

        $this->apply_role_scope($report);

        if ($report->mostrar_aviso_intervalo_tempo) {
            $output .= $this->build_warning('Intervalo de Tempo incorreto ou Formato de data inválido ');
        }
        $output .= $this->default_footer();
        return $output;
    }

    /**
     * Legenda em faixa horizontal, para ficar na mesma linha do botao de filtros.
     *
     * ⚠️ A legenda NAO e' colapsavel, e isso e' deliberado: nas tabelas a cor e' o UNICO
     * canal -- as celulas sao blocos coloridos sem texto (divida de WCAG 1.4.1 registrada
     * no CLAUDE.md). Enquanto isso for verdade, a legenda e' o unico decodificador da
     * tela, e esconde-la quebra a leitura para quem e' daltonico. Compacta, sim;
     * escondida, nao.
     *
     * A versao em bloco (build_legend) consumia 108px de altura numa tela onde o
     * cabecalho ja' comia 482px antes do primeiro dado. Esta ocupa uma linha.
     *
     * @param array $legend itens da legenda, do tipo ["classe_css"] => ["Descricao"]
     * @return String
     */
    public function build_legend_compacta($legend) {
        if ($legend === false || empty($legend)) {
            return null;
        }

        $itens = '';
        foreach ($legend as $class => $description) {
            // A amostra de cor leva `aria-hidden`: quem usa leitor de tela ja' recebe o
            // texto ao lado, e um quadrado vazio anunciado no meio atrapalharia.
            $amostra = html_writer::tag('span', '', array(
                'class' => "relatorio-unasus legenda-amostra {$class}",
                'aria-hidden' => 'true'));
            $itens .= html_writer::tag('span', $amostra . s($description),
                array('class' => 'relatorio-unasus legenda-item'));
        }

        // ⚠️ O nome do relatorio TEM de estar no container. As cores dos estados sao
        // escopadas por ele -- `.relatorio-unasus.boletim .na_media`, e assim por diante
        // -- entao uma amostra fora desse escopo sai branca. A versao em bloco punha o
        // nome no fieldset; sem ele aqui, a legenda vira uma fileira de quadrados vazios.
        return html_writer::tag('div',
            html_writer::tag('span', 'Legenda:', array('class' => 'relatorio-unasus legenda-rotulo')) . $itens,
            array('class' => "relatorio-unasus legenda-compacta {$this->report_name}"));
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
     * Monta um bloco de filtro de selecao multipla, com recolhimento opcional.
     *
     * ⚠️ Existe para os quatro filtros nao serem quatro copias do mesmo codigo. Eram seis
     * blocos praticamente identicos (rotulo, select, "Selecionar Todos / Limpar Selecao"),
     * e acrescentar recolhimento em cada copia multiplicaria a divergencia.
     *
     * @param string $rotulo texto do rotulo, ex.: 'Filtrar Polos'
     * @param string $id id do <select>, ex.: 'multiple_polo'
     * @param string $sufixo sufixo dos links de selecao, ex.: 'polo'
     * @param array $opcoes menu id => nome
     * @param string $campo nome do campo no POST, ex.: 'polos'
     * @param array|string $selecionados selecao vinda do formulario
     * @param bool $colapsavel se ganha a seta de recolher
     * @param array $ativos rotulos do que esta' selecionado de fato (para o resumo)
     * @return string
     */
    private function build_filtro_lista($rotulo, $id, $sufixo, $opcoes, $campo,
                                        $selecionados, $colapsavel = false, $ativos = array()) {

        $select = html_writer::select($opcoes, $campo . '[]', $selecionados, false,
            array('multiple' => 'multiple', 'id' => $id));

        // ⚠️ Os links dizem no `data-alvo` qual select operam, e o JS os pega por CLASSE.
        //
        // Antes cada par de links tinha id proprio e o module.js listava um tratador por
        // id -- quatro pares no codigo, e nenhum para orientacao, que "funcionava" apenas
        // porque reusava os ids de tutoria. Filtro novo, ou id renomeado, e os links
        // param de funcionar em silencio: foi o que aconteceu ao separar os dois blocos.
        //
        // Os ids continuam sendo emitidos: ha' teste de Behat que clica por id.
        $links = html_writer::tag('a', 'Selecionar Todos', array(
                'id' => 'select_all_' . $sufixo,
                'href' => '#',
                'class' => 'relatorio-unasus link-selecionar',
                'data-alvo' => $id,
                'data-marcar' => '1'))
            . ' / '
            . html_writer::tag('a', 'Limpar Seleção', array(
                'id' => 'select_none_' . $sufixo,
                'href' => '#',
                'class' => 'relatorio-unasus link-selecionar',
                'data-alvo' => $id,
                'data-marcar' => '0'));

        $corpo_id = 'corpo_' . $id;
        $corpo = html_writer::tag('div', $select . $links,
            array('id' => $corpo_id, 'class' => 'relatorio-unasus corpo-filtro'));

        if (!$colapsavel) {
            return html_writer::tag('div',
                html_writer::label($rotulo . ':', $id) . $corpo,
                array('class' => 'relatorio-unasus multiple_list'));
        }

        // Recolhivel: o rotulo vira o controle, com a mesma seta e o mesmo `aria-expanded`
        // do "Filtrar Resultados" -- um idioma so' em dois niveis.
        $seta = $this->pix_icon('t/collapsed', '', 'moodle', array('class' => 'icon seta-filtro seta-fechada'))
              . $this->pix_icon('t/expanded', '', 'moodle', array('class' => 'icon seta-filtro seta-aberta'));

        $texto = $rotulo;
        if (!empty($ativos)) {
            $texto .= ' (' . count($ativos) . ')';
        }

        // O detalhe da selecao vai para a DICA, nao para a tela: o rotulo mostra so' a
        // contagem, que e' o sinal de que ha' recorte, e o "quais" aparece no hover ou no
        // foco. Mesmo arranjo do "Filtrar Resultados", um nivel abaixo.
        $dica_id = 'dica_' . $id;
        $dica = '';
        if (!empty($ativos)) {
            $itens = '';
            foreach ($ativos as $rotulo_ativo) {
                $itens .= html_writer::tag('div', s($rotulo_ativo), array('class' => 'relatorio-unasus dica-linha'));
            }
            $dica = html_writer::tag('span', $itens,
                array('id' => $dica_id, 'role' => 'tooltip', 'class' => 'relatorio-unasus dica-filtro'));
        }

        $botao = html_writer::tag('button', $seta . html_writer::tag('span', s($texto)), array(
            'type' => 'button',
            'class' => 'relatorio-unasus botao-recolher-filtro',
            'aria-expanded' => 'false',
            'aria-controls' => $corpo_id,
            // Sem hover nao ha' dica; o `aria-describedby` entrega o mesmo texto a quem usa
            // leitor de tela, que e' onde o hover puro deixaria gente de fora.
            'aria-describedby' => empty($dica) ? null : $dica_id));

        // ⚠️ Botao, dica e corpo sao IRMAOS, e nao aninhados: o CSS esconde o corpo e
        // mostra a dica lendo o `aria-expanded` do botao pelo seletor de irmao. Envolver o
        // botao num span quebraria essa relacao e exigiria um segundo lugar guardando o
        // estado.
        return html_writer::tag('div', $botao . $dica . $corpo,
            array('class' => 'relatorio-unasus multiple_list filtro-colapsavel'));
    }

    /**
     * Lista, em texto, os filtros que estao valendo na tela.
     *
     * ⚠️ Existe porque o filtro ATIVO era invisivel. As selecoes moram dentro do painel
     * colapsado; com ele fechado -- que e' o estado padrao -- a tela dizia apenas
     * "Filtrar Resultados / Mostrar Filtros", identica a' de um relatorio sem filtro
     * nenhum. Quem abrisse um link compartilhado, ou voltasse a uma aba antiga, lia um
     * boletim de 3 grupos como se fosse o da turma inteira. Nao e' questao de estetica:
     * e' leitura errada de dado de gestao.
     *
     * @return array lista de rotulos, ja' prontos para exibicao
     */
    private function get_resumo_filtros($report) {
        $linhas = array();

        // "Todos" e' informacao, e nao ausencia dela: quem le' precisa saber que a
        // dimensao existe e nao esta' estreitando, em vez de ficar na duvida se ela foi
        // esquecida. Por isso cada dimensao que o relatorio oferece entra no resumo,
        // estreitada ou nao.
        $descreve = function ($rotulo, $selecionados, $menu) use (&$linhas) {
            $selecionados = (array)$selecionados;

            // ⚠️ Dimensao SEM OPCAO nenhuma nao entra: o filtro e' inoperante, e anuncia-la
            // como "Todos" e' pior que omitir -- diz que existe um recorte possivel onde
            // nao existe. Cai aqui, por exemplo, o filtro de cohorts numa turma cujos
            // cohorts vivem em outra categoria: o select sai vazio.
            //
            // Sem esta guarda a conta `0 >= 0` mandava a dimensao vazia direto para o
            // ramo do "Todos".
            if (empty($menu)) {
                return;
            }

            if (empty($selecionados) || count($selecionados) >= count($menu)) {
                $linhas[] = array('rotulo' => $rotulo, 'valor' => 'Todos', 'estreita' => 0);
                return;
            }

            $nomes = array();
            foreach ($selecionados as $id) {
                if (isset($menu[$id])) {
                    $nomes[] = $menu[$id];
                }
            }

            $linhas[] = array(
                'rotulo' => $rotulo,
                'valor' => implode(', ', $nomes),
                'estreita' => count($nomes));
        };

        if ($report->mostrar_filtro_modulos) {
            $descreve('Módulos', $report->modulos_selecionados,
                report_unasus_get_nome_modulos($report->get_categoria_turma_ufsc()));
        }

        // As dimensoes abaixo so' existem para quem pode ver tudo -- o mesmo teste que
        // decide se elas aparecem no painel. Sem espelhar isso, o resumo anunciaria
        // filtros que o usuario nem pode operar.
        if (has_capability('report/unasus:view_all', $report->get_context())) {

            if ($report->mostrar_filtro_cohorts) {
                $descreve('Cohorts', $report->cohorts_selecionados,
                    report_unasus_get_nomes_cohorts($report->get_categoria_curso_ufsc()));
            }

            if ($report->mostrar_filtro_grupo_tutoria) {
                $descreve('Grupos de Tutoria', $report->tutores_selecionados,
                    local_tutores_grupos_tutoria::get_grupos_tutoria_menu($report->get_categoria_turma_ufsc()));
            } elseif ($report->mostrar_filtro_tutores) {
                $descreve('Tutores', $report->tutores_selecionados,
                    local_tutores_grupos_tutoria::get_tutores($report->get_categoria_turma_ufsc()));
            }

            if ($report->mostrar_filtro_grupos_orientacao) {
                $descreve('Grupos de Orientação', $report->orientadores_selecionados,
                    local_tutores_grupo_orientacao::get_orientadores_grupos($report->get_categoria_turma_ufsc()));
            } elseif ($report->mostrar_filtro_orientadores) {
                $descreve('Orientadores', $report->orientadores_selecionados,
                    local_tutores_grupo_orientacao::get_orientadores($report->get_categoria_turma_ufsc()));
            }

            if ($report->mostrar_filtro_polos) {
                $polos = report_unasus_get_polos($report->get_categoria_turma_ufsc());
                if (!empty($polos)) {
                    $descreve('Polos', $report->polos_selecionados, $polos);
                }
            }
        }

        if ($report->mostrar_filtro_intervalo_tempo) {
            $periodo = trim($report->data_inicio . ' a ' . $report->data_fim);
            $linhas[] = array(
                'rotulo' => 'Período',
                'valor' => ($periodo === 'a') ? 'Todos' : $periodo,
                'estreita' => ($periodo === 'a') ? 0 : 1);
        }

        return $linhas;
    }

    private function get_filtros_ativos($report) {
        $ativos = array();

        // Cada bloco resolve o id para o nome usando o MESMO menu que alimenta o select
        // correspondente, para o chip e a lista nunca divergirem. So' consulta quando ha'
        // selecao, entao numa tela sem filtro nao ha custo de banco nenhum.
        //
        // ⚠️ Selecao que cobre o conjunto INTEIRO nao e' filtro, e nao entra na conta. A
        // factory preenche `modulos_selecionados` com todos os modulos quando nada foi
        // escolhido; contando isso, um boletim filtrado por 3 grupos anunciava "15
        // filtros" -- 12 deles sendo "o curso inteiro". Inflar a contagem destroi
        // justamente a informacao que os chips existem para dar.
        $nomes = function ($selecionados, $menu) {
            $selecionados = (array)$selecionados;
            if (count($selecionados) >= count($menu)) {
                return array();
            }

            $rotulos = array();
            foreach ($selecionados as $id) {
                if (isset($menu[$id])) {
                    $rotulos[] = $menu[$id];
                }
            }
            return $rotulos;
        };

        if (!empty($report->modulos_selecionados)) {
            $ativos = array_merge($ativos, $nomes($report->modulos_selecionados,
                report_unasus_get_nome_modulos($report->get_categoria_turma_ufsc())));
        }

        if (!empty($report->cohorts_selecionados)) {
            $ativos = array_merge($ativos, $nomes($report->cohorts_selecionados,
                report_unasus_get_nomes_cohorts($report->get_categoria_curso_ufsc())));
        }

        if (!empty($report->tutores_selecionados)) {
            $menu = $report->mostrar_filtro_grupo_tutoria
                ? local_tutores_grupos_tutoria::get_grupos_tutoria_menu($report->get_categoria_turma_ufsc())
                : local_tutores_grupos_tutoria::get_tutores($report->get_categoria_turma_ufsc());
            $ativos = array_merge($ativos, $nomes($report->tutores_selecionados, $menu));
        }

        if (!empty($report->orientadores_selecionados)) {
            $menu = $report->mostrar_filtro_grupos_orientacao
                ? local_tutores_grupo_orientacao::get_orientadores_grupos($report->get_categoria_turma_ufsc())
                : local_tutores_grupo_orientacao::get_orientadores($report->get_categoria_turma_ufsc());
            $ativos = array_merge($ativos, $nomes($report->orientadores_selecionados, $menu));
        }

        if (!empty($report->polos_selecionados)) {
            $ativos = array_merge($ativos, $nomes($report->polos_selecionados,
                report_unasus_get_polos($report->get_categoria_turma_ufsc())));
        }

        // O intervalo de tempo conta como filtro ativo: nos relatorios de tutor ele muda
        // o resultado tanto quanto a selecao de grupos.
        if ($report->mostrar_filtro_intervalo_tempo &&
            (!is_null($report->data_inicio) || !is_null($report->data_fim))) {
            $ativos[] = trim($report->data_inicio . ' a ' . $report->data_fim);
        }

        return $ativos;
    }

    /**
     * Cria a barra de Filtros
     *
     * @return string $output
     */
    public function build_filter($legenda_compacta = null) {
        global $CFG, $_POST;

        /** @var $report report_unasus_factory */
        $report = report_unasus_factory::singleton();

        // Inicio do Form
        $url_filtro = new moodle_url('/report/unasus/index.php', $report->get_page_params());
        $output = html_writer::start_tag('form', array('action' => $url_filtro,
                    'method' => 'post', 'accept-charset' => 'utf-8', 'id' => 'filter_form'));

        // Fieldset
        $output .= html_writer::start_tag('fieldset', array('class' => 'relatorio-unasus fieldset'));

        // Botao de ocultar/mostrar filtros, só aparece com javascript carregado
        $css_class = ($report->mostrar_barra_filtragem == true) ? 'hidden' : 'visible';

        $filtros_ativos = $this->get_filtros_ativos($report);

        // O controle e' o PROPRIO TITULO da secao com a seta ao lado, como na arvore de
        // navegacao do Moodle -- e nao um botao escrito "Mostrar/Ocultar Filtros". Com o
        // titulo virando controle, o rotulo de acao fica redundante e sai.
        //
        // As duas setas sao emitidas juntas e o CSS mostra uma so', conforme o
        // `aria-expanded` do botao: assim o JS troca UM atributo, que e' o mesmo que o
        // leitor de tela anuncia, em vez de trocar classe de icone e estado em dois
        // lugares que podem divergir.
        $icone_fechado = $this->pix_icon('t/collapsed', '', 'moodle',
            array('class' => 'icon seta-filtro seta-fechada'));
        $icone_aberto = $this->pix_icon('t/expanded', '', 'moodle',
            array('class' => 'icon seta-filtro seta-aberta'));

        $rotulo_botao = $icone_fechado . $icone_aberto .
            html_writer::tag('span', get_string('filter_header', 'report_unasus'),
                array('class' => 'relatorio-unasus rotulo-filtro'));

        if (!empty($filtros_ativos)) {
            $rotulo_botao .= html_writer::tag('span', '(' . count($filtros_ativos) . ')',
                array('class' => 'relatorio-unasus contagem-filtro'));
        }

        // Barra de ferramentas: botao, chips do filtro ativo e legenda na MESMA faixa.
        // Antes eram tres blocos empilhados -- fieldset do filtro, e a legenda num bloco
        // proprio de 108px -- somando 482px de cabecalho antes do primeiro dado, metade
        // da janela numa tela de 958px.
        $output .= html_writer::start_tag('div', array('class' => 'relatorio-unasus barra-ferramentas'));

        // Continua sendo um <button>: e' o que da' foco por teclado, acionamento por
        // Enter/Espaco e o papel anunciado ao leitor de tela. So' a aparencia de botao
        // sai, no CSS. `aria-expanded` diz o estado; `aria-controls` diz o que ele abre.
        // O resumo por dimensao vira DICA do controle, e nao uma faixa fixa de chips: com
        // muitos itens escolhidos a faixa crescia sem limite e devolvia o espaco que a
        // barra tinha acabado de recuperar. A contagem no titulo continua visivel sem
        // interacao -- e' ela o alarme de "isto esta' filtrado"; a dica traz o detalhe.
        $resumo = $this->get_resumo_filtros($report);
        $dica = '';
        foreach ($resumo as $linha) {
            $dica .= html_writer::tag('div',
                html_writer::tag('span', s($linha['rotulo']) . ':', array('class' => 'relatorio-unasus dica-rotulo')) .
                ' ' . s($linha['valor']),
                array('class' => 'relatorio-unasus dica-linha'));
        }

        // O invólucro posiciona a dica; a dica vem DEPOIS do botao no DOM para o CSS
        // alcanca-la pelo seletor de irmao adjacente.
        $output .= html_writer::start_tag('span', array('class' => 'relatorio-unasus controle-filtro'));

        $output .= html_writer::tag('button', $rotulo_botao, array(
            'id' => 'button-mostrar-filtro',
            'type' => 'button',
            'class' => "relatorio-unasus botao-ocultar {$css_class}",
            'aria-expanded' => ($report->mostrar_barra_filtragem == true) ? 'true' : 'false',
            'aria-controls' => 'div_filtro',
            'aria-describedby' => empty($dica) ? null : 'dica-filtro'));

        if (!empty($dica)) {
            // `role=tooltip` + `aria-describedby` no botao: assim o leitor de tela recebe
            // o resumo junto com o nome do controle, sem depender de hover -- que nao
            // existe para teclado nem para toque.
            $output .= html_writer::tag('span', $dica,
                array('id' => 'dica-filtro', 'role' => 'tooltip', 'class' => 'relatorio-unasus dica-filtro'));
        }

        $output .= html_writer::end_tag('span');

        if (!is_null($legenda_compacta)) {
            $output .= $legenda_compacta;
        }

        $output .= html_writer::end_tag('div');

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

        // ⚠️ ORDEM POR FREQUENCIA DE USO: Modulos e Grupos sao os filtros do dia a dia;
        // Polos e Cohorts sao ocasionais, vem depois e ja' nascem recolhidos. Antes a
        // ordem era Modulos, Cohorts, Grupos, Polos -- o mais raro no meio dos mais usados.
        //
        // Modulos e Grupos NAO recolhem: recolher o que se usa toda vez so' acrescenta um
        // clique a cada consulta.

        // ⚠️ A selecao vem da FACTORY, e nao do $_POST.
        //
        // O painel lia so' o $_POST, entao um filtro aplicado por URL (link compartilhado,
        // aba antiga, favorito) chegava aos dados mas nao aparecia marcado na lista: a tela
        // mostrava um relatorio recortado com os selects em branco.
        //
        // Selecao que cobre o conjunto INTEIRO nao e' marcada: a factory preenche
        // `modulos_selecionados` com todos os modulos quando nada foi escolhido, e marcar
        // os doze anunciaria um recorte que nao existe. Vazio le'-se "Todos", que e' o que
        // de fato esta' valendo.
        $selecao_efetiva = function ($selecionados, $menu) {
            $selecionados = (array)$selecionados;
            if (empty($menu) || count($selecionados) >= count($menu)) {
                return array();
            }
            return $selecionados;
        };

        // Rotulos do que esta' de fato estreitando, para a dica do bloco recolhido.
        // Mesma regra: selecao que cobre o conjunto inteiro nao e' filtro.
        $rotulos_ativos = function ($selecionados, $menu) {
            $selecionados = (array)$selecionados;
            if (empty($menu) || empty($selecionados) || count($selecionados) >= count($menu)) {
                return array();
            }
            $nomes = array();
            foreach ($selecionados as $id) {
                if (isset($menu[$id])) {
                    $nomes[] = $menu[$id];
                }
            }
            return $nomes;
        };

        // 1 - Modulos
        if ($report->mostrar_filtro_modulos) {
            $menu = report_unasus_get_nome_modulos($report->get_categoria_turma_ufsc());
            $selecao = $selecao_efetiva($report->modulos_selecionados, $menu);
            $output .= $this->build_filtro_lista('Filtrar Modulos', 'multiple_modulo', 'modulo',
                $menu, 'modulos', $selecao);
        }

        if (has_capability('report/unasus:view_all', $report->get_context())) {

            // 2 - Grupos, de tutoria ou de orientacao conforme o relatorio
            if ($report->mostrar_filtro_grupo_tutoria) {
                $menu = local_tutores_grupos_tutoria::get_grupos_tutoria_menu($report->get_categoria_turma_ufsc());
                $selecao = $selecao_efetiva($report->tutores_selecionados, $menu);
                $output .= $this->build_filtro_lista('Filtrar Grupos de Tutoria', 'multiple_tutor', 'tutor',
                    $menu, 'tutores', $selecao);
            } elseif ($report->mostrar_filtro_tutores) {
                $menu = local_tutores_grupos_tutoria::get_tutores($report->get_categoria_turma_ufsc());
                $selecao = $selecao_efetiva($report->tutores_selecionados, $menu);
                $output .= $this->build_filtro_lista('Filtrar Tutores', 'multiple_tutor', 'tutor',
                    $menu, 'tutores', $selecao);
            }

            if ($report->mostrar_filtro_grupos_orientacao) {
                $menu = local_tutores_grupo_orientacao::get_orientadores_grupos($report->get_categoria_turma_ufsc());
                $selecao = $selecao_efetiva($report->orientadores_selecionados, $menu);
                $output .= $this->build_filtro_lista('Filtrar Grupos de Orientação', 'multiple_orientador', 'orientador',
                    $menu, 'orientadores', $selecao);
            } elseif ($report->mostrar_filtro_orientadores) {
                $menu = local_tutores_grupo_orientacao::get_orientadores($report->get_categoria_turma_ufsc());
                $selecao = $selecao_efetiva($report->orientadores_selecionados, $menu);
                $output .= $this->build_filtro_lista('Filtrar Orientadores', 'multiple_orientador', 'orientador',
                    $menu, 'orientadores', $selecao);
            }

            // 3 - Polos, recolhido
            if ($report->mostrar_filtro_polos) {
                $menu = report_unasus_get_polos($report->get_categoria_turma_ufsc());
                if (!empty($menu)) {
                    $selecao = $selecao_efetiva($report->polos_selecionados, $menu);
                    $output .= $this->build_filtro_lista('Filtrar Polos', 'multiple_polo', 'polo',
                        $menu, 'polos', $selecao, true,
                        $rotulos_ativos($report->polos_selecionados, $menu));
                }
            }

            // 4 - Cohorts, recolhido
            //
            // ⚠️ So' sai quando ha' cohort para escolher. Numa turma cujos cohorts vivem em
            // outra categoria a consulta devolve nada, e o que ia para a tela era uma caixa
            // de selecao vazia: ocupa espaco, sugere um recorte possivel e nao faz nada.
            if ($report->mostrar_filtro_cohorts) {
                $menu = report_unasus_get_nomes_cohorts($report->get_categoria_curso_ufsc());
                if (!empty($menu)) {
                    $selecao = $selecao_efetiva($report->cohorts_selecionados, $menu);
                    $output .= $this->build_filtro_lista('Filtrar Cohorts', 'multiple_cohort', 'cohort',
                        $menu, 'cohorts', $selecao, true,
                        $rotulos_ativos($report->cohorts_selecionados, $menu));
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

        $output .= html_writer::end_tag('div'); // #div-multiple

        // ⚠️ As acoes ficam no RODAPE do painel, sempre -- e nao como mais uma coluna na
        // fila dos filtros. Chegaram a ficar na fila, para preencher a faixa morta a'
        // direita em janela larga, mas o lugar de "Gerar relatorio" e' no fim do
        // formulario: e' a acao que conclui o preenchimento, e ela muda de posicao a cada
        // relatorio se acompanhar a quantidade de filtros.
        $output .= html_writer::start_tag('div', array('class' => 'relatorio-unasus acoes-relatorio'));

        // Radio para selecao do modo de busca, tabela e/ou gráficos.
        // Cada par input+label vai dentro de um span: sem ele a linha pode quebrar entre o
        // botao e o seu rotulo, deixando um circulo solto no fim da linha.
        $opcao = function ($value, $id, $icone, $rotulo, $checked = false) use ($CFG) {
            $attrs = array('type' => 'radio', 'name' => 'modo_exibicao', 'value' => $value, 'id' => $id);
            if ($checked) {
                $attrs['checked'] = true;
            }
            return html_writer::tag('span',
                html_writer::empty_tag('input', $attrs) .
                html_writer::label("<img src=\"{$CFG->wwwroot}/report/unasus/img/{$icone}\">{$rotulo}",
                    $id, true, array('class' => 'relatorio-unasus radio')),
                array('class' => 'relatorio-unasus opcao-exibicao'));
        };

        // ⚠️ Os radios sao um GRUPO ROTULADO, e nao opcoes soltas.
        //
        // Eles nao sao acoes ao lado do botao: dizem o que "Gerar relatorio" vai FAZER --
        // com Tabela ele desenha a tela, com CSV ele baixa um arquivo. Como o botao vem
        // ANTES deles na linha, essa relacao precisa estar escrita: sem rotulo, tres
        // controles em fila leem-se como tres acoes.
        //
        // `role=radiogroup` + `aria-labelledby` em vez de <fieldset><legend>: o legend
        // dentro de um container flex tem historico de comportamento irregular entre
        // navegadores, e aqui a faixa inteira e' flex. O anuncio ao leitor de tela e' o
        // mesmo -- antes deste rotulo o grupo era anunciado como radios avulsos, sem
        // dizer de que sao opcoes.
        $output .= html_writer::start_tag('span', array(
            'class' => 'relatorio-unasus grupo-exibicao',
            'role' => 'radiogroup',
            'aria-labelledby' => 'rotulo-exibicao'));

        $output .= html_writer::tag('span', 'Exibir como:',
            array('id' => 'rotulo-exibicao', 'class' => 'relatorio-unasus rotulo-exibicao'));

        $output .= $opcao('tabela', 'radio_tabela', 'table.png', 'Tabela de Dados', true);

        if ($report->mostrar_botoes_grafico) {
            $output .= $opcao('grafico_valores', 'radio_valores', 'chart.png', 'Gráfico de Valores');
            $output .= $opcao('grafico_porcentagens', 'radio_porcentagem', 'pct.png', 'Gráfico de Porcentagem');
            // Barras lado a lado: as empilhadas mostram a composição de cada grupo, estas
            // permitem comparar o mesmo estado entre grupos.
            $output .= $opcao('grafico_agrupado', 'radio_agrupado', 'chart_pp.png', 'Gráfico Comparativo');
        }

        if ($report->mostrar_botoes_dot_chart) {
            $output .= $opcao('grafico_pontos', 'radio_dot', 'dot.png', 'Gráfico de Horas');
        }

        if ($report->mostrar_botao_exportar_csv) {
            $output .= $opcao('export_csv', 'radio_csv', 'csv_icon.png', 'Exportar para CSV');
        }

        $output .= html_writer::end_tag('span'); // .grupo-exibicao

        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Gerar relatório'));

        $output .= html_writer::end_tag('div'); // .acoes-relatorio
        $output .= html_writer::end_tag('div'); // #div_filtro

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
            // Mesmo caso do build_report(): ha' relatorio cujo cabecalho e' string,
            // e count() sobre string e' TypeError no PHP 8.
            $ultimo_alvo += is_countable($activities) ? count($activities) : 1;
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
            // Mesmo motivo do ramo acima: quem fica preso na lateral e' o span interno.
            $cel_tutor = new html_table_cell(html_writer::span($tutor, 'tutor-rotulo'));
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
                            $cell->attributes = array('class' => $valor->get_css_class() . " relatorio-unasus c_body");
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
            $coluna = 0;
            foreach ($aluno as $valor) {
                $cell = new html_table_cell($valor);
                if (is_a($valor, 'report_unasus_data_render')) {
                    $cell->attributes = array(
                        'class' => "relatorio-unasus " . $valor->get_css_class());
                } else { // Aluno
                    $cell->header = true;
                    $cell->attributes = array('class' => 'relatorio-unasus estudante ');
                }

                // ⚠️ A trava lateral nao pode depender do TIPO do conteudo.
                //
                // A primeira coluna e' a coluna congelada, sempre -- mas ate' aqui quem
                // ganhava a marcacao era o ramo do "Aluno", isto e', so' quando o rotulo
                // vinha como string. No tcc_consolidado o rotulo da linha de total e' um
                // report_unasus_dado_texto_render, entao caia no ramo de dados, saia como
                // `td.total` sem trava, e era a unica celula da coluna que escorregava ao
                // rolar para o lado -- as outras ficavam paradas, e a linha do total
                // aparecia desencontrada das demais.
                if ($coluna === 0) {
                    $cell->attributes['class'] .= ' primeira_coluna';
                }
                $coluna++;

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
        // `estudante_header` alem de `estudante`: e' por ela que a trava lateral do
        // cabecalho pega (styles.css, "Trava da coluna do estudante"). Sem a classe,
        // a coluna do estudante congelava no corpo e o titulo dela nao acompanhava.
        $student->attributes = array('class' => 'relatorio-unasus title estudante estudante_header');

        $heading1 = array();
        $heading1[] = $student;
        $heading1[] = $table_title;

        $table->head = $heading1;

        // ⚠️ Esta tabela tem LINHAS IRREGULARES: cada estudante tem tantas celulas
        // quantas forem as pendencias dele. Sob "cada celula desenha so' a direita e a
        // de baixo", o topo de uma celula e' a borda de baixo da celula ACIMA -- e a
        // celula que sobra numa linha mais longa que a anterior nao tem ninguem acima,
        // entao fica sem linha superior. Aparecia como um pedaco de linha faltando a
        // partir da coluna onde a linha de cima terminou.
        //
        // A correcao e' ESTRUTURAL, e nao de estilo: a linha de cima recebe celulas
        // vazias ate' alcancar o comprimento da de baixo, e a divisa volta a ser o
        // border-bottom de uma celula de verdade. Ver a nota do `celula_vazia` no
        // styles.css para as duas tentativas de resolver pelo CSS que sairam
        // desalinhadas por um pixel, cada uma para um lado.
        //
        // O cabecalho e as faixas de tutor cobrem a largura inteira (headspan e
        // colspan), entao a linha logo abaixo de uma delas ja' vem fechada.

       // passa por todos os tutores
        foreach ($dadostabela as $tutor => $alunos) {

            //celula com o nome do tutor, a cada iteração um tutor e seus respectivos
            //alunos vao sendo populado na tabela
            // Mesmo motivo do ramo acima: quem fica preso na lateral e' o span interno.
            $cel_tutor = new html_table_cell(html_writer::span($tutor, 'tutor-rotulo'));
            $cel_tutor->attributes = array('class' => 'relatorio-unasus tutor');
            $cel_tutor->colspan = $header_size + 1; // expande a célula com nome dos tutores
            $row_tutor = new html_table_row();
            $row_tutor->cells[] = $cel_tutor;
            $table->data[] = $row_tutor;

            // As linhas do grupo sao montadas antes de entrar na tabela: para saber se
            // uma precisa de celulas vazias e' preciso conhecer o comprimento da
            // SEGUINTE.
            $linhas_grupo = array();

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
                $linhas_grupo[] = $row;
            }

            // Fecha o topo das celulas que sobram na linha de baixo, alongando a de
            // cima com celulas vazias. A ultima linha do grupo nao precisa: quem vem
            // depois dela e' a faixa do proximo tutor, que cobre a tabela inteira.
            //
            // ⚠️ A conta usa o numero de celulas de DADO da linha de baixo, medido antes
            // de qualquer preenchimento. A divisa entre duas linhas vai ate' o maior dos
            // dois vizinhos, e nem um pixel alem.
            //
            // Propagar o comprimento ja' preenchido (percorrendo de baixo para cima, por
            // exemplo) faz o tamanho subir em cascata pelo grupo inteiro: uma linha longa
            // la' embaixo estica todas as de cima, e sobra linha muito depois do fim dos
            // dados. Foi o que aconteceu entre Gabriela Lima e Thiago Cavalcanti.
            $dados_por_linha = array();
            foreach ($linhas_grupo as $linha) {
                $dados_por_linha[] = count($linha->cells);
            }

            foreach ($linhas_grupo as $i => $linha) {
                if (!isset($dados_por_linha[$i + 1])) {
                    continue; // ultima do grupo: embaixo dela vem a faixa do proximo tutor
                }
                $faltam = $dados_por_linha[$i + 1] - $dados_por_linha[$i];
                for ($n = 0; $n < $faltam; $n++) {
                    $vazia = new html_table_cell('');
                    $vazia->attributes = array('class' => 'relatorio-unasus celula_vazia');
                    $linha->cells[] = $vazia;
                }
            }

            foreach ($linhas_grupo as $linha) {
                $table->data[] = $linha;
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
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter();

        $this->apply_role_scope($report);

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
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
        $output .= $this->build_filter();

        $this->apply_role_scope($report);

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

    /**
     * Restringe os filtros de tutor e orientador ao escopo do usuário logado.
     *
     * Aplica em qualquer página que exiba dados do relatório: usuários sem
     * `view_all` veem apenas os grupos em que atuam como tutor e/ou orientador.
     *
     * @param report_unasus_factory $report
     * @return void
     */
    private function apply_role_scope($report) {
        global $USER;

        $context = $report->get_context();
        if (has_capability('report/unasus:view_all', $context)) {
            return;
        }

        if (has_capability('report/unasus:view_tutoria', $context)) {
            $report->tutores_selecionados = $this->get_grupos_tutoria_byuser_id($report, $USER->id);
        }

        if (has_capability('report/unasus:view_orientacao', $context)) {
            $report->orientadores_selecionados = $this->get_grupos_orientacao_byuser_id($report, $USER->id);
        }
    }

    private function get_grupos_tutoria_byuser_id($report, $userid) {
        $categoria_turma_ufsc = $report->get_categoria_turma_ufsc();
        $grupos_tutoria = local_tutores_grupos_tutoria::get_grupos_tutoria_by_userid($categoria_turma_ufsc, $userid);
        $tutores_selecionados = array();
        foreach ($grupos_tutoria as $grupo_tutoria_id => $grupo_tutoria) {
            $tutores_selecionados[] = $grupo_tutoria_id;
        }
        return $tutores_selecionados;
    }

    private function get_grupos_orientacao_byuser_id($report, $userid) {
        $categoria_turma_ufsc = $report->get_categoria_turma_ufsc();
        $grupos_orientacao = local_tutores_grupo_orientacao::get_grupos_orientacao_by_userid($categoria_turma_ufsc, $userid);
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

        //-----------------------------------------------------------------
        //ALTERAR esta 'estrutura_dados_relatorio' para o objeto relatório???

        $data_class = $report->get_estrutura_dados_relatorio();
        //-----------------------------------------------------------------

        // A legenda entra DENTRO da barra de filtros, e nao mais num bloco proprio abaixo
        // dela: sao os mesmos itens ocupando uma linha em vez de 108px.
        $output .= $this->build_filter($this->build_legend_compacta($data_class::get_legend()));

        $this->apply_role_scope($report);

        /* Ajustes para o cabeçalho duplo de alguns relatórios */

        // ⚠️ `relatorio-unasus` vai na propria TABELA, e nao so' no wrapper em volta:
        // `table.relatorio-unasus { border-collapse: separate }` e' seletor de tabela, nao
        // descendente. Sem a classe aqui, o ramo de cabecalho simples (default_table)
        // ficava em `collapse` -- com duas bordas por divisa, que e' o que faz a linha
        // sumir quando uma celula presa se desloca. So' o ramo de cabecalho duplo
        // acrescentava a classe, na hora de emitir a tag.
        //
        // ⚠️ E `divisao-por-modulos` NAO vai junto: ela e' do ramo de cabecalho duplo, o
        // unico que emite o <colgroup>. A regra
        // `table.relatorio-unasus.divisao-por-modulos { table-layout: fixed }` tira as
        // larguras do conteudo e as le' do colgroup -- numa tabela sem colgroup as
        // colunas colapsam para zero e o nome do estudante quebra letra a letra. A classe
        // sempre esteve nas duas, e so' nao fazia estrago porque sem `relatorio-unasus` a
        // regra nao alcancava a tabela.
        $classe_base = 'relatorio-unasus ' . $report->get_relatorio() .
                       ' generaltable_without_stripes';
        $class = $classe_base . ' divisao-por-modulos fixed';

        // Descobre se o cabeçalho é de 2 ou 1 linha, se for de 2 cria o header de duas linhas
        // que não existe no moodle API
        $header_method = $report->get_table_header();
        $header_keys = array_keys($header_method);
        $dados_method = $report->get_dados();

        $ultima_atividade_modulo = array();
        $ultimo_alvo = 0;
        $ultima_atividade_modulo[] = $ultimo_alvo;
        foreach ($header_method as $module_name => $activities) {
            // Nem todo relatorio devolve o cabecalho como array de atividades: o
            // modulos_concluidos devolve strings, e count() sobre string e' TypeError
            // no PHP 8. O proprio codigo abaixo ja' trata as duas formas com is_array().
            $ultimo_alvo += is_countable($activities) ? count($activities) : 1;
            $ultima_atividade_modulo[] = $ultimo_alvo;
        }
        if (isset($header_keys[0]) && is_array($header_method[$header_keys[0]])) {

            /* Dados do cabeçalho */

            $output .= html_writer::start_tag('div', array('class' => 'relatorio-unasus relatorio-wrapper'));
            $output .= html_writer::start_tag('table', array('class' => $class));

            // A tabela usa table-layout:fixed, entao as larguras vem do colgroup e nao do
            // conteudo. Sem isso a linha do grupo/tutor (uma unica celula com colspan e um
            // texto longo) estica as colunas de atividade, que deveriam ser estreitas.
            // As larguras em si ficam no styles.css, por classe.
            $output .= html_writer::start_tag('colgroup');
            $output .= html_writer::empty_tag('col', array('class' => 'col-estudante'));
            foreach ($header_method as $module_name => $activities) {
                foreach ($activities as $activity) {
                    $output .= html_writer::empty_tag('col', array('class' => 'col-atividade'));
                }
            }
            $output .= html_writer::end_tag('colgroup');

            $output .= html_writer::start_tag('thead');
            $output .= html_writer::start_tag('tr', array('class' => 'relatorio-unasus r0'));
            $output .= html_writer::tag('td', '', array('class' => 'relatorio-unasus blank'));

            foreach ($header_method as $module_name => $activities) {
                // O rotulo vai dentro de um span pelo mesmo motivo da faixa de grupo: a
                // celula cobre todas as atividades do modulo e ja' nasce mais larga que a
                // area visivel, entao sticky nela nao segura nada. Sem isto o titulo do
                // modulo sai de vista ao rolar e a faixa fica vazia -- com 108 colunas,
                // perde-se a referencia de qual modulo se esta' lendo.
                $output .= html_writer::tag('th', html_writer::span($module_name, 'modulo-rotulo'),
                    array('class' => 'relatorio-unasus modulo_header cell c1', 'colspan' => count($activities)));
            }

            $output .= html_writer::end_tag('tr');
            $output .= html_writer::start_tag('tr', array('class' => 'relatorio-unasus r1'));

            $output .= html_writer::tag('th', 'Estudante', array('class' => 'relatorio-unasus ultima_atividade title estudante_header'));

            foreach ($header_method as $module_name => $activities) {
                $count_ = 1;
                foreach ($activities as $activity) {
                    if (! is_object($activity)){
                        $class = (is_numeric($activity[0]) AND !is_string($activity)) ? '' : 'relatorio-unasus rotate cell c_body';//' . $count_;
                    } else {
                        $class = 'relatorio-unasus rotate cell c_body';// . $count_;
                    }

                    $count_++;

                    // Cabecalho de texto puro (as datas do uso_sistema_tutor, por exemplo)
                    // vai dentro de um span: o giro do rotulo e' aplicado a um elemento, e
                    // um no de texto solto ficaria na horizontal.
                    $conteudo = is_object($activity)
                        ? $activity
                        : html_writer::span($activity, 'rotate-rotulo');

                    $output .= html_writer::tag('th', $conteudo, array('class' => "relatorio-unasus ".$class));
                }
            }

            $output .= html_writer::end_tag('tr');

            $output .= html_writer::end_tag('thead');

            /* Dados da tabela */

            $output .= html_writer::start_tag('tbody', array('class' => "relatorio-unasus"));

            foreach ($dados_method as $tutor => $alunos) {

                $output .= html_writer::start_tag('tr', array('class' => 'relatorio-unasus r0'));
                // O rotulo vai dentro de um span porque a celula tem colspan e ja' nasce
                // tao larga quanto a area rolavel: sticky nela nao segura nada. Quem fica
                // preso na lateral e' o span, dentro dela.
                $output .= html_writer::tag('td', html_writer::span($tutor, 'tutor-rotulo'),
                    array('class' => 'relatorio-unasus tutor', 'colspan' => $ultimo_alvo + 1));
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
            // Sem `divisao-por-modulos`: aqui nao ha colgroup para o table-layout:fixed
            // ler, e as larguras tem de continuar vindo do conteudo.
            $table->attributes['class'] = $classe_base;

            $header_method = $report->get_table_header();
            $dados_method = $report->get_dados();
            $table = $this->default_table($dados_method, $header_method, $table, $classe_base);
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
    public function build_graph($report, $porcentagem = false, $empilhado = true) {
        raise_memory_limit(MEMORY_EXTRA);

        $output = $this->default_header();
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

        $this->apply_role_scope($report);

        $output .= $this->grafico_barras(
            $dados_method, $legend, get_string($this->report_name, 'report_unasus'),
            $porcentagem, $empilhado);
        $output .= $this->default_footer();

        return $output;
    }

    /**
     * Monta o gráfico de barras com a API de gráficos do core.
     *
     * Substituiu o Highcharts 2.2.5 (2012), que vinha empacotado junto de um jQuery 1.7.1. Aquele
     * jQuery se registrava como o módulo AMD `jquery` e deslocava o do core, quebrando o
     * JavaScript do Moodle na página inteira -- não só o gráfico.
     *
     * @param array $dados array de rótulo (grupo/tutor) => valores, na ordem da legenda
     * @param array $legend array de classe CSS => descrição, na mesma ordem dos valores
     * @param string $titulo título do gráfico
     * @param bool $porcentagem se true, normaliza cada barra para 100%
     * @param bool $empilhado empilhadas mostram a composição de cada grupo; lado a lado
     *        permitem comparar o mesmo estado entre grupos
     * @return string HTML do gráfico
     */
    protected function grafico_barras($dados, $legend, $titulo, $porcentagem = false, $empilhado = true) {
        $classes = array_keys($legend);
        $descricoes = array_values($legend);

        $rotulos = array();
        $series = array_fill(0, count($descricoes), array());

        foreach ($dados as $rotulo => $valores) {
            // O rótulo do grupo/tutor vem com marcação HTML, que num gráfico apareceria crua.
            $rotulos[] = trim(html_to_text($rotulo, 0, false));

            $total = array_sum($valores);
            foreach ($descricoes as $i => $descricao) {
                $valor = isset($valores[$i]) ? $valores[$i] : 0;
                if ($porcentagem) {
                    // Barra sem nenhum dado fica zerada em vez de dividir por zero.
                    $valor = $total > 0 ? round($valor * 100 / $total, 1) : 0;
                }
                $series[$i][] = $valor;
            }
        }

        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $chart->set_stacked($empilhado);
        $chart->set_title($titulo);
        $chart->set_labels($rotulos);

        foreach ($descricoes as $i => $descricao) {
            $serie = new \core\chart_series($descricao, $series[$i]);

            // Mantém o gráfico com as mesmas cores da legenda das tabelas.
            $cor = self::cor_da_legenda($classes[$i]);
            if ($cor !== null) {
                $serie->set_color($cor);
            }

            $chart->add_series($serie);
        }

        return $this->render($chart);
    }

    /**
     * Cor de uma classe da legenda, espelhando o styles.css.
     *
     * A paleta e' a Okabe-Ito, segura para deuteranopia, protanopia e tritanopia. A anterior
     * apoiava-se em pares vermelho x verde e laranja x amarelo, que colapsam para quem tem
     * deficiencia de visao de cores -- justamente nos estados que o usuario mais precisa
     * distinguir (entregue no prazo x entregue com atraso x nao entregue).
     *
     * Mexer aqui exige mexer no styles.css: legenda da tabela e serie do grafico precisam
     * dizer a mesma cor para o mesmo estado.
     *
     * @param string $classe classe CSS usada na legenda
     * @return string|null cor em hexadecimal, ou null se nao houver equivalente
     */
    protected static function cor_da_legenda($classe) {
        $cores = array(
            // Atribuicao de notas
            'nota_atribuida' => '#0072B2',              // azul       -- nota no prazo
            'nota_atribuida_atraso' => '#56B4E9',       // azul-ceu   -- nota fora do prazo
            'avaliado_sem_nota' => '#E69F00',           // laranja    -- entregue, sem nota
            'nao_entregue' => '#D55E00',                // vermelho   -- nao realizada

            // Entrega de atividades
            'no_prazo' => '#0072B2',                    // azul       -- entregue em dia
            'pouco_atraso' => '#009E73',                // verde-azul -- atraso pequeno
            'muito_atraso' => '#F0E442',                // amarelo    -- atraso grande
            'nao_entregue_mas_no_prazo' => '#E69F00',   // laranja    -- pendente, no prazo
            'nao_entregue_fora_do_prazo' => '#D55E00',  // vermelho   -- pendente, fora do prazo
            'sem_prazo' => '#000000',                   // preto      -- sem prazo definido
            'nao_aplicado' => '#999999',                // cinza      -- nao se aplica
            'nao_realizada' => '#CC79A7',               // rosa       -- nao realizada

            // Conclusao
            'concluido' => '#009E73',
            'nao_concluido' => '#D55E00',

            // TCC
            'rascunho' => '#E69F00',
            'revisao' => '#F0E442',
            'avaliado' => '#0072B2',
        );

        return isset($cores[$classe]) ? $cores[$classe] : null;
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
        $output = $this->default_header();
        $output .= $this->build_filter();

        // verifica se o gráfico foi implementado
        if (!$report->relatorio_possui_grafico($report)) {
            $output .= $this->box(get_string('unimplemented_graph_error', 'report_unasus'));
            $output .= $this->default_footer();
            return $output;
        }

        $dados_method = $report->get_dados_grafico();

        if (report_unasus_dot_chart_com_tutores_com_acesso($dados_method)) {
            $output .= $this->mapa_de_calor($dados_method);
        } else {
            // Se nenhum tutor logou ele informa um erro em vez de gerar um gráfico vazio
            $output .= $this->build_warning('Nenhum tutor logou no moodle no intervalo de tempo selecionado');
        }

        $output .= $this->default_footer();

        return $output;
    }

    /**
     * Mapa de calor de uso do sistema: uma linha por tutor, uma coluna por dia.
     *
     * Substitui o dot chart do g.raphael, que desenhava um circulo por cruzamento com o
     * tamanho e a cor proporcionais as horas. O raphael-min.js empacotado dependia da
     * biblioteca `eve`, que nunca veio junto, entao o grafico quebrava ao carregar desde
     * sempre. Aqui o mesmo desenho sai em HTML e CSS, sem biblioteca nenhuma.
     *
     * A escala e' de matiz unica (mais escuro = mais horas): variacao de luminosidade e'
     * legivel em qualquer tipo de daltonismo. E o numero fica na celula, para a cor nao ser
     * o unico canal -- ver a diretiva em CLAUDE.md.
     *
     * @param array $dados array de tutor => (dia => horas)
     * @return string HTML do mapa de calor
     */
    protected function mapa_de_calor($dados) {
        $maximo = 0;
        foreach ($dados as $dias) {
            foreach ($dias as $horas) {
                $maximo = max($maximo, (float) $horas);
            }
        }
        if ($maximo <= 0) {
            $maximo = 1;
        }

        $output = html_writer::start_tag('div', array('class' => 'relatorio-unasus relatorio-wrapper mapa-calor-wrapper'));
        $output .= html_writer::start_tag('table', array('class' => 'relatorio-unasus mapa-calor'));

        $primeiro = reset($dados);
        $colunas = array_keys($primeiro);

        // Agrupa as colunas por ano e marca em que indice o ano vira, para desenhar a quebra.
        $grupos = array();
        $viradas = array();
        foreach ($colunas as $indice => $dia) {
            $ano = self::ano_de($dia);
            $ultimo = count($grupos) - 1;

            if ($ultimo < 0 || $grupos[$ultimo]['ano'] !== $ano) {
                $grupos[] = array('ano' => $ano, 'colunas' => 1);
                if ($indice > 0) {
                    $viradas[$indice] = true;
                }
            } else {
                $grupos[$ultimo]['colunas']++;
            }
        }

        $output .= html_writer::start_tag('thead');

        // Linha dos anos: cada ano cobre os seus dias. Só aparece quando ha' mais de um,
        // senao repetiria uma informacao que o filtro ja' deixou claro.
        if (count($grupos) > 1) {
            $output .= html_writer::start_tag('tr');
            $output .= html_writer::tag('th', '', array('class' => 'mapa-calor-tutor mapa-calor-canto mapa-calor-fim-grupo'));
            $ultimo = count($grupos) - 1;
            foreach ($grupos as $indice => $grupo) {
                $fim = ($indice < $ultimo) ? ' mapa-calor-fim-grupo' : '';
                $output .= html_writer::tag('th', $grupo['ano'], array(
                    'class' => 'mapa-calor-ano' . $fim, 'colspan' => $grupo['colunas'],
                    'scope' => 'colgroup'));
            }
            $output .= html_writer::end_tag('tr');
        }

        $output .= html_writer::start_tag('tr');
        $output .= html_writer::tag('th', '', array('class' => 'mapa-calor-tutor mapa-calor-fim-grupo'));
        foreach ($colunas as $indice => $dia) {
            // O rotulo vai num span: girar a propria celula deixaria o texto encostado
            // numa das bordas em vez de centralizado sobre a coluna.
            //
            // E mostra so' dia/mes: o ano se repete em todas as colunas sem informar nada,
            // e deixava o rotulo quase o dobro do comprimento. A data inteira continua no
            // title de cada ponto.
            // A ultima coluna antes da virada de ano perde a borda direita: sob border-collapse
            // ela disputaria a mesma aresta com a borda do separador, e a cor mais clara
            // venceria, deixando os dois lados do canal com espessuras diferentes.
            $fim = isset($viradas[$indice + 1]) ? ' mapa-calor-fim-grupo' : '';
            $output .= html_writer::tag('th',
                html_writer::span(self::dia_curto($dia), 'mapa-calor-dia-rotulo'),
                array('class' => 'mapa-calor-dia' . $fim, 'title' => $dia));
        }
        $output .= html_writer::end_tag('tr');
        $output .= html_writer::end_tag('thead');

        $output .= html_writer::start_tag('tbody');
        foreach ($dados as $tutor => $dias) {
            $output .= html_writer::start_tag('tr');
            $output .= html_writer::tag('th', $tutor,
                array('class' => 'mapa-calor-tutor mapa-calor-fim-grupo', 'scope' => 'row'));

            $indice = 0;
            foreach ($dias as $dia => $horas) {
                $fim = isset($viradas[$indice + 1]) ? ' mapa-calor-fim-grupo' : '';
                $indice++;

                $horas = (float) $horas;
                $rotulo = format_float($horas, 1) . 'h';

                // O balao do hover e' o mesmo para celula com e sem valor, e vem do CSS.
                // Mostra so' as horas: a data ja' esta' na coluna, logo acima do ponto.
                // O atributo title fica de fora: somado ao balao, o navegador mostrava dois
                // tooltips na mesma celula.
                $balao = $rotulo;

                if ($horas <= 0) {
                    // Ausencia nao vira circulo: o vazio comunica melhor que um ponto minimo,
                    // e e' assim que o grafico original se comportava.
                    $output .= html_writer::tag('td', html_writer::span($rotulo, 'sr-only'),
                        array('class' => 'mapa-calor-celula' . $fim, 'data-valor' => $balao));
                    continue;
                }

                $proporcao = $horas / $maximo;
                $tamanho = round(8 + ($proporcao * 22));

                $circulo = html_writer::tag('span', '', array(
                    'class' => 'mapa-calor-circulo',
                    'style' => "width: {$tamanho}px; height: {$tamanho}px; "
                        . 'background-color: ' . self::cor_da_escala($proporcao) . ';',
                    'aria-hidden' => 'true'));

                // O valor aparece no hover; para leitor de tela vai sempre, no sr-only.
                $output .= html_writer::tag('td',
                    $circulo . html_writer::span($rotulo, 'sr-only'),
                    array('class' => 'mapa-calor-celula mapa-calor-tem-valor' . $fim,
                          'data-valor' => $balao));
            }
            $output .= html_writer::end_tag('tr');
        }
        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Ano de uma data do mapa de calor.
     *
     * @param string $dia data no formato d/m/Y
     * @return string o ano, ou a entrada inalterada se nao vier nesse formato
     */
    protected static function ano_de($dia) {
        if (preg_match('#^\d{2}/\d{2}/(\d{4})$#', $dia, $partes)) {
            return $partes[1];
        }

        return $dia;
    }

    /**
     * Rotulo curto de uma data do mapa de calor: dia/mes, sem o ano.
     *
     * @param string $dia data no formato d/m/Y
     * @return string dia/mes, ou a entrada inalterada se nao vier nesse formato
     */
    protected static function dia_curto($dia) {
        if (preg_match('#^(\d{2}/\d{2})/\d{4}$#', $dia, $partes)) {
            return $partes[1];
        }

        return $dia;
    }

    /**
     * Cor de um ponto do mapa de calor, numa escala sequencial segura para daltonismo.
     *
     * Usa a viridis, perceptualmente uniforme e legivel nos tres tipos de deficiencia de
     * visao de cores. O grafico original ia de ocre a amarelo-esverdeado -- mesma sensacao
     * de "escuro para claro", mas apoiada num gradiente que confunde vermelho e verde.
     *
     * A cor nunca carrega o valor sozinha: o diametro do circulo varia junto, e o numero
     * esta no hover e no leitor de tela.
     *
     * @param float $proporcao 0 a 1
     * @return string cor em hexadecimal
     */
    protected static function cor_da_escala($proporcao) {
        $paradas = array(
            array(0x44, 0x01, 0x54),
            array(0x41, 0x44, 0x87),
            array(0x2a, 0x78, 0x8e),
            array(0x22, 0xa8, 0x84),
            array(0x7a, 0xd1, 0x51),
            array(0xfd, 0xe7, 0x25),
        );

        $proporcao = max(0, min(1, (float) $proporcao));
        $posicao = $proporcao * (count($paradas) - 1);
        $i = (int) floor($posicao);
        $j = min($i + 1, count($paradas) - 1);
        $t = $posicao - $i;

        $cor = '#';
        foreach (array(0, 1, 2) as $canal) {
            $valor = (int) round($paradas[$i][$canal] + ($paradas[$j][$canal] - $paradas[$i][$canal]) * $t);
            $cor .= str_pad(dechex($valor), 2, '0', STR_PAD_LEFT);
        }

        return $cor;
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
