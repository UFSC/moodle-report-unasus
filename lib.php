<?php

defined('MOODLE_INTERNAL') || die;

function report_unasus_relatorios_validos_list() {
    return array(
        'atividades_vs_notas', 'entrega_de_atividades', 'historico_atribuicao_notas', 'atividades_nao_avaliadas',
        'estudante_sem_atividade_postada', 'estudante_sem_atividade_avaliada', 'atividades_nota_atribuida', 'acesso_tutor', 'uso_sistema_tutor',
        'potenciais_evasoes');
}

/**
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function report_unasus_extend_navigation_course($navigation, $course, $context) {

    if ($course->id != SITEID && has_capability('report/unasus:view', $context)) {
        $reports = report_unasus_relatorios_validos_list();

        foreach($reports as $report) {
            $url = new moodle_url('/report/unasus/index.php', array('relatorio' => $report, 'course' => $course->id));
            $navigation->add(get_string($report, 'report_unasus'), $url, navigation_node::TYPE_SETTING, null, $report, new pix_icon('i/report', ''));
        }
    }
}