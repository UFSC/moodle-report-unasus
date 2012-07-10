<?php
defined('MOODLE_INTERNAL') || die;

// Adiciona ao Menu: uma subpasta dentro de report
$ADMIN->add('reports', new admin_category('unasus', 'UNA-SUS'));
$ADMIN->add('unasus', 
        new admin_externalpage('report_unasus_atividade_notas', 
                               'Atividades vs Notas Atribuídas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_vs_notas"));
$ADMIN->add('unasus', 
        new admin_externalpage('report_unasus_entrega_atividades', 
                               'Entrega de Atividades',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=entrega_de_atividades"));
$ADMIN->add('unasus', 
        new admin_externalpage('report_unasus_acompanhamento_avaliacao', 
                               'Acompanhamento de Avaliação de Atividade',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=acompanhamento_de_avaliacao"));
$ADMIN->add('unasus', 
        new admin_externalpage('report_unasus_atividades_avaliadas', 
                               'Atividades Postadas e não Avaliadas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_nao_avaliadas"));
$ADMIN->add('unasus', 
        new admin_externalpage('report_unasus_estudante_sem_atividade_postada', 
                               'Relatório de Estudantes sem Atividades Postadas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=estudante_sem_atividade_postada"));

$ADMIN->add('unasus', 
        new admin_externalpage('report_unasus_atividades_em_atraso_tutor', 
                               'Atividades com Avaliação em Atraso por Tutor',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_em_atraso_tutor"));

// no report settings
$settings = null;