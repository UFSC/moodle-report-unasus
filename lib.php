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

/**
 * Tells whether the advisor scope of the current user left no orientation group to show.
 *
 * @param string $relatorio Report name.
 * @param context $context Report context.
 * @param int[]|null $orientadoresselecionados Orientation groups after the role scope; null means no filter.
 * @return bool
 * @package report_unasus
 */
function report_unasus_escopo_orientacao_vazio($relatorio, $context, $orientadoresselecionados) {
    if (!in_array($relatorio, report_unasus_relatorios_validos_orientacao_list())) {
        return false;
    }
    if (has_capability('report/unasus:view_all', $context)) {
        return false;
    }
    if (!has_capability('report/unasus:view_orientacao', $context)) {
        return false;
    }
    return is_array($orientadoresselecionados) && empty($orientadoresselecionados);
}

/**
 * Tells whether the user holds an orientation support role but cannot open the TCC reports.
 *
 * Without report/unasus:view_orientacao the TCC reports are hidden from the menu with no
 * explanation. This is what lets the page tell the user what to ask for (issue #20).
 *
 * @param context $context Course context.
 * @param int $userid Id of the user to check.
 * @return bool
 * @package report_unasus
 */
function report_unasus_suporte_sem_permissao($context, $userid) {
    global $CFG;

    if (
        has_capability('report/unasus:view_all', $context, $userid) ||
        has_capability('report/unasus:view_orientacao', $context, $userid)
    ) {
        return false;
    }

    require_once($CFG->dirroot . '/local/tutores/lib.php');

    $shortnames = local_tutores_grupo_orientacao::get_papeis_suporte();
    foreach (get_user_roles($context, $userid, true) as $role) {
        if (in_array($role->shortname, $shortnames)) {
            return true;
        }
    }
    return false;
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

    global $USER;

    $reports = report_unasus_relatorios_visiveis_list($course, $context);

    // A support member without the capability still gets the entry: it leads to the page that
    // explains what is missing, instead of the reports just not being there. The site front
    // page is not a class course, so the role lookup is skipped there.
    if (empty($reports)) {
        if ($course->id == SITEID || !report_unasus_suporte_sem_permissao($context, $USER->id)) {
            return;
        }
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
