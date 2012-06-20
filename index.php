<?php

// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// chamada da biblioteca local
require_once($CFG->dirroot.'/report/atividadevsnota/locallib.php');

require_login(SITEID);
// Carrega o layout dos reports
admin_externalpage_setup('reportatividadevsnota', '', null, '', array('pagelayout'=>'report'));

// "Orientação a objetos" chamando a pagina desejada no renderer.php, report_exemplo refere-se ao
// caminho de pastas para encontrar o arquivo renderer.php
$renderer = $PAGE->get_renderer('report_atividadevsnota');
echo $renderer->index_page();
