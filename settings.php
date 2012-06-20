<?php
// This file is part
defined('MOODLE_INTERNAL') || die;

// just a link to course report, esse é o nome do módulo que irá aparecer no menu de navegação
$ADMIN->add('reports', new admin_externalpage('reportatividadevsnota', 'Relatório Atividade vs Nota',
                                               "$CFG->wwwroot/report/atividadevsnota/index.php?id=".SITEID));

// no report settings
$settings = null;
