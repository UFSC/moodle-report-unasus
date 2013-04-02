<?php

/**
 * Relatórios UNASUS
 *
 * Este plugin tem como objetivo gerar relatórios, sobre a forma de tabelas e gráficos,
 * do desemprenho de alunos e tutores dentro de um curso moodle. Existem vários tipos de relatórios
 *
 * 'estudante_sem_atividade_avaliada'
 * 'estudante_sem_atividade_postada'
 * 'atividades_nao_avaliadas'
 * 'atividades_nota_atribuida'
 * 'entrega_de_atividades'
 * 'atividades_vs_notas'
 * 'boletim',
 * 'acesso_tutor'
 * 'uso_sistema_tutor
 * 'potenciais_evasoes'
 *
 * O caminho básico para a renderização de um relatório é
 *
 * index.php (aonde é construida a FACTORY com os parametros via GET e POST e do que vai ser
 * renderizado (tela inicial - somente filtro, tabela - filtro e tabela ou gráfico - filtro e gráfico)
 *
 * Após esta seleção no index.php o relatório segue para o
 * renderer.php (aonde são construidos os filtros, com os parametros setados na FACTORY) e caso necessário
 * são chamadas as funçoes de geração de tabelas e gráficos no arquivo /relatorios/relatorios.php
 */


// Bibiotecas minimas necessarias para ser um plugin da area administrativa
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php'); // biblioteca local
require_once($CFG->dirroot . '/report/unasus/lib.php'); // biblioteca global
require_once($CFG->dirroot . '/report/unasus/factory.php'); // fabrica de relatorios

/** @var $factory Factory */
$factory = Factory::singleton();

// Usuário tem de estar logado no curso moodle
require_login($factory->get_curso_moodle());
// Usuário tem de ter a permissão para ver o relatório?
require_capability('report/unasus:view', $factory->get_context());


// Usuário tem permissão para ver os relatorios restritos
if (in_array($factory->get_relatorio(), report_unasus_relatorios_restritos_list())) {
    require_capability('report/unasus:view_all', $factory->get_context());
}

// Configurações da pagina HTML
$PAGE->set_url('/report/unasus/index.php', $factory->get_page_params());
$PAGE->set_pagelayout('report');
$PAGE->requires->js_init_call('M.report_unasus.init'); // carrega arquivo module.js dentro deste módulo


/** @var $renderer report_unasus_renderer */
$renderer = $PAGE->get_renderer('report_unasus');

// Renderiza os relatórios

// Somente barra de filtragem, ou seja, tela inicial do relatório
if ($factory->get_relatorio() != null && $factory->get_modo_exibicao() == null) {
    $factory->mostrar_barra_filtragem = true;

    switch ($factory->get_relatorio()) {
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
            $factory->mostrar_botoes_grafico = false;
            echo $renderer->build_page();
            break;
        case 'acesso_tutor' :
            $factory->mostrar_filtro_modulos = false;
            $factory->mostrar_botoes_grafico = false;
            $factory->mostrar_filtro_polos = false;
            $factory->mostrar_filtro_intervalo_tempo = true;
            echo $renderer->build_page();
            break;
        case 'uso_sistema_tutor' :
            $factory->mostrar_filtro_modulos = false;
            $factory->mostrar_botoes_grafico = false;
            $factory->mostrar_botoes_dot_chart = true;
            $factory->mostrar_filtro_intervalo_tempo = true;
            $factory->mostrar_filtro_polos = false;
            echo $renderer->build_page();
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }

// Construção da tabela de dados
} else if ($factory->get_relatorio() != null && ($factory->get_modo_exibicao() === 'tabela' || $factory->get_modo_exibicao() == null)) {
    switch ($factory->get_relatorio()) {

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
            $factory->mostrar_botoes_grafico = false;
            echo $renderer->page_todo_list();
            break;
        case 'potenciais_evasoes' :
            $factory->mostrar_botoes_grafico = false;
            $factory->texto_cabecalho = 'Tutores';
            echo $renderer->build_report();
            break;
        case 'acesso_tutor' :
            $factory->mostrar_botoes_grafico = false;
            $factory->mostrar_filtro_polos = false;
            $factory->mostrar_filtro_modulos = false;
            $factory->mostrar_filtro_intervalo_tempo = true;
            //As strings informadas sao datas validas?
            if ($factory->datas_validas()) {
                $factory->texto_cabecalho = 'Tutores';
                echo $renderer->build_report();
                //$PAGE->requires->js_init_call('M.report_unasus.init_date_picker');
                break;
            }
            $factory->mostrar_aviso_intervalo_tempo = true;
            echo $renderer->build_page();
            break;

        case 'uso_sistema_tutor' :
            $factory->mostrar_botoes_grafico = false;
            $factory->mostrar_botoes_dot_chart = true;
            $factory->mostrar_filtro_polos = false;
            $factory->mostrar_filtro_modulos = false;
            $factory->mostrar_filtro_intervalo_tempo = true;

            //As strings informadas sao datas validas?
            if ($factory->datas_validas()) {
                $factory->texto_cabecalho = null;
                echo $renderer->build_report();
                //$PAGE->requires->js_init_call('M.report_unasus.init_date_picker');
                break;
            }
            $factory->mostrar_aviso_intervalo_tempo = true;
            echo $renderer->build_page();
            break;
        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }

// Construção dos gráficos
} elseif ($factory->get_modo_exibicao() === 'grafico_valores' || $factory->get_modo_exibicao() === 'grafico_porcentagens' || $factory->get_modo_exibicao() === 'grafico_pontos') {
    $porcentagem = false;
    if ($factory->get_modo_exibicao() === 'grafico_porcentagens') {
        $porcentagem = true;
    }
    switch ($factory->get_relatorio()) {

        // - relatório desativado segundo o ticket #4460 case 'historico_atribuicao_notas':
        case 'atividades_vs_notas':
        case 'entrega_de_atividades':
        case 'boletim':
            echo $renderer->build_graph($porcentagem);
            break;
        case 'uso_sistema_tutor' :
            $factory->mostrar_botoes_grafico = false;
            $factory->mostrar_botoes_dot_chart = true;
            $factory->mostrar_filtro_polos = false;
            $factory->mostrar_filtro_modulos = false;
            $factory->mostrar_filtro_intervalo_tempo = true;
            //As strings informadas sao datas validas?
            if ($factory->datas_validas()) {
                echo $renderer->build_dot_graph();
                break;
            }
            $factory->mostrar_aviso_intervalo_tempo = true;
            echo $renderer->build_page();
            break;

        default:
            print_error('unknow_report', 'report_unasus');
            break;
    }
}

