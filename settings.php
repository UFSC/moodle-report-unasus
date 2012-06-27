<?php
// This file is part
defined('MOODLE_INTERNAL') || die;

// just a link to course report, esse é o nome do módulo que irá aparecer no menu de navegação
$ADMIN->add('reports', new admin_externalpage('report_unasus_atividade_notas', 'Atividades vs Notas Atribuídas',
                                               "$CFG->wwwroot/report/unasus/index.php"));

// no report settings
$settings = null;
