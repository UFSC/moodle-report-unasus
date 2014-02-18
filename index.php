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
require_once($CFG->dirroot . '/report/unasus/sistematcc.php'); // client ws sistema de tcc

require_once('reports/report_estudante_sem_atividade_postada.php');
require_once('reports/report_estudante_sem_atividade_avaliada.php');
require_once('reports/report_atividades_vs_notas.php');
require_once('reports/report_entrega_de_atividades.php');
require_once('reports/report_boletim.php');
require_once('reports/report_atividades_nota_atribuida.php');
require_once('reports/report_atividades_nao_avaliadas.php');
require_once('reports/report_potenciais_evasoes.php');
require_once('reports/report_acesso_tutor.php');
require_once('reports/report_uso_sistema_tutor.php');
require_once('reports/report_tcc_concluido.php');


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

if ($factory->get_relatorio() != null) {

    // - relatório desativado segundo o ticket #4460: 'historico_atribuicao_notas'
    $relatorios_disponiveis = array('atividades_vs_notas', 'entrega_de_atividades', 'boletim', 'atividades_nao_avaliadas',
        'estudante_sem_atividade_postada', 'estudante_sem_atividade_avaliada', 'atividades_nota_atribuida', 'potenciais_evasoes',
        'acesso_tutor', 'uso_sistema_tutor', 'tcc_portfolio', 'tcc_portfolio_concluido', 'tcc_portfolio_entrega_atividades',
        'tcc_entrega_atividades', 'tcc_consolidado', 'tcc_concluido');

    // Configurações de exibição dos relatórios
/*    switch ($factory->get_relatorio()) {
//        case 'atividades_nao_avaliadas':
//        case 'estudante_sem_atividade_postada':
//        case 'estudante_sem_atividade_avaliada':
//        case 'atividades_nota_atribuida':
//        case 'potenciais_evasoes':
        case 'tcc_portfolio':
        case 'tcc_portfolio_concluido':
        case 'tcc_portfolio_entrega_atividades':
        case 'tcc_consolidado':
        case 'tcc_concluido':
        case 'tcc_entrega_atividades':
            $factory->mostrar_botoes_grafico = false;
            break;
        case 'acesso_tutor' :
            $factory->mostrar_filtro_cohorts = false;
            $factory->mostrar_filtro_modulos = false;
            $factory->mostrar_botoes_grafico = false;
            $factory->mostrar_filtro_polos = false;
            $factory->mostrar_filtro_intervalo_tempo = true;
            break;
        case 'uso_sistema_tutor' :
            $factory->mostrar_filtro_cohorts = false;
            $factory->mostrar_botoes_dot_chart = true;
            $factory->mostrar_botoes_grafico = false;
            $factory->mostrar_filtro_modulos = false;
            $factory->mostrar_filtro_polos = false;
            $factory->mostrar_filtro_intervalo_tempo = true;
            break;
        default:
            break;
    }*/

    //
    // Renderiza os relatórios
    //

    $report = $factory->get_page_params()['relatorio'];
    $modo_exibicao = $factory->get_modo_exibicao();

    if (in_array($report, report_unasus_relatorios_validos_list())){
        $report = 'report_' . $report;
        $report = new $report();
        $report->initialize($factory);

        //Primeiro acesso ao relatório
        if($modo_exibicao == null){
            echo $report->render_report_default($renderer);
        }

        if($modo_exibicao === 'tabela'){
            $report->render_report_table($renderer, $report, $factory);

        } elseif ($modo_exibicao === 'grafico_valores' ||
                  $modo_exibicao === 'grafico_porcentagens' ||
                  $modo_exibicao === 'grafico_pontos'){

            $porcentagem = ($modo_exibicao === 'grafico_porcentagens');

            $report->render_report_graph($renderer, $report, $porcentagem, $factory);
        }
/*             switch ($factory->get_relatorio()) {

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
            }*/
        }
        print_error('unknow_report', 'report_unasus');
    }


    // Somente barra de filtragem, ou seja, tela inicial do relatório
    /*if ($factory->get_modo_exibicao() == null) {
        if (in_array($factory->get_relatorio(), $relatorios_disponiveis)) {
            echo $renderer->build_page();
        } else {
            print_error('unknow_report', 'report_unasus');
        }
    } elseif ($factory->get_modo_exibicao() === 'tabela') {
        $factory->mostrar_barra_filtragem = false;

        // Construção da tabela de dados

        $report = $factory->get_page_params()['relatorio'];

        if (in_array($report, report_unasus_relatorios_validos_list())){
            $report = 'report_' . $report;

            $report = new $report($factory);
            $report->initialize();
            $report->render_report($renderer, $report);
        }

/*        switch ($factory->get_relatorio()) {

            // - relatório desativado segundo o ticket #4460  case 'historico_atribuicao_notas':
            case 'atividades_vs_notas': OK!
            case 'entrega_de_atividades': OK!
            case 'boletim': OK!
            case 'tcc_portfolio_concluido':
            case 'tcc_portfolio_entrega_atividades':
            case 'tcc_concluido':
            case 'tcc_entrega_atividades':
                echo $renderer->build_report();
                break;
            case 'atividades_nota_atribuida': OK!
            case 'atividades_nao_avaliadas': OK!
            case 'tcc_portfolio':
            case 'tcc_consolidado':
                echo $renderer->page_atividades_nao_avaliadas($factory->get_relatorio());
                break;
            //TESTE REFATORAÇÃO
            case 'estudante_sem_atividade_postada': OK!
                $estudante_sem_atividade_postada = new report_estudante_sem_atividade_postada;
                $estudante_sem_atividade_postada->initialize();
                $estudante_sem_atividade_postada->render_report($renderer, $estudante_sem_atividade_postada);
                break;
            //---------------------------------------
            case 'estudante_sem_atividade_avaliada': OK!
                echo $renderer->page_todo_list();
                break;
            case 'potenciais_evasoes': OK!
                $factory->texto_cabecalho = 'Tutores';
                echo $renderer->build_report();
                break;
            case 'acesso_tutor':
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

            case 'uso_sistema_tutor':
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
    } elseif */


