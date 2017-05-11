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

/** @var $factory report_unasus_factory */
$report = report_unasus_factory::singleton();

// Usuário tem de estar logado no curso moodle
require_login($report->get_curso_moodle());

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

// Configurações da pagina HTML
$PAGE->set_url('/report/unasus/index.php', $report->get_page_params());
$PAGE->set_pagelayout('report');
$PAGE->requires->js_init_call('M.report_unasus.init'); // carrega arquivo module.js dentro deste módulo

/** @var $renderer report_unasus_renderer */
$renderer = $PAGE->get_renderer('report_unasus');

$name_report = $report->get_page_params()['relatorio'];
$modo_exibicao = $report->get_modo_exibicao();

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
    case 'grafico_pontos':
        $porcentagem = ($modo_exibicao === 'grafico_porcentagens');
        $report->render_report_graph($renderer, $porcentagem);
        break;
    case 'export_csv':
        $report->render_report_csv($name_report);
        break;
}



