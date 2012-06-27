<?php

// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// chamada da biblioteca local
require_once($CFG->dirroot . '/report/unasus/locallib.php');

require_login(SITEID);


// "Orientação a objetos" chamando a pagina desejada no renderer.php, report_exemplo refere-se ao
// caminho de pastas para encontrar o arquivo renderer.php
$renderer = $PAGE->get_renderer('report_unasus');

$relatorio = filter_input(INPUT_GET, 'relatorio', FILTER_SANITIZE_STRING);

// Carrega o layout dos reports
switch ($relatorio) {

    case 'atividades_vs_notas':
        admin_externalpage_setup('report_unasus_atividade_notas', '', null, '', array('pagelayout' => 'report'));
        echo $renderer->index_page();
        break;
    default:
        print_error('unknow_report', 'report_unasus');
        break;
}

