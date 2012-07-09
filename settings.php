<?php
defined('MOODLE_INTERNAL') || die;

// Adiciona ao Menu: Atividades vs Notas Atribuídas
$ADMIN->add('reports', new admin_category('unasus', 'UNA-SUS'));
$ADMIN->add('unasus', new admin_externalpage('report_unasus_atividade_notas', 'Atividades vs Notas Atribuídas',
                                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_vs_notas"));
$ADMIN->add('unasus', new admin_externalpage('report_unasus_entrega_atividades', 'Entrega de Atividades',
                                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=entrega_de_atividades"));
$ADMIN->add('unasus', new admin_externalpage('report_unasus_acompanhamento_avaliacao', 'Acompanhamento de Avaliação de Atividade',
                                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=acompanhamento_de_avaliacao"));
$ADMIN->add('unasus', new admin_externalpage('report_unasus_atividades_avaliadas', 'Atividades Postadas e não Avaliadas',
                                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_nao_avaliadas"));


// no report settings
$settings = null;