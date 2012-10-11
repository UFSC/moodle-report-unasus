<?php
defined('MOODLE_INTERNAL') || die;

// Adiciona ao Menu: uma subpasta dentro de report
$ADMIN->add('reports', new admin_category('unasus', 'UNA-SUS'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_atividades_vs_notas',
                               'Acompanhamento: atribuição de notas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_vs_notas",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_entrega_de_atividades',
                               'Acompanhamento: entrega de atividades',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=entrega_de_atividades",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_historico_atribuicao_notas',
                               'Histórico: atribuição de notas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=historico_atribuicao_notas",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_estudante_sem_atividade_postada',
                               'Lista: atividades não postadas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=estudante_sem_atividade_postada",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_estudante_sem_atividade_avaliada',
                               'Lista: atividades não avaliadas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=estudante_sem_atividade_avaliada",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_atividades_nao_avaliadas',
                               'Síntese: avaliações em atraso',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_nao_avaliadas",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_atividades_nota_atribuida',
                               'Síntese: atividades concluídas',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=atividades_nota_atribuida",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_uso_sistema_tutor',
                               'Uso do sistema pelo tutor (horas)',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=uso_sistema_tutor",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_acesso_tutor',
                               'Uso do sistema pelo tutor (acessos)',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=acesso_tutor",
                               'report/unasus:view'));
$ADMIN->add('unasus',
        new admin_externalpage('report_unasus_potenciais_evasoes',
                               'Potenciais evasões',
                               "{$CFG->wwwroot}/report/unasus/index.php?relatorio=potenciais_evasoes",
                               'report/unasus:view'));
// no report settings
$settings = null;

if ($hassiteconfig) {

    $settings = new admin_settingpage('reportunasus', get_string('report_unasus_settings', 'report_unasus'));

    $settings->add(new admin_setting_heading('report_unasus_tutor_heading', get_string('report_unasus_tutor_heading', 'report_unasus'), null));

    $settings->add(new admin_setting_configtext('report_unasus_prazo_esperado_avaliacao',
        get_string('settings_prazo_esperado_avaliacao', 'report_unasus'),
        get_string('description_prazo_esperado_avaliacao', 'report_unasus'), 2, PARAM_INT));
    $settings->add(new admin_setting_configtext('report_unasus_prazo_maximo_avaliacao',
        get_string('settings_prazo_maximo_avaliacao', 'report_unasus'),
        get_string('description_prazo_maximo_avaliacao', 'report_unasus'), 7, PARAM_INT));

    $settings->add(new admin_setting_heading('report_unasus_estudante_heading', get_string('report_unasus_estudante_heading', 'report_unasus'), null));

    $settings->add(new admin_setting_configtext('report_unasus_tolerancia_entrega',
        get_string('settings_tolerancia_entrega', 'report_unasus'),
        get_string('description_tolerancia_entrega', 'report_unasus'), 5, PARAM_INT));
    $settings->add(new admin_setting_configtext('report_unasus_tolerancia_entrega_maxima',
        get_string('settings_tolerancia_entrega_maxima', 'report_unasus'),
        get_string('description_tolerancia_entrega_maxima', 'report_unasus'), 10, PARAM_INT));
}