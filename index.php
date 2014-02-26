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

/** @var $factory Factory */
$report = Factory::singleton();

// Usuário tem de estar logado no curso moodle
require_login($report->get_curso_moodle());

// Usuário tem de ter a permissão para ver o relatório?
require_capability('report/unasus:view', $report->get_context());

// Usuário tem permissão para ver os relatorios restritos
if (in_array($report->get_relatorio(), report_unasus_relatorios_restritos_list())) {
    require_capability('report/unasus:view_all', $report->get_context());
}

// Configurações da pagina HTML
$PAGE->set_url('/report/unasus/index.php', $report->get_page_params());
$PAGE->set_pagelayout('report');
$PAGE->requires->js_init_call('M.report_unasus.init'); // carrega arquivo module.js dentro deste módulo

/** @var $renderer report_unasus_renderer */
$renderer = $PAGE->get_renderer('report_unasus');

$name_report = $report->get_page_params()['relatorio'];
$modo_exibicao = $report->get_modo_exibicao();

$report->initialize();

//Primeiro acesso ao relatório
if($modo_exibicao == null){
    echo $report->render_report_default($renderer);
}

if($modo_exibicao === 'tabela'){
    $report->render_report_table($renderer, $report);

} elseif ($modo_exibicao === 'grafico_valores' ||
          $modo_exibicao === 'grafico_porcentagens' ||
          $modo_exibicao === 'grafico_pontos'){

    $porcentagem = ($modo_exibicao === 'grafico_porcentagens');

    $report->render_report_graph($renderer, $report, $porcentagem);
    } elseif ($modo_exibicao === 'export_csv'){

        switch($name_report){
            case 'atividades_nao_avaliadas':
                $name_report = 'avaliações_em_atraso';
                break;
            case 'atividades_nota_atribuida':
                $name_report = 'atividades_concluidas';
                break;
            case 'atividades_vs_notas':
                $name_report = 'atribuição_de_notas';
                break;
            case 'tcc_portfolio':
                $name_report = 'portfolios_consolidados';
                break;
            default: //Caso do 'Boletim', 'Acesso Tutor', 'Uso sistema tutor' e 'Potenciais Evasões' que já vem com o nome correto
                break;
        }
        $report->render_report_csv($name_report);
}






