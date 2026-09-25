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
 * Tests for report_unasus_escopo_vazio().
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/unasus/lib.php');

/**
 * Covers the empty-scope rule on both axes (tutoring and orientation).
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      report_unasus
 * @covers     ::report_unasus_escopo_vazio
 */
class unasus_escopo_vazio_test extends advanced_testcase {
    /** @var context_course */
    protected $context;

    /**
     * Creates a course whose context the tests use.
     */
    protected function setUp() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->context = context_course::instance($course->id);
    }

    /**
     * Creates a user holding a new role with the given capabilities and makes it current.
     *
     * @param string[] $capabilities Capabilities allowed to the role in the course.
     * @return void
     */
    protected function usuario_com($capabilities) {
        $shortname = 'r' . uniqid();
        $roleid = create_role($shortname, $shortname, '');
        foreach ($capabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $this->context->id, true);
        }
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, $this->context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);
    }

    // Tutoring axis on the boletim report: no filter, empty list, one group and several groups.

    /**
     * A null tutoring selection means no filter.
     */
    public function test_tutoria_null_nao_e_vazio() {
        $this->usuario_com(['report/unasus:view_tutoria']);
        $this->assertFalse(report_unasus_escopo_vazio('boletim', $this->context, null, null));
    }

    /**
     * An empty tutoring selection is an empty scope.
     */
    public function test_tutoria_lista_vazia_e_vazio() {
        $this->usuario_com(['report/unasus:view_tutoria']);
        $this->assertTrue(report_unasus_escopo_vazio('boletim', $this->context, [], null));
    }

    /**
     * One tutoring group is not empty.
     */
    public function test_tutoria_um_grupo_nao_e_vazio() {
        $this->usuario_com(['report/unasus:view_tutoria']);
        $this->assertFalse(report_unasus_escopo_vazio('boletim', $this->context, [7], null));
    }

    /**
     * Several tutoring groups are not empty.
     */
    public function test_tutoria_varios_grupos_nao_e_vazio() {
        $this->usuario_com(['report/unasus:view_tutoria']);
        $this->assertFalse(report_unasus_escopo_vazio('boletim', $this->context, [7, 8, 9], null));
    }

    // Orientation axis on the tcc_consolidado report: no filter, empty list, one group and several groups.

    /**
     * A null orientation selection means no filter.
     */
    public function test_orientacao_null_nao_e_vazio() {
        $this->usuario_com(['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_vazio('tcc_consolidado', $this->context, null, null));
    }

    /**
     * An empty orientation selection is an empty scope.
     */
    public function test_orientacao_lista_vazia_e_vazio() {
        $this->usuario_com(['report/unasus:view_orientacao']);
        $this->assertTrue(report_unasus_escopo_vazio('tcc_consolidado', $this->context, null, []));
    }

    /**
     * One orientation group is not empty.
     */
    public function test_orientacao_um_grupo_nao_e_vazio() {
        $this->usuario_com(['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_vazio('tcc_consolidado', $this->context, null, [7]));
    }

    /**
     * Several orientation groups are not empty.
     */
    public function test_orientacao_varios_grupos_nao_e_vazio() {
        $this->usuario_com(['report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_vazio('tcc_consolidado', $this->context, null, [7, 8, 9]));
    }

    // Guards: view_all, missing capability, report outside the axis lists.

    /**
     * With view_all the scope is never empty.
     */
    public function test_view_all_nunca_e_vazio() {
        $this->usuario_com(['report/unasus:view_all', 'report/unasus:view_tutoria',
            'report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_vazio('boletim', $this->context, [], []));
        $this->assertFalse(report_unasus_escopo_vazio('tcc_consolidado', $this->context, [], []));
    }

    /**
     * Without the axis capability the scope is not empty.
     */
    public function test_sem_capability_do_eixo_nao_e_vazio() {
        $this->usuario_com([]);
        $this->assertFalse(report_unasus_escopo_vazio('boletim', $this->context, [], []));
        $this->assertFalse(report_unasus_escopo_vazio('tcc_consolidado', $this->context, [], []));
    }

    /**
     * A restricted report belongs to neither axis.
     */
    public function test_relatorio_restrito_nao_e_vazio() {
        $this->usuario_com(['report/unasus:view_tutoria', 'report/unasus:view_orientacao']);
        $this->assertFalse(report_unasus_escopo_vazio('acesso_tutor', $this->context, [], []));
    }

    // Corners with both capabilities: (tutoring, orientation) each empty or not, per report list.

    /**
     * On a tutoring report only the tutoring axis counts.
     */
    public function test_cantos_relatorio_de_tutoria() {
        $this->usuario_com(['report/unasus:view_tutoria', 'report/unasus:view_orientacao']);
        $this->assertTrue(report_unasus_escopo_vazio('boletim', $this->context, [], []));
        $this->assertTrue(report_unasus_escopo_vazio('boletim', $this->context, [], [7]));
        $this->assertFalse(report_unasus_escopo_vazio('boletim', $this->context, [7], []));
        $this->assertFalse(report_unasus_escopo_vazio('boletim', $this->context, [7], [7]));
    }

    /**
     * On an orientation report only the orientation axis counts.
     */
    public function test_cantos_relatorio_de_orientacao() {
        $this->usuario_com(['report/unasus:view_tutoria', 'report/unasus:view_orientacao']);
        $this->assertTrue(report_unasus_escopo_vazio('tcc_consolidado', $this->context, [], []));
        $this->assertFalse(report_unasus_escopo_vazio('tcc_consolidado', $this->context, [], [7]));
        $this->assertTrue(report_unasus_escopo_vazio('tcc_consolidado', $this->context, [7], []));
        $this->assertFalse(report_unasus_escopo_vazio('tcc_consolidado', $this->context, [7], [7]));
    }
}
