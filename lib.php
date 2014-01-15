<?php

defined('MOODLE_INTERNAL') || die;

function report_unasus_relatorios_validos_list() {
    return array(
        'estudante_sem_atividade_avaliada',
        'estudante_sem_atividade_postada',
        'atividades_nao_avaliadas',
        'atividades_nota_atribuida',
        'entrega_de_atividades',
        'atividades_vs_notas',
        // - relatório desativado segundo o ticket #4460 'historico_atribuicao_notas',
        'boletim',
        'acesso_tutor',
        'uso_sistema_tutor',
        'potenciais_evasoes',
        'tcc_portfolio',
        'tcc_portfolio_concluido',
        'tcc_portfolio_entrega_atividades',
//        'tcc_tcc',
        'tcc_entrega_atividades',
        'tcc_concluido');
}

function report_unasus_relatorios_restritos_list() {
    return array('acesso_tutor', 'uso_sistema_tutor');
}

/**
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function report_unasus_extend_navigation_course($navigation, $course, $context) {

    $restricted_list = report_unasus_relatorios_restritos_list();

    if ($course->id != SITEID && has_capability('report/unasus:view', $context)) {
        $reports = report_unasus_relatorios_validos_list();

        foreach ($reports as $report) {
            if (in_array($report, $restricted_list) && !has_capability('report/unasus:view_all', $context)) {
                continue;
            }

            $url = new moodle_url('/report/unasus/index.php', array('relatorio' => $report, 'course' => $course->id));
            $navigation->add(get_string($report, 'report_unasus'), $url, navigation_node::TYPE_SETTING, null, $report, new pix_icon('i/report', ''));
        }
    }
}