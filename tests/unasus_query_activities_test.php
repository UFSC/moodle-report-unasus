<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/report/unasus/locallib.php');

/**
 * Cobre report_unasus_query_activities_ordered_courses (locallib.php).
 *
 * A consulta liga {course_modules} a {course_sections} por
 * FIND_IN_SET(cm.id, cs.sequence). O `sequence` e' texto livre mantido pelo
 * core: quem garante que ele so' cita modulos do proprio curso e' o codigo que
 * move atividades, nao o banco. Restauracao, importacao ou uma movimentacao
 * interrompida deixam la' um cmid que pertence a outro curso -- e ai' a
 * atividade alheia entra no relatorio.
 *
 * @group report_unasus
 */
class unasus_query_activities_test extends advanced_testcase {

    /**
     * Cria um curso com conclusao habilitada e uma atividade que a usa.
     *
     * @param string $nome
     * @return array [stdClass $curso, stdClass $modulo]
     */
    private function curso_com_atividade($nome) {
        $curso = $this->getDataGenerator()->create_course(
            array('fullname' => $nome, 'enablecompletion' => 1));

        $modulo = $this->getDataGenerator()->create_module('assign', array(
            'course' => $curso->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ));

        return array($curso, $modulo);
    }

    /**
     * Devolve os coursemoduleid que a consulta retorna para os cursos dados.
     *
     * @param array $cursos ids de curso
     * @return array lista de coursemoduleid
     */
    private function cmids_retornados($cursos) {
        $ids = array();
        $rs = report_unasus_query_activities_ordered_courses($cursos);
        foreach ($rs as $registro) {
            $ids[] = (int)$registro->coursemoduleid;
        }
        $rs->close();
        return $ids;
    }

    public function test_atividade_do_proprio_curso_aparece() {
        $this->resetAfterTest(true);

        list($curso, $modulo) = $this->curso_com_atividade('Curso A');

        // Caso normal: sem ele, um filtro que rejeitasse tudo tambem passaria
        // no teste de vazamento abaixo.
        $this->assertContains((int)$modulo->cmid, $this->cmids_retornados(array($curso->id)));
    }

    public function test_atividade_de_outro_curso_citada_na_sequence_nao_vaza() {
        global $DB;
        $this->resetAfterTest(true);

        list($curso_a, $modulo_a) = $this->curso_com_atividade('Curso A');
        list($curso_b, $modulo_b) = $this->curso_com_atividade('Curso B');

        // Suja a sequence do curso B com o cmid do curso A, que e' o estado que
        // sobra de uma movimentacao mal terminada.
        $secao_b = $DB->get_record('course_sections',
            array('course' => $curso_b->id, 'section' => 0), '*', MUST_EXIST);
        $DB->set_field('course_sections', 'sequence',
            $secao_b->sequence . ',' . $modulo_a->cmid, array('id' => $secao_b->id));

        $retornados = $this->cmids_retornados(array($curso_b->id));

        $this->assertContains((int)$modulo_b->cmid, $retornados,
            'A atividade do proprio curso B deve continuar aparecendo.');
        $this->assertNotContains((int)$modulo_a->cmid, $retornados,
            'A atividade do curso A nao pertence ao curso B e nao pode entrar no relatorio dele.');
    }

    public function test_atividade_citada_em_duas_secoes_do_mesmo_curso_aparece_uma_vez() {
        global $DB;
        $this->resetAfterTest(true);

        $curso = $this->getDataGenerator()->create_course(
            array('enablecompletion' => 1, 'numsections' => 2));
        $modulo = $this->getDataGenerator()->create_module('assign', array(
            'course' => $curso->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ));

        // O mesmo cmid citado tambem na secao 1, sem sair da secao 0 -- o rastro
        // de uma movimentacao entre secoes que nao terminou de limpar a origem.
        $secao_1 = $DB->get_record('course_sections',
            array('course' => $curso->id, 'section' => 1), '*', MUST_EXIST);
        $DB->set_field('course_sections', 'sequence', (string)$modulo->cmid,
            array('id' => $secao_1->id));

        $ocorrencias = array_count_values($this->cmids_retornados(array($curso->id)));

        $this->assertSame(1, $ocorrencias[(int)$modulo->cmid],
            'Uma atividade citada em duas secoes do proprio curso rende duas linhas, ' .
            'e cada linha vira um bloco de colunas repetido no relatorio.');
    }

    public function test_ordem_de_apresentacao_segue_a_sequence_da_secao() {
        global $DB;
        $this->resetAfterTest(true);

        // Prende a ordenacao, que sai de FIND_IN_SET(cm.id, cs.sequence) como
        // `module_order`. Sem este teste, um conserto na juncao poderia trocar a
        // ordem de apresentacao sem que nada ficasse vermelho.
        $curso = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $primeiro = $this->getDataGenerator()->create_module('assign', array(
            'course' => $curso->id, 'completion' => COMPLETION_TRACKING_MANUAL));
        $segundo = $this->getDataGenerator()->create_module('assign', array(
            'course' => $curso->id, 'completion' => COMPLETION_TRACKING_MANUAL));

        // Inverte a ordem declarada na sequence da secao 0.
        $secao_0 = $DB->get_record('course_sections',
            array('course' => $curso->id, 'section' => 0), '*', MUST_EXIST);
        $DB->set_field('course_sections', 'sequence',
            $segundo->cmid . ',' . $primeiro->cmid, array('id' => $secao_0->id));

        $this->assertSame(
            array((int)$segundo->cmid, (int)$primeiro->cmid),
            $this->cmids_retornados(array($curso->id)),
            'A ordem tem de seguir a sequence da secao, nao o id do modulo.');
    }
}
