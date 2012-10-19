<?php

// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php'); // biblioteca local
require_once($CFG->dirroot . '/report/unasus/lib.php');

$courseid = get_course_id();
$relatorio = optional_param('relatorio', null, PARAM_ALPHANUMEXT);
$modo_exibicao = optional_param('modo_exibicao', null, PARAM_ALPHANUMEXT);

$params = array('relatorio' => $relatorio, 'course' => $courseid);

// verificar se o relatório é válido e inicializar página
// caso contrário, mostrar erro.
if (in_array($relatorio, report_unasus_relatorios_validos_list())) {
    $PAGE->set_url('/report/unasus/index.php', $params);
    $PAGE->set_pagelayout('report');
    $PAGE->requires->js_init_call('M.report_unasus.init'); // carrega arquivo module.js dentro deste módulo

    require_login($courseid);
    $renderer = $PAGE->get_renderer('report_unasus');
} else {
    print_error('unknow_report', 'report_unasus');
}

// Renderiza os relatórios
if ($relatorio != null && $modo_exibicao == null) {
    switch ($relatorio) {
        case 'atividades_vs_notas':
        case 'entrega_de_atividades':
        case 'historico_atribuicao_notas':
            echo $renderer->build_page();
            break;
        case 'atividades_nao_avaliadas':
        case 'estudante_sem_atividade_postada':
        case 'estudante_sem_atividade_avaliada':
        case 'atividades_nota_atribuida' :
        case 'acesso_tutor' :
        case 'potenciais_evasoes' :
            echo $renderer->build_page(false);
            break;
        case 'uso_sistema_tutor' :
            echo $renderer->build_page(false, true);
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }


} else if ($relatorio != null && ($modo_exibicao === 'tabela' || $modo_exibicao == null)) {
    switch ($relatorio) {

        case 'atividades_vs_notas':
        case 'entrega_de_atividades':
        case 'historico_atribuicao_notas':
            echo $renderer->build_report();
            break;
        case 'atividades_nota_atribuida' :
        case 'atividades_nao_avaliadas':
            echo $renderer->page_atividades_nao_avaliadas();
            break;
        case 'estudante_sem_atividade_postada':
        case 'estudante_sem_atividade_avaliada':
            echo $renderer->page_todo_list();
            break;
        case 'acesso_tutor' :
        case 'potenciais_evasoes' :
            echo $renderer->build_report(false);
            break;
        case 'uso_sistema_tutor' :
            echo $renderer->build_report(false, true);
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }
} elseif ($modo_exibicao === 'grafico_valores' || $modo_exibicao === 'grafico_porcentagens' || $modo_exibicao === 'grafico_pontos') {
    $porcentagem = false;
    if ($modo_exibicao === 'grafico_porcentagens') {
        $porcentagem = true;
    }
    switch ($relatorio) {

        case 'atividades_vs_notas':
        case 'entrega_de_atividades':
        case 'historico_atribuicao_notas':
            echo $renderer->build_graph($porcentagem);
            break;
        case 'uso_sistema_tutor' :
            echo $renderer->build_dot_graph();
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }
}