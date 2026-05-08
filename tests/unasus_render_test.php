<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/report/unasus/datastructures.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php');

/**
 * Pinning das classes render — transições internas (estado → CSS / __toString).
 *
 * As render classes são puras: não acessam DB exceto via getters de config
 * (`report_unasus_get_prazo_maximo_avaliacao`, `report_unasus_get_prazo_maximo_entrega`,
 * `report_unasus_get_passing_grade_percentage`). Esses são fixados por teste com
 * `set_config`.
 *
 * @group report_unasus
 */
class unasus_render_testcase extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest(true);
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_atividades_vs_notas_render (8 estados)
    // -----------------------------------------------------------------------

    public function test_atividades_vs_notas_nao_entregue() {
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_NAO_ENTREGUE, 1);
        $this->assertEquals('não entregue', (string) $r);
        $this->assertEquals('nao_entregue', $r->get_css_class());
    }

    public function test_atividades_vs_notas_correcao_atrasada_pouco() {
        // atraso <= prazo_maximo_avaliacao → pouco_atraso
        set_config('report_unasus_prazo_maximo_avaliacao', 10);
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::CORRECAO_ATRASADA, 1, 0, 5);

        $this->assertEquals('5 dias', (string) $r);
        $this->assertEquals('pouco_atraso', $r->get_css_class());
    }

    public function test_atividades_vs_notas_correcao_atrasada_muito() {
        // atraso > prazo_maximo_avaliacao → muito_atraso (boundary CSS class)
        set_config('report_unasus_prazo_maximo_avaliacao', 5);
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::CORRECAO_ATRASADA, 1, 0, 10);

        $this->assertEquals('10 dias', (string) $r);
        $this->assertEquals('muito_atraso', $r->get_css_class());
    }

    public function test_atividades_vs_notas_avaliada_sem_atraso() {
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_AVALIADA_SEM_ATRASO, 1, 8.5);

        $this->assertEquals('nota_atribuida', $r->get_css_class());
        // format_grade aplica format_float($v, 1) — usa locale; verificamos só o conteúdo numérico
        $this->assertContains('8', (string) $r);
    }

    public function test_atividades_vs_notas_no_prazo_entrega() {
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_NO_PRAZO_ENTREGA, 1);
        $this->assertEquals('no prazo', (string) $r);
        $this->assertEquals('nao_realizada', $r->get_css_class());
    }

    public function test_atividades_vs_notas_sem_prazo_entrega() {
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_SEM_PRAZO_ENTREGA, 1);
        $this->assertEquals('sem prazo', (string) $r);
        $this->assertEquals('sem_prazo', $r->get_css_class());
    }

    public function test_atividades_vs_notas_avaliada_com_atraso() {
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_AVALIADA_COM_ATRASO, 1, 7.0);

        $this->assertEquals('nota_atribuida_atraso', $r->get_css_class());
        $this->assertContains('7', (string) $r);
    }

    public function test_atividades_vs_notas_entregue_nao_avaliada() {
        // sem atraso → 'sem nota'
        $r1 = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_ENTEGUE_NAO_AVALIADA, 1, 0, 0);
        $this->assertEquals('sem nota', (string) $r1);
        $this->assertEquals('avaliado_sem_nota', $r1->get_css_class());

        // com atraso > 0 → mostra dias
        $r2 = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_ENTEGUE_NAO_AVALIADA, 1, 0, 3);
        $this->assertEquals('3 dias', (string) $r2);
    }

    public function test_atividades_vs_notas_nao_aplicado() {
        $r = new report_unasus_dado_atividades_vs_notas_render(
            report_unasus_dado_atividades_vs_notas_render::ATIVIDADE_NAO_APLICADO, 1);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_aplicado', $r->get_css_class());
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_boletim_render (3 estados + boundary)
    // -----------------------------------------------------------------------

    public function test_boletim_render_com_nota_acima_media() {
        set_config('report_unasus_passing_grade_percentage', 60);
        $r = new report_unasus_dado_boletim_render(
            report_unasus_dado_boletim_render::ATIVIDADE_COM_NOTA, 1, 8.0, 10);

        // 8/10 = 80% >= 60% → na_media
        $this->assertEquals('na_media', $r->get_css_class());
        $this->assertContains('8', (string) $r);
    }

    public function test_boletim_render_com_nota_abaixo_media() {
        set_config('report_unasus_passing_grade_percentage', 60);
        $r = new report_unasus_dado_boletim_render(
            report_unasus_dado_boletim_render::ATIVIDADE_COM_NOTA, 1, 5.0, 10);

        // 5/10 = 50% < 60% → abaixo_media_nota
        $this->assertEquals('abaixo_media_nota', $r->get_css_class());
        $this->assertContains('5', (string) $r);
    }

    public function test_boletim_render_sem_nota() {
        $r = new report_unasus_dado_boletim_render(
            report_unasus_dado_boletim_render::ATIVIDADE_SEM_NOTA, 1);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('sem_nota', $r->get_css_class());
    }

    public function test_boletim_render_nao_aplicado() {
        $r = new report_unasus_dado_boletim_render(
            report_unasus_dado_boletim_render::ATIVIDADE_NAO_APLICADO, 1);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_aplicado', $r->get_css_class());
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_nota_final_render (3 estados)
    // -----------------------------------------------------------------------

    public function test_nota_final_render_com_nota_acima_media() {
        set_config('report_unasus_passing_grade_percentage', 60);
        $r = new report_unasus_dado_nota_final_render(
            report_unasus_dado_nota_final_render::ATIVIDADE_COM_NOTA, 7.0, 10);

        $this->assertEquals('na_media', $r->get_css_class());
        $this->assertContains('7', (string) $r);
    }

    public function test_nota_final_render_com_nota_abaixo_media() {
        set_config('report_unasus_passing_grade_percentage', 60);
        $r = new report_unasus_dado_nota_final_render(
            report_unasus_dado_nota_final_render::ATIVIDADE_COM_NOTA, 4.0, 10);

        // Nota final usa CSS 'abaixo_media' (sem sufixo _nota)
        $this->assertEquals('abaixo_media', $r->get_css_class());
    }

    public function test_nota_final_render_sem_nota() {
        $r = new report_unasus_dado_nota_final_render(
            report_unasus_dado_nota_final_render::ATIVIDADE_SEM_NOTA);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('sem_nota', $r->get_css_class());
    }

    public function test_nota_final_render_nao_aplicado() {
        $r = new report_unasus_dado_nota_final_render(
            report_unasus_dado_nota_final_render::ATIVIDADE_NAO_APLICADO);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_aplicado', $r->get_css_class());
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_entrega_de_atividades_render (6 estados + boundary)
    // -----------------------------------------------------------------------

    public function test_entrega_atividades_nao_entregue_fora_prazo() {
        $r = new report_unasus_dado_entrega_de_atividades_render(
            report_unasus_dado_entrega_de_atividades_render::ATIVIDADE_NAO_ENTREGUE_FORA_DO_PRAZO, 1);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_entregue_fora_do_prazo', $r->get_css_class());
    }

    public function test_entrega_atividades_entregue_no_prazo() {
        $r = new report_unasus_dado_entrega_de_atividades_render(
            report_unasus_dado_entrega_de_atividades_render::ATIVIDADE_ENTREGUE_NO_PRAZO, 1);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('no_prazo', $r->get_css_class());
    }

    public function test_entrega_atividades_entregue_fora_prazo_pouco() {
        // atraso <= prazo_maximo_entrega → pouco_atraso
        set_config('report_unasus_prazo_maximo_entrega', 10);
        $r = new report_unasus_dado_entrega_de_atividades_render(
            report_unasus_dado_entrega_de_atividades_render::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO, 1, 5);

        $this->assertEquals('5 dias', (string) $r);
        $this->assertEquals('pouco_atraso', $r->get_css_class());
    }

    public function test_entrega_atividades_entregue_fora_prazo_muito() {
        set_config('report_unasus_prazo_maximo_entrega', 5);
        $r = new report_unasus_dado_entrega_de_atividades_render(
            report_unasus_dado_entrega_de_atividades_render::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO, 1, 10);

        $this->assertEquals('10 dias', (string) $r);
        $this->assertEquals('muito_atraso', $r->get_css_class());
    }

    public function test_entrega_atividades_sem_prazo() {
        $r = new report_unasus_dado_entrega_de_atividades_render(
            report_unasus_dado_entrega_de_atividades_render::ATIVIDADE_SEM_PRAZO_ENTREGA, 1);
        $this->assertEquals('sem prazo', (string) $r);
        $this->assertEquals('sem_prazo', $r->get_css_class());
    }

    public function test_entrega_atividades_nao_entregue_mas_no_prazo() {
        $r = new report_unasus_dado_entrega_de_atividades_render(
            report_unasus_dado_entrega_de_atividades_render::ATIVIDADE_NAO_ENTREGUE_MAS_NO_PRAZO, 1);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_entregue_mas_no_prazo', $r->get_css_class());
    }

    public function test_entrega_atividades_nao_aplicado() {
        $r = new report_unasus_dado_entrega_de_atividades_render(
            report_unasus_dado_entrega_de_atividades_render::ATIVIDADE_NAO_APLICADO, 1);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_aplicado', $r->get_css_class());
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_tcc_entrega_atividades_render (4 estados)
    // -----------------------------------------------------------------------

    public function test_tcc_entrega_rascunho_hoje() {
        $r = new report_unasus_dado_tcc_entrega_atividades_render(
            report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_RASCUNHO, 'cap1', 0);
        $this->assertContains('Hoje', (string) $r);
        $this->assertEquals('rascunho', $r->get_css_class());
    }

    public function test_tcc_entrega_rascunho_n_dias() {
        $r = new report_unasus_dado_tcc_entrega_atividades_render(
            report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_RASCUNHO, 'cap1', 5);
        $this->assertContains('5', (string) $r);
        $this->assertContains('dias', (string) $r);
    }

    public function test_tcc_entrega_revisao() {
        $r = new report_unasus_dado_tcc_entrega_atividades_render(
            report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_REVISAO, 'cap1', 1);
        $this->assertContains('1', (string) $r);
        $this->assertContains(' dia', (string) $r);
        $this->assertEquals('revisao', $r->get_css_class());
    }

    public function test_tcc_entrega_avaliado() {
        $r = new report_unasus_dado_tcc_entrega_atividades_render(
            report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_AVALIADO, 'cap1');
        $this->assertEquals('', (string) $r);
        $this->assertEquals('avaliado', $r->get_css_class());
    }

    public function test_tcc_entrega_nao_aplicado() {
        $r = new report_unasus_dado_tcc_entrega_atividades_render(
            report_unasus_dado_tcc_entrega_atividades_render::ATIVIDADE_NAO_APLICADO, 'cap1');
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_aplicado', $r->get_css_class());
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_tcc_concluido_render (2 estados)
    // -----------------------------------------------------------------------

    public function test_tcc_concluido_nao_concluida() {
        $r = new report_unasus_dado_tcc_concluido_render(
            report_unasus_dado_tcc_concluido_render::ATIVIDADE_NAO_CONCLUIDA, 'cap1');
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_concluido', $r->get_css_class());
    }

    public function test_tcc_concluido_concluida() {
        $r = new report_unasus_dado_tcc_concluido_render(
            report_unasus_dado_tcc_concluido_render::ATIVIDADE_CONCLUIDA, 'cap1');
        $this->assertEquals('', (string) $r);
        $this->assertEquals('concluido', $r->get_css_class());
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_historico_atribuicao_notas_render (5 estados)
    // -----------------------------------------------------------------------

    public function test_historico_nao_entregue() {
        $r = new report_unasus_dado_historico_atribuicao_notas_render(
            report_unasus_dado_historico_atribuicao_notas_render::ATIVIDADE_NAO_ENTREGUE, 1, 0);
        $this->assertEquals('', (string) $r);
        $this->assertEquals('nao_entregue', $r->get_css_class());
    }

    public function test_historico_correcao_no_prazo() {
        $r = new report_unasus_dado_historico_atribuicao_notas_render(
            report_unasus_dado_historico_atribuicao_notas_render::CORRECAO_NO_PRAZO, 1, 2);
        $this->assertEquals('2 dias', (string) $r);
        $this->assertEquals('no_prazo', $r->get_css_class());
    }

    public function test_historico_correcao_pouco_atraso() {
        $r = new report_unasus_dado_historico_atribuicao_notas_render(
            report_unasus_dado_historico_atribuicao_notas_render::CORRECAO_POUCO_ATRASO, 1, 3);
        $this->assertEquals('3 dias', (string) $r);
        $this->assertEquals('pouco_atraso', $r->get_css_class());
    }

    public function test_historico_correcao_muito_atraso() {
        $r = new report_unasus_dado_historico_atribuicao_notas_render(
            report_unasus_dado_historico_atribuicao_notas_render::CORRECAO_MUITO_ATRASO, 1, 15);
        $this->assertEquals('15 dias', (string) $r);
        $this->assertEquals('muito_atraso', $r->get_css_class());
    }

    public function test_historico_atividade_entregue_nao_avaliada() {
        $r = new report_unasus_dado_historico_atribuicao_notas_render(
            report_unasus_dado_historico_atribuicao_notas_render::ATIVIDADE_ENTREGUE_NAO_AVALIADA, 1, 1);
        $this->assertEquals('1 dia', (string) $r);
        $this->assertEquals('nao_avaliada', $r->get_css_class());
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_modulos_concluidos_render (3 estados)
    // -----------------------------------------------------------------------

    public function test_modulos_concluidos_concluido() {
        // Sem atividades pendentes e is_student=1 → estado 1 (concluído)
        $r = new report_unasus_dado_modulos_concluidos_render(5, 'A', 1);
        $this->assertEquals(1, $r->get_state());
        $this->assertEquals('concluido', $r->get_css_class());
        $this->assertContains('A', (string) $r);
    }

    public function test_modulos_concluidos_concluido_sem_nota() {
        // final_grade '-' → "Sem Nota"
        $r = new report_unasus_dado_modulos_concluidos_render(5, '-', 1);
        $this->assertContains('Sem Nota', (string) $r);
    }

    public function test_modulos_concluidos_nao_concluido() {
        // Tem atividade pendente → estado 0
        $r = new report_unasus_dado_modulos_concluidos_render(5, 'A', 1);
        $r->add_atividade_pendente('atividade_x');

        $this->assertEquals(0, $r->get_state());
        $this->assertEquals('nao_concluido', $r->get_css_class());
        $this->assertContains('atividade_x', (string) $r);
    }

    public function test_modulos_concluidos_nao_aplicado() {
        // is_student == 0 → estado 2 (não aplicado), independente de pendências
        $r = new report_unasus_dado_modulos_concluidos_render(5, 'A', 0);
        $this->assertEquals(2, $r->get_state());
        $this->assertEquals('nao_aplicado', $r->get_css_class());
        $this->assertEquals('', (string) $r);
    }

    // -----------------------------------------------------------------------
    // report_unasus_dado_potenciais_evasoes_render — transições internas
    // -----------------------------------------------------------------------

    public function test_potenciais_evasoes_inicia_concluido() {
        // Construtor inicia em MODULO_CONCLUIDO
        $r = new report_unasus_dado_potenciais_evasoes_render(3);
        $this->assertEquals('Sim', (string) $r);
        $this->assertEquals('concluido', $r->get_css_class());
    }

    public function test_potenciais_evasoes_transicao_para_parcial() {
        // 1 atividade não realizada → MODULO_PARCIALMENTE_CONCLUIDO
        $r = new report_unasus_dado_potenciais_evasoes_render(3);
        $r->add_atividade_nao_realizada();

        $this->assertEquals('Parcial', (string) $r);
        $this->assertEquals('parcial', $r->get_css_class());
        $this->assertEquals(1, $r->get_total_atividades_nao_realizadas());
    }

    public function test_potenciais_evasoes_transicao_para_nao_concluido() {
        // todas atividades não realizadas → MODULO_NAO_CONCLUIDO
        $r = new report_unasus_dado_potenciais_evasoes_render(2);
        $r->add_atividade_nao_realizada();
        $r->add_atividade_nao_realizada();

        $this->assertEquals('Não', (string) $r);
        $this->assertEquals('nao_concluido', $r->get_css_class());
    }
}
