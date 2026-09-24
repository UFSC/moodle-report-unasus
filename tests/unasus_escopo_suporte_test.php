<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the orientation support scope of the TCC reports (issue #20).
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unasus;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/unasus/lib.php');

/**
 * Covers report_unasus_escopo_orientacao_vazio() and report_unasus_suporte_sem_permissao().
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class unasus_escopo_suporte_test extends \advanced_testcase {
    /** @var \stdClass */
    protected $course;

    /** @var \context_course */
    protected $context;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->context = \context_course::instance($this->course->id);
    }

    /**
     * Creates a user holding a new role in the course, optionally granting capabilities to it.
     *
     * @param string $shortname Role shortname.
     * @param string[] $capabilities Capabilities allowed to the role in the course.
     * @return \stdClass The user, already set as current.
     */
    protected function usuario_com_papel($shortname, array $capabilities = []) {
        global $DB;
        $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
        if (!$roleid) {
            $roleid = create_role($shortname, $shortname, '');
            set_role_contextlevels($roleid, [CONTEXT_COURSECAT, CONTEXT_COURSE]);
        }
        foreach ($capabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $this->context->id, true);
        }
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, $this->context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);
        return $user;
    }

    /**
     * Zero groups in scope with view_orientacao is an empty scope.
     *
     * @covers ::report_unasus_escopo_orientacao_vazio
     */
    public function test_escopo_vazio_com_view_orientacao_e_zero_grupos(): void {
        $this->usuario_com_papel('suporteorientacao', ['report/unasus:view_orientacao']);
        $this->assertTrue(report_unasus_escopo_orientacao_vazio('tcc_entrega_atividades', $this->context, []));
    }

    /**
     * One group in scope is not an empty scope.
     *
     * @covers ::report_unasus_escopo_orientacao_vazio
     */
    public function test_escopo_nao_vazio_com_um_grupo(): void {
        $this->usuario_com_papel('suporteorientacao', ['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_orientacao_vazio('tcc_entrega_atividades', $this->context, [7]));
    }

    /**
     * Two groups in scope are not an empty scope.
     *
     * @covers ::report_unasus_escopo_orientacao_vazio
     */
    public function test_escopo_nao_vazio_com_dois_grupos(): void {
        $this->usuario_com_papel('suporteorientacao', ['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_orientacao_vazio('tcc_consolidado', $this->context, [7, 8]));
    }

    /**
     * No scope applied is not an empty scope.
     *
     * @covers ::report_unasus_escopo_orientacao_vazio
     */
    public function test_sem_escopo_aplicado_nao_e_vazio(): void {
        // A null scope means "no filter", which is how view_all reaches the report.
        $this->usuario_com_papel('suporteorientacao', ['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_orientacao_vazio('tcc_concluido', $this->context, null));
    }

    /**
     * A user with view_all never has an empty scope.
     *
     * @covers ::report_unasus_escopo_orientacao_vazio
     */
    public function test_view_all_nunca_e_escopo_vazio(): void {
        $this->usuario_com_papel('coordtcc', ['report/unasus:view_all', 'report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_orientacao_vazio('tcc_entrega_atividades', $this->context, []));
    }

    /**
     * A tutoring report is outside the orientation scope.
     *
     * @covers ::report_unasus_escopo_orientacao_vazio
     */
    public function test_relatorio_de_tutoria_nao_e_escopo_de_orientacao(): void {
        $this->usuario_com_papel('suporteorientacao', ['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_orientacao_vazio('boletim', $this->context, []));
    }

    /**
     * Without view_orientacao the scope is not reported as empty.
     *
     * @covers ::report_unasus_escopo_orientacao_vazio
     */
    public function test_sem_view_orientacao_nao_e_escopo_vazio(): void {
        // Missing capability is a different state, covered by report_unasus_suporte_sem_permissao().
        $this->usuario_com_papel('suporteorientacao');
        $this->assertFalse(report_unasus_escopo_orientacao_vazio('tcc_entrega_atividades', $this->context, []));
    }

    /**
     * A support role without the capability is flagged.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_papel_de_suporte_sem_capability_avisa(): void {
        set_config('local_wstcc_suporte_roles', 'suporteorientacao');
        $user = $this->usuario_com_papel('suporteorientacao');
        $this->assertTrue(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * A support role with the capability is not flagged.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_papel_de_suporte_com_capability_nao_avisa(): void {
        set_config('local_wstcc_suporte_roles', 'suporteorientacao');
        $user = $this->usuario_com_papel('suporteorientacao', ['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * No support role and no capability is not flagged.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_sem_papel_de_suporte_e_sem_capability_nao_avisa(): void {
        set_config('local_wstcc_suporte_roles', 'suporteorientacao');
        $user = $this->usuario_com_papel('estudante_qualquer');
        $this->assertFalse(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * No support role but with the capability is not flagged.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_sem_papel_de_suporte_mas_com_capability_nao_avisa(): void {
        set_config('local_wstcc_suporte_roles', 'suporteorientacao');
        $user = $this->usuario_com_papel('orientador', ['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * A support member with view_all is not flagged.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_suporte_com_view_all_nao_avisa(): void {
        set_config('local_wstcc_suporte_roles', 'suporteorientacao');
        $user = $this->usuario_com_papel('suporteorientacao', ['report/unasus:view_all']);
        $this->assertFalse(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * A blank support setting falls back to the default role.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_config_vazia_usa_o_papel_padrao(): void {
        set_config('local_wstcc_suporte_roles', '');
        $user = $this->usuario_com_papel('suporteorientacao');
        $this->assertTrue(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * A support setting with two roles recognises the second one.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_config_com_dois_papeis_reconhece_o_segundo(): void {
        set_config('local_wstcc_suporte_roles', 'outro_papel,suporte2');
        $user = $this->usuario_com_papel('suporte2');
        $this->assertTrue(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * A user with no role at all is not flagged.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_usuario_sem_papel_nenhum_nao_avisa(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->assertFalse(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }

    /**
     * A support role assigned at the category is flagged too.
     *
     * @covers ::report_unasus_suporte_sem_permissao
     */
    public function test_papel_de_suporte_atribuido_na_categoria_avisa(): void {
        global $DB;

        // The function report_unasus_suporte_sem_permissao() reads get_user_roles() with checkparentcontexts
        // set to true, so a role assigned at the course's CATEGORY context must count too, not
        // only a role assigned directly at the course context (which is all usuario_com_papel()
        // exercises elsewhere in this file).
        set_config('local_wstcc_suporte_roles', 'suporteorientacao');

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'suporteorientacao']);
        if (!$roleid) {
            $roleid = create_role('suporteorientacao', 'suporteorientacao', '');
            set_role_contextlevels($roleid, [CONTEXT_COURSECAT, CONTEXT_COURSE]);
        }

        $user = $this->getDataGenerator()->create_user();
        $categorycontext = \context_coursecat::instance($this->course->category);
        role_assign($roleid, $user->id, $categorycontext->id);
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertTrue(report_unasus_suporte_sem_permissao($this->context, $user->id));
    }
}
