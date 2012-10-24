<?php
defined('MOODLE_INTERNAL') || die;

// no report settings
$settings = null;

if ($hassiteconfig) {

    $settings = new admin_settingpage('reportunasus', get_string('report_unasus_settings', 'report_unasus'));

    $settings->add(new admin_setting_heading('report_unasus_tutor_heading', get_string('report_unasus_tutor_heading', 'report_unasus'), null));

    $settings->add(new admin_setting_configtext('report_unasus_prazo_avaliacao',
        get_string('settings_prazo_esperado_avaliacao', 'report_unasus'),
        get_string('description_prazo_esperado_avaliacao', 'report_unasus'), 2, PARAM_INT));
    $settings->add(new admin_setting_configtext('report_unasus_prazo_maximo_avaliacao',
        get_string('settings_prazo_maximo_avaliacao', 'report_unasus'),
        get_string('description_prazo_maximo_avaliacao', 'report_unasus'), 7, PARAM_INT));

    $settings->add(new admin_setting_heading('report_unasus_estudante_heading', get_string('report_unasus_estudante_heading', 'report_unasus'), null));

    $settings->add(new admin_setting_configtext('report_unasus_prazo_entrega',
        get_string('settings_tolerancia_entrega', 'report_unasus'),
        get_string('description_tolerancia_entrega', 'report_unasus'), 5, PARAM_INT));
    $settings->add(new admin_setting_configtext('report_unasus_prazo_maximo_entrega',
        get_string('settings_tolerancia_entrega_maxima', 'report_unasus'),
        get_string('description_tolerancia_entrega_maxima', 'report_unasus'), 10, PARAM_INT));

    $settings->add(new admin_setting_configtext('report_unasus_tolerancia_potencial_evasao',
        get_string('settings_tolerancia_potencial_evasao', 'report_unasus'),
        get_string('description_tolerancia_potencial_evasao', 'report_unasus'), 3, PARAM_INT));
}