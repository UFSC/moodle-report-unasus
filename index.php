<?php

/**
 * Relatórios UNASUS
 *
 * Este plugin tem como objetivo gerar relatórios, sobre a forma de tabelas e gráficos,
 * do desemprenho de alunos e tutores dentro de um curso moodle. Existem vários tipos de relatórios
 *
 * 'estudante_sem_atividade_avaliada'
 * 'estudante_sem_atividade_postada'
 * 'atividades_nao_avaliadas'
 * 'atividades_nota_atribuida'
 * 'entrega_de_atividades'
 * 'atividades_vs_notas'
 * 'boletim',
 * 'acesso_tutor'
 * 'uso_sistema_tutor
 * 'potenciais_evasoes'
 *
 * O caminho básico para a renderização de um relatório é
 *
 * index.php (aonde é construida a FACTORY com os parametros via GET e POST e do que vai ser
 * renderizado (tela inicial - somente filtro, tabela - filtro e tabela ou gráfico - filtro e gráfico)
 *
 * Após esta seleção no index.php o relatório segue para o
 * renderer.php (aonde são construidos os filtros, com os parametros setados na FACTORY) e caso necessário
 * são chamadas as funçoes de geração de tabelas e gráficos no arquivo /relatorios/relatorios.php
 */

// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php'); // biblioteca local
require_once($CFG->dirroot . '/report/unasus/lib.php'); // biblioteca global
require_once($CFG->dirroot . '/report/unasus/factory.php'); // fabrica de relatorios
require_once($CFG->dirroot . '/report/unasus/sistematcc.php'); // client ws sistema de tcc

/**
 * Mostra uma falha de configuracao numa pagina normal do Moodle, e encerra.
 *
 * A mensagem vem de quem detectou o problema (em geral o local_tutores). O que se
 * acrescenta aqui e' o CONTEXTO que falta nela: de que configuracao se trata e onde
 * mexer -- sem isso, quem recebe o aviso nao tem por onde comecar.
 *
 * @param string $mensagem
 * @param int $courseid
 * @param string|null $parcial saida ja' emitida antes da falha, se houver
 * @param string|null $detalhe html com o diagnostico especifico
 */
function report_unasus_pagina_de_erro($mensagem, $courseid, $parcial = null, $detalhe = null) {
    global $OUTPUT, $PAGE;

    // ⚠️ Duas situacoes, e a diferenca importa.
    //
    // Se nada foi impresso ainda (falha na factory), monta-se a pagina do zero. Mas se a
    // falha veio no meio da renderizacao, o renderer JA' emitiu o cabecalho e o $PAGE
    // esta' em estado de impressao -- um segundo header() e' recusado com "Invalid state
    // passed to moodle_page::set_state". Nesse caso reaproveita-se o cabecalho que ja'
    // tinha sido montado (tematizado, com menu e migalhas) e escreve-se o aviso embaixo.
    if ($PAGE->state < moodle_page::STATE_PRINTING_HEADER) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('pluginname', 'report_unasus'));
    } else if ($parcial !== null) {
        echo $parcial;
    }

    echo $OUTPUT->notification($mensagem, \core\output\notification::NOTIFY_ERROR);

    if (!empty($detalhe)) {
        echo $detalhe;
    }

    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $courseid)));
    echo $OUTPUT->footer();
    die;
}

// ⚠️ O LOGIN VEM ANTES DA FACTORY, e a ordem importa por dois motivos.
//
// Segurança: a factory le' parametros e consulta o banco; fazer isso antes de saber quem
// e' o usuario e' trabalho feito para quem talvez nem possa ver a pagina.
//
// E a tela de erro: a factory pode estourar (por exemplo, quando ha' mais de um
// Relacionamento de Tutoria no caminho de categorias do curso -- local_tutores/lib.php).
// Estourando ANTES do require_login, o Moodle ainda nao tem contexto nem tema, e cai na
// pagina minima de emergencia -- a mesma do "Redirecionar", sem folha de estilo, sem
// menu, sem migalhas. Com o login antes, o mesmo erro sai numa pagina normal.
$courseid = required_param('course', PARAM_INT);
require_login($courseid);

$PAGE->set_url('/report/unasus/index.php', array('course' => $courseid));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_pagelayout('report');

/** @var $factory report_unasus_factory */
try {
    $report = report_unasus_factory::singleton();
} catch (moodle_exception $e) {
    report_unasus_pagina_de_erro($e->getMessage(), $courseid);
}

// ⚠️ A VALIDACAO VEM ANTES DE IMPRIMIR QUALQUER COISA.
//
// A checagem do local_tutores dispara durante a construcao da NAVEGACAO, ja' dentro do
// cabecalho: a excecao estoura no meio do <head> e a pagina sai sem folha de estilo, sem
// menu, na tela de emergencia do Moodle. Nenhum tratamento posterior conserta isso --
// nem reter a saida em buffer, porque o $PAGE ja' entrou em estado de impressao.
//
// Entao exercita-se aqui, de proposito, a MESMA funcao que estoura, com a pagina ainda em
// branco. Chamar a funcao de verdade (em vez de reimplementar a regra) garante que este
// aviso nunca discorde do comportamento real.
try {
    $categoria_turma = $report->get_categoria_turma_ufsc();

    local_tutores_grupos_tutoria::get_relationship_tutoria($categoria_turma);

    if (in_array($report->get_relatorio(), report_unasus_relatorios_validos_orientacao_list())) {
        local_tutores_grupo_orientacao::get_relationship_orientacao($categoria_turma);
    }
} catch (moodle_exception $e) {
    // A mensagem original diz QUE ha' mais de um; o detalhe abaixo diz QUAIS e ONDE, que
    // e' o que falta para alguem poder corrigir sem ir cavar no banco.
    $detalhe = '';
    $nomes_tag = array('grupo_tutoria' => 'Tutoria', 'grupo_orientacao' => 'Orientação');

    foreach (report_unasus_relacionamentos_em_conflito($categoria_turma) as $tag => $linhas) {
        $itens = '';
        foreach ($linhas as $l) {
            $itens .= html_writer::tag('li',
                html_writer::tag('strong', s($l->relacionamento)) . ' — categoria ' . s($l->categoria));
        }

        $detalhe .= html_writer::tag('p', 'Relacionamentos de ' . s($nomes_tag[$tag]) .
            ' encontrados nesta categoria e abaixo dela (' . count($linhas) . '):');
        $detalhe .= html_writer::tag('ul', $itens);
    }

    // ⚠️ A explicacao acompanha o DIAGNOSTICO, e nao a pagina de erro. Estando na pagina,
    // ela saia tambem para falhas que nada tem a ver com relacionamentos -- um relatorio
    // inexistente, por exemplo, vinha com um paragrafo sobre categorias e tutoria.
    $detalhe .= html_writer::tag('p',
        'Os relatórios dependem da configuração de <strong>Relacionamentos</strong> ' .
        'nas categorias do caminho deste curso: é preciso haver exatamente um de ' .
        'Tutoria e, quando o relatório for de TCC, exatamente um de Orientação. ' .
        'Mover uma categoria para dentro de outra junta os relacionamentos das duas ' .
        'na mesma subárvore, e é o que costuma provocar este erro.');

    report_unasus_pagina_de_erro($e->getMessage(), $courseid, null, $detalhe);
}

// Usuário tem de ter a permissão para ver os relatórios?
// terá permissão de acessar os relatórios se tiver uma das permissões abaixo
if (! (has_capability('report/unasus:view_all', $report->get_context(), null, true) ||
       has_capability('report/unasus:view_tutoria', $report->get_context(), null, true) ||
       has_capability('report/unasus:view_orientacao', $report->get_context(), null, true)
      )
   ) {
    throw new required_capability_exception($report->get_context(),
        'report/unasus:view_all',
        'nopermissions',
        '');
}

// Usuário tem permissão para ver os relatorios de orientação
if (in_array($report->get_relatorio(), report_unasus_relatorios_validos_orientacao_list())) {
    if (! (has_capability('report/unasus:view_all', $report->get_context(), null, true) ||
        has_capability('report/unasus:view_orientacao', $report->get_context(), null, true)
    )
    ) {
        throw new required_capability_exception($report->get_context(),
            'report/unasus:view_orientacao',
            'nopermissions',
            '');
    }
}

// Usuário tem permissão para ver os relatorios de tutoria
if (in_array($report->get_relatorio(), report_unasus_relatorios_validos_tutoria_list())) {
    if (! (has_capability('report/unasus:view_all', $report->get_context(), null, true) ||
        has_capability('report/unasus:view_tutoria', $report->get_context(), null, true)
    )
    ) {
        throw new required_capability_exception($report->get_context(),
            'report/unasus:view_tutoria',
            'nopermissions',
            '');
    }
}

// Usuário tem permissão para ver os relatorios restritos
if (in_array($report->get_relatorio(), report_unasus_relatorios_restritos_list())) {
    require_capability('report/unasus:view_all', $report->get_context());
}

// ⚠️ AVISO DE MOCK ATIVO.
//
// O cliente do Sistema de TCC (sistematcc.php) devolve o conteudo da config
// `report_unasus/behat_tcc_mock_<endpoint>` em vez de chamar o web service, e isso NAO e'
// limitado ao Behat: enquanto a config existir, os dados de TCC na tela sao sinteticos --
// inclusive para estudantes reais, e sem nenhum sinal proprio.
//
// Sob Behat o aviso e' suprimido: la' o mock e' ligado de proposito pelo teste, e um
// aviso a mais na pagina atrapalha as assercoes.
if (!defined('BEHAT_SITE_RUNNING')) {
    $mocks_ativos = $DB->count_records_select('config_plugins',
        "plugin = :plugin AND " . $DB->sql_like('name', ':nome'),
        array('plugin' => 'report_unasus', 'nome' => 'behat_tcc_mock_%'));

    if ($mocks_ativos > 0) {
        \core\notification::add(
            'Dados do Sistema de TCC vindos de MOCK, não do web service. ' .
            'Para desligar, remova a configuração ' .
            '<code>report_unasus / behat_tcc_mock_*</code>.',
            \core\output\notification::NOTIFY_WARNING);
    }
}

// Configurações da pagina HTML
$PAGE->set_url('/report/unasus/index.php', $report->get_page_params());
$PAGE->set_pagelayout('report');
$PAGE->requires->js_init_call('M.report_unasus.init'); // carrega arquivo module.js dentro deste módulo

/** @var $renderer report_unasus_renderer */
$renderer = $PAGE->get_renderer('report_unasus');

$name_report = $report->get_page_params()['relatorio'];
$modo_exibicao = $report->get_modo_exibicao();

// ⚠️ A SAIDA FICA RETIDA ate' a renderizacao terminar.
//
// Boa parte das falhas de configuracao so' aparece DURANTE a montagem da tabela, quando
// o relatorio vai buscar o relacionamento de tutoria ou de orientacao -- e a essa altura
// o cabecalho da pagina ja' foi emitido. Com saida na rua, o Moodle nao consegue mais
// montar uma pagina de erro decente e cai na de emergencia, sem folha de estilo nem
// menu: era o que se via.
//
// Retendo em buffer, a falha encontra a pagina ainda em branco: descarta-se o que ja'
// tinha sido escrito e mostra-se o aviso numa pagina normal. Sem falha, o buffer sai
// inteiro e nada muda.
//
// ⚠️ Nao trocar por uma validacao previa de relacionamentos aqui: seria duplicar, no
// index, uma regra que e' de cada relatorio -- e que muda entre tutoria, orientacao e os
// relatorios restritos. O buffer pega qualquer falha, inclusive as que ainda nao existem.
ob_start();

try {
    //Primeiro acesso ao relatório
    if ($modo_exibicao == null) {
        echo $report->render_report_default($renderer);
    }

    switch ($modo_exibicao) {
        case 'tabela':
            $report->render_report_table($renderer);
            break;
        case 'grafico_valores':
        case 'grafico_porcentagens':
        case 'grafico_agrupado':
        case 'grafico_pontos':
            // ⚠️ Nem todo relatorio implementa grafico, e ate' aqui a chamada era feita
            // as cegas: quem nao implementa estourava com "Call to undefined method
            // render_report_graph()" -- fatal cru, e nao a mensagem que existe no lang
            // justamente para este caso. Pela interface nao acontecia, porque os botoes
            // de grafico so' aparecem em quem os tem; por URL editada a mao ou favorito
            // antigo, acontecia.
            if (!method_exists($report, 'render_report_graph')) {
                throw new moodle_exception('unimplemented_graph_error', 'report_unasus');
            }

            $porcentagem = ($modo_exibicao === 'grafico_porcentagens');
            // Empilhado mostra a composicao de cada grupo; agrupado poe as barras lado a
            // lado, que e' o que permite comparar o mesmo estado entre grupos.
            $empilhado = ($modo_exibicao !== 'grafico_agrupado');
            $report->render_report_graph($renderer, $porcentagem, $empilhado);
            break;
        case 'export_csv':
            $report->render_report_csv($name_report);
            break;
    }

    ob_end_flush();
} catch (moodle_exception $e) {
    // O que ja' foi montado ate' a falha -- na pratica, o cabecalho tematizado e, talvez,
    // a barra de filtros. E' isso que salva a pagina de sair crua.
    report_unasus_pagina_de_erro($e->getMessage(), $courseid, ob_get_clean());
}



