<?php
defined('MOODLE_INTERNAL') || die;

// Adiciona ao Menu: uma subpasta dentro de report
$ADMIN->add('reports', new admin_category('unasus', 'UNA-SUS'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_atividade_notas',
                               'Atividades vs notas atribuídas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_vs_notas",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_entrega_atividades',
                               'Entrega de atividades',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=entrega_de_atividades",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_acompanhamento_avaliacao',
                               'Acompanhamento de avaliação de atividade',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=acompanhamento_de_avaliacao",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_atividades_avaliadas',
                               'Atividades postadas e não avaliadas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_nao_avaliadas",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_estudante_sem_atividade_postada',
                               'Estudantes sem atividades postadas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=estudante_sem_atividade_postada",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_avaliacao_em_atraso',
                               'Atividades com avaliação em atraso',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=avaliacao_em_atraso",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_atividades_nota_atribuida',
                               'Atividades com notas atribuídas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_nota_atribuida",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_uso_sistema_tutor',
                               'Uso do sistema pelo tutor',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=uso_sistema_tutor",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_acesso_tutor',
                               'Acesso ao moodle pelos tutores',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=acesso_tutor",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_potenciais_evasoes',
                               'Potenciais evasões',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=potenciais_evasoes",
                               'report/unasus:view'));

// no report settings
$settings = null;
