<?php

// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php'); // biblioteca local
require_once($CFG->dirroot . '/report/unasus/lib.php'); // biblioteca local
require_once($CFG->dirroot . '/report/unasus/factory.php'); // fabrica de relatorios

/** @var $FACTORY Factory */
$FACTORY = Factory::singleton();
// Verifica se é um relatorio valido
$FACTORY->set_relatorio(optional_param('relatorio', null, PARAM_ALPHANUMEXT));
// Verifica se é um modo de exibicao valido
$FACTORY->set_modo_exibicao(optional_param('modo_exibicao', null, PARAM_ALPHANUMEXT));


// Usuário tem de estar logado no curso moodle
require_login($FACTORY->get_curso_moodle());
// Usuário tem de ter a permissão para ver o relatório?
require_capability('report/unasus:view', $FACTORY->get_context());


// Usuário tem permissão para ver os relatorios restritos
if (in_array($FACTORY->get_relatorio(), report_unasus_relatorios_restritos_list())) {
    require_capability('report/unasus:view_all', $FACTORY->get_context());
}

// Confurações da pagina HTML
$PAGE->set_url('/report/unasus/index.php', $FACTORY->get_page_params());
$PAGE->set_pagelayout('report');
$PAGE->requires->js_init_call('M.report_unasus.init'); // carrega arquivo module.js dentro deste módulo


/** @var $renderer report_unasus_renderer */
$renderer = $PAGE->get_renderer('report_unasus');

// Renderiza os relatórios
if ($FACTORY->get_relatorio() != null && $FACTORY->get_modo_exibicao() == null) {
    $FACTORY->ocultar_barra_filtragem = false;

    switch ($FACTORY->get_relatorio()) {
        // - relatório desativado segundo o ticket #4460 case 'historico_atribuicao_notas':
        case 'atividades_vs_notas':
        case 'entrega_de_atividades':
        case 'boletim':
            echo $renderer->build_page();
            break;
        case 'atividades_nao_avaliadas':
        case 'estudante_sem_atividade_postada':
        case 'estudante_sem_atividade_avaliada':
        case 'atividades_nota_atribuida' :
        case 'potenciais_evasoes' :
            $FACTORY->mostrar_botoes_grafico = false;
            echo $renderer->build_page();
            break;
        case 'acesso_tutor' :
            $FACTORY->mostrar_filtro_modulos = false;
            $FACTORY->mostrar_botoes_grafico = false;
            $FACTORY->mostrar_filtro_polos = false;
            $FACTORY->mostrar_filtro_intervalo_tempo = true;
            echo $renderer->build_page();
            break;
        case 'uso_sistema_tutor' :
            $FACTORY->mostrar_filtro_modulos = false;
            $FACTORY->mostrar_botoes_grafico = false;
            $FACTORY->mostrar_botoes_dot_chart = true;
            $FACTORY->mostrar_filtro_intervalo_tempo = true;
            $FACTORY->mostrar_filtro_polos = false;
            echo $renderer->build_page();
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }


} else if ($FACTORY->get_relatorio() != null && ($FACTORY->get_modo_exibicao() === 'tabela' || $FACTORY->get_modo_exibicao() == null)) {
    switch ($FACTORY->get_relatorio()) {

        // - relatório desativado segundo o ticket #4460  case 'historico_atribuicao_notas':
        case 'atividades_vs_notas':
        case 'entrega_de_atividades':
        case 'boletim':
            echo $renderer->build_report();
            break;
        case 'atividades_nota_atribuida' :
        case 'atividades_nao_avaliadas':
            echo $renderer->page_atividades_nao_avaliadas();
            break;
        case 'estudante_sem_atividade_postada':
        case 'estudante_sem_atividade_avaliada':
            $FACTORY->mostrar_botoes_grafico = false;
            echo $renderer->page_todo_list();
            break;
        case 'potenciais_evasoes' :
            $FACTORY->mostrar_botoes_grafico = false;
            $FACTORY->texto_cabecalho = 'Tutores';
            echo $renderer->build_report();
            break;
        case 'acesso_tutor' :
            $FACTORY->mostrar_botoes_grafico = false;
            $FACTORY->mostrar_filtro_polos = false;
            $FACTORY->mostrar_filtro_modulos = false;
            $FACTORY->mostrar_filtro_intervalo_tempo = true;
            //As strings informadas sao datas validas?
            if($FACTORY->datas_validas()){
	            $FACTORY->texto_cabecalho = 'Tutores';
                echo $renderer->build_report();
                    //$PAGE->requires->js_init_call('M.report_unasus.init_date_picker');
                break;
            }
            $FACTORY->mostrar_aviso_intervalo_tempo = true;
            echo $renderer->build_page();
            break;
            
        case 'uso_sistema_tutor' :
            $FACTORY->mostrar_botoes_grafico = false;
            $FACTORY->mostrar_botoes_dot_chart = true;
            $FACTORY->mostrar_filtro_polos = false;
            $FACTORY->mostrar_filtro_modulos = false;
            $FACTORY->mostrar_filtro_intervalo_tempo = true;

            //As strings informadas sao datas validas?
            if($FACTORY->datas_validas()){
                $FACTORY->texto_cabecalho = null;
                echo $renderer->build_report();
                //$PAGE->requires->js_init_call('M.report_unasus.init_date_picker');
                break;
            }
            $FACTORY->mostrar_aviso_intervalo_tempo = true;
            echo $renderer->build_page();
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }
} elseif ($FACTORY->get_modo_exibicao() === 'grafico_valores' || $FACTORY->get_modo_exibicao() === 'grafico_porcentagens' || $FACTORY->get_modo_exibicao() === 'grafico_pontos') {
    $porcentagem = false;
    if ($FACTORY->get_modo_exibicao() === 'grafico_porcentagens') {
        $porcentagem = true;
    }
    switch ($FACTORY->get_relatorio()) {

        // - relatório desativado segundo o ticket #4460 case 'historico_atribuicao_notas':
        case 'atividades_vs_notas':
        case 'entrega_de_atividades':
        case 'boletim':
            echo $renderer->build_graph($porcentagem);
            break;
        case 'uso_sistema_tutor' :
            $FACTORY->mostrar_botoes_grafico = false;
            $FACTORY->mostrar_botoes_dot_chart = true;
            $FACTORY->mostrar_filtro_polos = false;
            $FACTORY->mostrar_filtro_modulos = false;
            $FACTORY->mostrar_filtro_intervalo_tempo = true;
            //As strings informadas sao datas validas?
            if($FACTORY->datas_validas()){
                echo $renderer->build_dot_graph();
                break;
            }
            $FACTORY->mostrar_aviso_intervalo_tempo = true;
            echo $renderer->build_page();
            break;

        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }
}

