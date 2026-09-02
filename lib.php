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
 * Relatorios que o usuario corrente pode ver neste curso.
 *
 * Existe para o menu do curso e o indice (index.php sem `relatorio`) nao terem duas
 * copias da mesma regra de capability -- divergindo, o menu ofereceria relatorio que o
 * indice nega, ou o contrario.
 *
 * @param stdClass $course
 * @param context_course $context
 * @return string[] nomes dos relatorios, na ordem de exibicao
 */
function report_unasus_relatorios_visiveis_list($course, $context) {

    $reports = array();

    if ($course->id == SITEID) {
        return $reports;
    }

    $tudo = has_capability('report/unasus:view_all', $context);

    //Caso usuário seja tutor
    if ($tudo || has_capability('report/unasus:view_tutoria', $context)) {
        $reports = array_merge($reports, report_unasus_relatorios_validos_tutoria_list());
    }

    //Caso usuário seja coordenador
    if ($tudo) {
        $reports = array_merge($reports, report_unasus_relatorios_restritos_list());
    }

    //Caso usuário seja orientador
    if ($tudo || has_capability('report/unasus:view_orientacao', $context)) {
        $reports = array_merge($reports, report_unasus_relatorios_validos_orientacao_list());
    }

    return $reports;
}

/**
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function report_unasus_extend_navigation_course($navigation, $course, $context) {

    $reports = report_unasus_relatorios_visiveis_list($course, $context);

    if (empty($reports)) {
        return;
    }

    // ⚠️ O NO' PRECISA DE ACTION, mesmo sendo um container com filhos.
    //
    // A pagina /report/view.php (a lista "Relatorios" do curso) renderiza o template
    // core/report_link_page, que percorre UM UNICO nivel -- os filhos diretos do no'
    // `coursereports` -- e emite cada um como <a href="{{action}}">. Sem action, este no'
    // saia de la' como <a href="">UNA-SUS</a>: um item morto, e os relatorios, que estao
    // um nivel abaixo, nao apareciam naquela tela.
    //
    // Pelo menu do curso nada disso se notava: a navegacao secundaria desce a arvore
    // inteira, e por ali os relatorios sempre estiveram acessiveis.
    $url_indice = new moodle_url('/report/unasus/index.php', array('course' => $course->id));

    $unasus_node = $navigation->add(get_string('unasus_navigation_name', 'report_unasus'),
        $url_indice, navigation_node::TYPE_CONTAINER);

    foreach ($reports as $report) {
        $url = new moodle_url('/report/unasus/index.php', array('relatorio' => $report, 'course' => $course->id));
        $unasus_node->add(get_string($report, 'report_unasus'), $url, navigation_node::TYPE_SETTING, null, $report, new pix_icon('i/report', ''));
    }
}
