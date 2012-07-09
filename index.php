<?php

// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php'); // biblioteca local

require_login(SITEID);

// carrega arquivo module.js dentro deste módulo
$PAGE->requires->js_init_call('M.report_unasus.init');

$renderer = $PAGE->get_renderer('report_unasus');

// Renderiza os relatórios
$relatorio = filter_input(INPUT_GET, 'relatorio', FILTER_SANITIZE_STRING);
switch ($relatorio) {

    case 'atividades_vs_notas':
        admin_externalpage_setup('report_unasus_atividade_notas', '', null, '', array('pagelayout' => 'report'));
        echo $renderer->page_atividades_vs_notas_atribuidas($relatorio);
        break;
    case 'entrega_de_atividades':
        admin_externalpage_setup('report_unasus_entrega_atividades', '', null, '', array('pagelayout' => 'report'));
        echo $renderer->page_entrega_de_atividades($relatorio);
        break;
    case 'acompanhamento_de_avaliacao':
        admin_externalpage_setup('report_unasus_acompanhamento_avaliacao', '', null, '', array('pagelayout' => 'report'));
        echo $renderer->page_acompanhamento_de_avaliacao($relatorio);
        break;
    case 'atividades_nao_avaliadas':
        admin_externalpage_setup('report_unasus_atividades_avaliadas', '', null, '', array('pagelayout' => 'report'));
        echo $renderer->page_atividades_nao_avaliadas($relatorio);
        break;
    default:
        print_error('unknow_report', 'report_unasus');
        break;
}

