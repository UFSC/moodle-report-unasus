<?php
defined('MOODLE_INTERNAL') || die;

// Adiciona ao Menu: Atividades vs Notas Atribuídas
$ADMIN->add('reports', new admin_externalpage('report_unasus_atividade_notas', 'Atividades vs Notas Atribuídas',
                                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_vs_notas"));

// no report settings
$settings = null;