<?php

// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php'); // biblioteca local

require_login(SITEID);

// carrega arquivo module.js dentro deste módulo
$PAGE->requires->js_init_call('M.report_unasus.init');

$renderer = $PAGE->get_renderer('report_unasus');
$relatorio = optional_param('relatorio', null, PARAM_ALPHANUMEXT);
$modo_exibicao = optional_param('modo_exibicao', null, PARAM_ALPHANUMEXT);

// Renderiza os relatórios
if($relatorio != null && $modo_exibicao == null){
    switch ($relatorio) {
        case 'atividades_vs_notas':
            admin_externalpage_setup('report_unasus_atividade_notas', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page();
            break;
        case 'entrega_de_atividades':
            admin_externalpage_setup('report_unasus_entrega_atividades', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page();
            break;
        case 'acompanhamento_de_avaliacao':
            admin_externalpage_setup('report_unasus_acompanhamento_avaliacao', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page();
            break;
        case 'atividades_nao_avaliadas':
            admin_externalpage_setup('report_unasus_atividades_avaliadas', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page(false);
            break;
        case 'estudante_sem_atividade_postada':
            admin_externalpage_setup('report_unasus_estudante_sem_atividade_postada', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page(false);
            break;
        case 'estudante_sem_atividade_avaliada':
            admin_externalpage_setup('report_unasus_estudante_sem_atividade_avaliada', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page(false);
            break;
        case 'atividades_nota_atribuida' :
            admin_externalpage_setup('report_unasus_atividades_nota_atribuida', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page(false);
            break;
        case 'acesso_tutor' :
            admin_externalpage_setup('report_unasus_acesso_tutor', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page(false);
            break;
        case 'uso_sistema_tutor' :
            admin_externalpage_setup('report_unasus_uso_sistema_tutor', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page(false, true);
            break;
        case 'potenciais_evasoes' :
            admin_externalpage_setup('report_unasus_potenciais_evasoes', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_page(false);
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }


}else if ($relatorio != null && ($modo_exibicao === 'tabela' || $modo_exibicao == null)) {
    switch ($relatorio) {

        case 'atividades_vs_notas':
            admin_externalpage_setup('report_unasus_atividade_notas', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_report();
            break;
        case 'entrega_de_atividades':
            admin_externalpage_setup('report_unasus_entrega_atividades', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_report();
            break;
        case 'acompanhamento_de_avaliacao':
            admin_externalpage_setup('report_unasus_acompanhamento_avaliacao', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_report();
            break;
        case 'atividades_nao_avaliadas':
            admin_externalpage_setup('report_unasus_atividades_avaliadas', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->page_atividades_nao_avaliadas();
            break;
        case 'estudante_sem_atividade_postada':
            admin_externalpage_setup('report_unasus_estudante_sem_atividade_postada', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->page_todo_list();
            break;
        case 'estudante_sem_atividade_avaliada':
            admin_externalpage_setup('report_unasus_estudante_sem_atividade_avaliada', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->page_todo_list();
            break;
        case 'atividades_nota_atribuida' :
            admin_externalpage_setup('report_unasus_atividades_nota_atribuida', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_report(false);
            break;
        case 'uso_sistema_tutor' :
            admin_externalpage_setup('report_unasus_uso_sistema_tutor', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_report(false,true);
            break;
        case 'acesso_tutor' :
            admin_externalpage_setup('report_unasus_acesso_tutor', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_report(false);
            break;
        case 'potenciais_evasoes' :
            admin_externalpage_setup('report_unasus_potenciais_evasoes', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_report(false);
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }
} elseif ($modo_exibicao === 'grafico_valores' || $modo_exibicao === 'grafico_porcentagens' || $modo_exibicao === 'grafico_pontos') {
    $porcentagem = false;
    if($modo_exibicao === 'grafico_porcentagens'){
        $porcentagem = true;
    }
    switch ($relatorio) {

        case 'atividades_vs_notas':
            admin_externalpage_setup('report_unasus_atividade_notas', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_graph($porcentagem);
            break;
        case 'entrega_de_atividades':
            admin_externalpage_setup('report_unasus_entrega_atividades', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_graph($porcentagem);
            break;
        case 'acompanhamento_de_avaliacao':
            admin_externalpage_setup('report_unasus_acompanhamento_avaliacao', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_graph($porcentagem);
            break;
        case 'uso_sistema_tutor' :
            admin_externalpage_setup('report_unasus_uso_sistema_tutor', '', null, '', array('pagelayout' => 'report'));
            echo $renderer->build_dot_graph();
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }
}
