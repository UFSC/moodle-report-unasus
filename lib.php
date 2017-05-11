<?php

defined('MOODLE_INTERNAL') || die;

/*
 * Lista contendo todos relatórios
 */
function report_unasus_relatorios_validos_list() {
    // Função usda no report_unasus_factory para validações
    return array_merge(report_unasus_relatorios_validos_tutoria_list(),
        report_unasus_relatorios_restritos_list(),
        report_unasus_relatorios_validos_orientacao_list());
}

/*
 * Apresenta somente os relatórios válidos para a capability tutores
 */
function report_unasus_relatorios_validos_tutoria_list() {
    return array(
        'estudante_sem_atividade_avaliada',
        'estudante_sem_atividade_postada',
        'modulos_concluidos',
        'avaliacoes_em_atraso',
        'atividades_nota_atribuida',
        'atividades_concluidas_agrupadas',
        'entrega_de_atividades',
        'atividades_vs_notas',
        'boletim',
//        'potenciais_evasoes',
    );
}

/*
 * Apresenta somente os relatórios válidos para a capability orientadores
 */
function report_unasus_relatorios_validos_orientacao_list() {
    return array(
        'tcc_consolidado',
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

    $reports = array();

    //Caso usuário seja tutor
    if( ($course->id != SITEID && has_capability('report/unasus:view_all', $context)) ||
        ($course->id != SITEID && has_capability('report/unasus:view_tutoria', $context))
      ) {
        $reports = array_merge($reports, report_unasus_relatorios_validos_tutoria_list());
    }

    //Caso usuário seja coordenador
    if( ($course->id != SITEID && has_capability('report/unasus:view_all', $context))
      ) {
        $reports = array_merge($reports, report_unasus_relatorios_restritos_list());
    }

    //Caso usuário seja orientador
    if( ($course->id != SITEID && has_capability('report/unasus:view_all', $context)) ||
        ($course->id != SITEID && has_capability('report/unasus:view_orientacao', $context))
      ) {
        $reports = array_merge($reports, report_unasus_relatorios_validos_orientacao_list());
    }

    if( ($course->id != SITEID && has_capability('report/unasus:view_all', $context)) ||
        ($course->id != SITEID && has_capability('report/unasus:view_tutoria', $context)) ||
        ($course->id != SITEID && has_capability('report/unasus:view_orientacao', $context))
    ) {

        $unasus_node = $navigation->add(get_string('unasus_navigation_name', 'report_unasus'), null, navigation_node::TYPE_CONTAINER);

        foreach ($reports as $report) {
            $url = new moodle_url('/report/unasus/index.php', array('relatorio' => $report, 'course' => $course->id));
            $unasus_node->add(get_string($report, 'report_unasus'), $url, navigation_node::TYPE_SETTING, null, $report, new pix_icon('i/report', ''));
        }
    }
}
