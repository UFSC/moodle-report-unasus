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
 * Tests for report_unasus_aplicar_escopo_do_papel().
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Tag lib must be loaded before relationship lib: relationship_add_relationship() calls tag_set().
require_once($CFG->dirroot . '/tag/lib.php');
require_once($CFG->dirroot . '/local/relationship/lib.php');
require_once($CFG->dirroot . '/report/unasus/lib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php');

/**
 * Minimal report double: what the role scope reads and writes.
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_unasus_escopo_report_stub {
    /** @var int[]|null Tutoring groups selected. */
    public $tutores_selecionados = null;
    /** @var int[]|null Orientation groups selected. */
    public $orientadores_selecionados = null;
    /** @var context Report context. */
    protected $context;
    /** @var int Class category id. */
    protected $categoriaturma;

    /**
     * Constructor.
     *
     * @param context $context Report context.
     * @param int $categoriaturma Class category id.
     */
    public function __construct($context, $categoriaturma) {
        $this->context = $context;
        $this->categoriaturma = $categoriaturma;
    }

    /**
     * Returns the report context.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Returns the class category id.
     *
     * @return int
     */
    public function get_categoria_turma_ufsc() {
        return $this->categoriaturma;
    }
}

/**
 * Covers the role scope on both axes, with 0 to 3 groups for the current user.
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      report_unasus
 */
class report_unasus_aplicar_escopo_testcase extends advanced_testcase {
    /** @var context_course */
    protected $context;
    /** @var int */
    protected $categoriaturma;
    /** @var int[] Tutoring group ids, three of them. */
    protected $grupostutoria = [];
    /** @var int[] Orientation group ids, three of them. */
    protected $gruposorientacao = [];
    /** @var int relationship_cohorts.id of the tutor cohort. */
    protected $rctutor;
    /** @var int relationship_cohorts.id of the advisor cohort. */
    protected $rcorientador;

    /**
     * Creates a class category with one tutoring and one orientation relationship of 3 groups each.
     */
    protected function setUp() {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();

        set_config('local_tutores_student_roles', 'student');
        set_config('local_tutores_tutor_roles', 'teacher');
        set_config('local_tutores_orientador_roles', 'editingteacher');

        $category = $gen->create_category();
        $this->categoriaturma = $category->id;
        $catcontext = context_coursecat::instance($category->id);
        $course = $gen->create_course(['category' => $category->id]);
        $this->context = context_course::instance($course->id);

        $this->rctutor = $this->criar_relationship($catcontext->id, 'grupo_tutoria', 'teacher',
            'Tutoria', $this->grupostutoria);
        $this->rcorientador = $this->criar_relationship($catcontext->id, 'grupo_orientacao',
            'editingteacher', 'Orientacao', $this->gruposorientacao);
    }

    /**
     * Creates a tagged relationship with one cohort for the given role and three groups.
     *
     * @param int $contextid Category context id.
     * @param string $tag Relationship tag.
     * @param string $roleshortname Role of the cohort.
     * @param string $prefixo Group name prefix.
     * @param int[] $grupos Receives the three group ids.
     * @return int relationship_cohorts.id
     */
    protected function criar_relationship($contextid, $tag, $roleshortname, $prefixo, &$grupos) {
        global $DB;
        $gen = $this->getDataGenerator();
        $relationshipid = relationship_add_relationship((object) [
            'contextid' => $contextid,
            'name' => $prefixo,
            'tags' => [$tag],
        ]);
        $cohort = $gen->create_cohort(['contextid' => $contextid]);
        $rc = relationship_add_cohort((object) [
            'relationshipid' => $relationshipid,
            'cohortid' => $cohort->id,
            'roleid' => $DB->get_field('role', 'id', ['shortname' => $roleshortname], MUST_EXIST),
            'allowdupsingroups' => 1,
            'uniformdistribution' => 0,
        ]);
        foreach (['A', 'B', 'C'] as $letra) {
            $grupos[] = relationship_add_group((object) [
                'relationshipid' => $relationshipid,
                'name' => $prefixo . ' ' . $letra,
                'userlimit' => 0,
                'uniformdistribution' => 0,
            ]);
        }
        return $rc;
    }

    /**
     * Creates the current user with the given capabilities, member of the first groups of each axis.
     *
     * @param string[] $capabilities Capabilities allowed in the course.
     * @param int $ntutoria How many tutoring groups the user is in.
     * @param int $norientacao How many orientation groups the user is in.
     * @return void
     */
    protected function usuario($capabilities, $ntutoria, $norientacao) {
        $shortname = 'r' . uniqid();
        $roleid = create_role($shortname, $shortname, '');
        foreach ($capabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $this->context->id, true);
        }
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, $this->context->id);
        for ($i = 0; $i < $ntutoria; $i++) {
            relationship_add_member($this->grupostutoria[$i], $this->rctutor, $user->id);
        }
        for ($i = 0; $i < $norientacao; $i++) {
            relationship_add_member($this->gruposorientacao[$i], $this->rcorientador, $user->id);
        }
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);
    }

    /**
     * Returns a fresh report double for the test course.
     *
     * @return report_unasus_escopo_report_stub
     */
    protected function report() {
        return new report_unasus_escopo_report_stub($this->context, $this->categoriaturma);
    }

    /**
     * Tutor in 0, 1, 2 and 3 of the 3 tutoring groups gets exactly those groups.
     */
    public function test_tutoria_de_0_a_3_grupos() {
        foreach ([0, 1, 2, 3] as $n) {
            $this->usuario(['report/unasus:view_tutoria'], $n, 0);
            $report = $this->report();
            report_unasus_aplicar_escopo_do_papel($report);
            $esperado = array_slice($this->grupostutoria, 0, $n);
            $obtido = $report->tutores_selecionados;
            sort($obtido);
            sort($esperado);
            $this->assertEquals($esperado, $obtido, "tutor em $n grupos");
            $this->assertNull($report->orientadores_selecionados, "tutor em $n grupos, eixo orientacao");
        }
    }

    /**
     * Advisor in 0, 1, 2 and 3 of the 3 orientation groups gets exactly those groups.
     */
    public function test_orientacao_de_0_a_3_grupos() {
        foreach ([0, 1, 2, 3] as $n) {
            $this->usuario(['report/unasus:view_orientacao'], 0, $n);
            $report = $this->report();
            report_unasus_aplicar_escopo_do_papel($report);
            $esperado = array_slice($this->gruposorientacao, 0, $n);
            $obtido = $report->orientadores_selecionados;
            sort($obtido);
            sort($esperado);
            $this->assertEquals($esperado, $obtido, "orientador em $n grupos");
            $this->assertNull($report->tutores_selecionados, "orientador em $n grupos, eixo tutoria");
        }
    }

    /**
     * A group forged in the request is replaced by the user's own groups.
     */
    public function test_filtro_forjado_e_substituido() {
        $this->usuario(['report/unasus:view_tutoria'], 1, 0);
        $report = $this->report();
        $report->tutores_selecionados = [$this->grupostutoria[2]];
        report_unasus_aplicar_escopo_do_papel($report);
        $this->assertEquals([$this->grupostutoria[0]], array_values($report->tutores_selecionados));
    }

    /**
     * With view_all the request filter is left untouched on both axes.
     */
    public function test_view_all_mantem_o_filtro() {
        $this->usuario(['report/unasus:view_all', 'report/unasus:view_tutoria',
            'report/unasus:view_orientacao'], 1, 1);
        $report = $this->report();
        $report->tutores_selecionados = [$this->grupostutoria[2]];
        $report->orientadores_selecionados = null;
        report_unasus_aplicar_escopo_do_papel($report);
        $this->assertEquals([$this->grupostutoria[2]], $report->tutores_selecionados);
        $this->assertNull($report->orientadores_selecionados);
    }

    /**
     * Without any report capability nothing is changed.
     */
    public function test_sem_capability_nao_muda_nada() {
        $this->usuario([], 1, 1);
        $report = $this->report();
        report_unasus_aplicar_escopo_do_papel($report);
        $this->assertNull($report->tutores_selecionados);
        $this->assertNull($report->orientadores_selecionados);
    }
}
