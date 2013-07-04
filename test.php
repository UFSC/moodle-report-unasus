<?php

/**
 * Relatórios UNASUS Teste
 *
 */
// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php'); // biblioteca local
require_once($CFG->dirroot . '/report/unasus/lib.php'); // biblioteca global
require_once($CFG->dirroot . '/report/unasus/factory.php'); // fabrica de relatorios

/** @var $factory Factory */
$factory = Factory::singleton();

// Usuário tem de estar logado no curso moodle
require_login($factory->get_curso_moodle());
// Usuário tem de ter a permissão para ver o relatório?
require_capability('report/unasus:view', $factory->get_context());


// Usuário tem permissão para ver os relatorios restritos
if (in_array($factory->get_relatorio(), report_unasus_relatorios_restritos_list())) {
    require_capability('report/unasus:view_all', $factory->get_context());
}

// Configurações da pagina HTML
$PAGE->set_url('/report/unasus/test.php', $factory->get_page_params());
$PAGE->set_pagelayout('report');
$PAGE->requires->js_init_call('M.report_unasus.init'); // carrega arquivo module.js dentro deste módulo


/** @var $renderer report_unasus_renderer */
$renderer = $PAGE->get_renderer('report_unasus');

echo $renderer->build_fixed_report();
