<?php

defined('MOODLE_INTERNAL') || die;

/*
 * Lista contendo todos relatórios
 */
function report_unasus_relatorios_validos_list() {
    return array(
        'estudante_sem_atividade_avaliada',
        'estudante_sem_atividade_postada',
        'modulos_concluidos',
        'avaliacoes_em_atraso',
        'atividades_nota_atribuida',
        'atividades_concluidas_agrupadas',
        'entrega_de_atividades',
        'atividades_vs_notas',
        // - relatório desativado segundo o ticket #4460 'historico_atribuicao_notas',
        'boletim',
        'acesso_tutor',
        'uso_sistema_tutor',
        'potenciais_evasoes',
        // Relatórios desativados para nova versão do TCC - ticket #7528
        /*'tcc_portfolio_consolidado',
        'tcc_portfolio_concluido',
        'tcc_portfolio_entrega_atividades',*/

        'tcc_consolidado',
        'tcc_entrega_atividades',
        'tcc_concluido');
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
        // - relatório desativado segundo o ticket #4460 'historico_atribuicao_notas',
        'boletim',
        'acesso_tutor',
        'uso_sistema_tutor',
        'potenciais_evasoes',
        // Relatórios desativados para nova versão do TCC - ticket #7528
        /*'tcc_portfolio_consolidado',
        'tcc_portfolio_concluido',
        'tcc_portfolio_entrega_atividades',*/);
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

    $restricted_list = report_unasus_relatorios_restritos_list();

    if ($course->id != SITEID && has_capability('report/unasus:view', $context)) {

        //Caso usuário seja tutor
        if(($course->id != SITEID && has_capability('report/unasus:view_tutoria', $context)) &&
            ($course->id != SITEID && has_capability('report/unasus:view_all', $context))) {
                $reports = report_unasus_relatorios_validos_tutoria_list();
        }

        //Caso usuário seja orientador
        if($course->id != SITEID && has_capability('report/unasus:view_orientacao', $context) &&
            ($course->id != SITEID && has_capability('report/unasus:view_all', $context))) {
                $reports = report_unasus_relatorios_validos_orientacao_list();
        }

        //Caso usuário seja ambos
        if(($course->id != SITEID && has_capability('report/unasus:view_tutoria', $context)) &&
            ($course->id != SITEID && has_capability('report/unasus:view_orientacao', $context))) {
                $reports = report_unasus_relatorios_validos_list();
        }

        $unasus_node = $navigation->add(get_string('unasus_navigation_name', 'report_unasus'), null, navigation_node::TYPE_CONTAINER);
        foreach ($reports as $report) {
            if (in_array($report, $restricted_list) && !has_capability('report/unasus:view_all', $context)) {
                continue;
            }

            $url = new moodle_url('/report/unasus/index.php', array('relatorio' => $report, 'course' => $course->id));
            $unasus_node->add(get_string($report, 'report_unasus'), $url, navigation_node::TYPE_SETTING, null, $report, new pix_icon('i/report', ''));
        }
    }
}
