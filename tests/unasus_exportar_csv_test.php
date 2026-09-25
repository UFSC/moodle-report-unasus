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
 * End-to-end tests of the CSV export scope, through the boletim report.
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unasus;

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Tag lib must be loaded before relationship lib: relationship_add_relationship() calls tag_set().
require_once($CFG->dirroot . '/tag/lib.php');
require_once($CFG->dirroot . '/local/relationship/lib.php');
require_once($CFG->dirroot . '/report/unasus/lib.php');
require_once($CFG->dirroot . '/report/unasus/locallib.php');
require_once($CFG->dirroot . '/report/unasus/factory.php');

/**
 * Tutor in 1 of 3 tutoring groups exports 1 group; the control without scope exports 3.
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      report_unasus
 * @covers     ::report_unasus_exportar_csv
 */
final class unasus_exportar_csv_test extends \advanced_testcase {
    /** @var stdClass */
    protected $course;
    /** @var context_course */
    protected $context;
    /** @var int[] Tutoring group ids A, B, C. */
    protected $grupos = [];
    /** @var int relationship_cohorts.id of the tutor cohort. */
    protected $rctutor;

    /**
     * Creates a course with one activity and three tutoring groups of one student each.
     */
    protected function setUp(): void {
        parent::setUp();
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();

        // Report columns are activities with completion tracking, which needs it enabled site-wide.
        set_config('enablecompletion', 1);
        set_config('local_tutores_student_roles', 'student');
        set_config('local_tutores_tutor_roles', 'teacher');
        set_config('local_tutores_orientador_roles', 'editingteacher');
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'teacher'], MUST_EXIST);

        $category = $gen->create_category();
        $catcontext = \context_coursecat::instance($category->id);
        $this->course = $gen->create_course([
            'category' => $category->id,
            'visible' => 1,
            'enablecompletion' => 1,
        ]);
        $this->context = \context_course::instance($this->course->id);
        $gen->create_module('assign', [
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        $relationshipid = relationship_add_relationship((object) [
            'contextid' => $catcontext->id,
            'name' => 'Tutoria',
            'tags' => ['grupo_tutoria'],
        ]);
        $rcestudante = relationship_add_cohort((object) [
            'relationshipid' => $relationshipid,
            'cohortid' => $gen->create_cohort(['contextid' => $catcontext->id])->id,
            'roleid' => $studentroleid,
            'allowdupsingroups' => 0,
            'uniformdistribution' => 0,
        ]);
        $this->rctutor = relationship_add_cohort((object) [
            'relationshipid' => $relationshipid,
            'cohortid' => $gen->create_cohort(['contextid' => $catcontext->id])->id,
            'roleid' => $teacherroleid,
            'allowdupsingroups' => 0,
            'uniformdistribution' => 0,
        ]);

        foreach (['A', 'B', 'C'] as $letra) {
            $grupo = relationship_add_group((object) [
                'relationshipid' => $relationshipid,
                'name' => 'Grupo ' . $letra,
                'userlimit' => 0,
                'uniformdistribution' => 0,
            ]);
            $this->grupos[] = $grupo;
            $estudante = $gen->create_user(['firstname' => 'Estudante', 'lastname' => $letra]);
            $gen->enrol_user($estudante->id, $this->course->id, $studentroleid);
            relationship_add_member($grupo, $rcestudante, $estudante->id);
        }
    }

    /**
     * Creates the current user with the given capabilities, tutor of the given groups.
     *
     * @param string[] $capabilities Capabilities allowed in the course.
     * @param int[] $grupos Tutoring group ids the user leads.
     * @return void
     */
    protected function usuario($capabilities, $grupos) {
        $shortname = 'r' . uniqid();
        $roleid = create_role($shortname, $shortname, '');
        foreach ($capabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $this->context->id, true);
        }
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, $this->context->id);
        foreach ($grupos as $grupo) {
            relationship_add_member($grupo, $this->rctutor, $user->id);
        }
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);
    }

    /**
     * Builds a fresh boletim report from request parameters, as index.php does.
     *
     * @param int[]|null $tutores Tutoring filter sent with the request.
     * @return report_unasus_factory
     */
    protected function report($tutores = null) {
        $singleton = new \ReflectionProperty('report_unasus_factory', 'report');
        $singleton->setAccessible(true);
        $singleton->setValue(null, null);
        $_GET['relatorio'] = 'boletim';
        $_GET['course'] = $this->course->id;
        if ($tutores !== null) {
            $_GET['tutores'] = $tutores;
        }
        return \report_unasus_factory::singleton();
    }

    /**
     * Counts group rows in a boletim CSV: after the two header lines, a group row has one field.
     *
     * @param string $csv CSV text.
     * @return int
     */
    protected function contar_grupos($csv) {
        $linhas = array_slice(array_filter(explode("\n", $csv), 'strlen'), 2);
        $grupos = 0;
        foreach ($linhas as $linha) {
            if (count(str_getcsv($linha)) === 1) {
                $grupos++;
            }
        }
        return $grupos;
    }

    /**
     * Runs a CSV export and returns its output.
     *
     * Under PHPUnit the output has already started, so the header() calls of the export raise
     * "headers already sent" warnings. Only those are ignored; any other error still fails the test.
     *
     * @param callable $exportar Function that writes the CSV.
     * @return string
     */
    protected function capturar_csv($exportar) {
        $anterior = null;
        $anterior = set_error_handler(function ($errno, $errstr) use (&$anterior) {
            if (strpos($errstr, 'Cannot modify header information') === 0) {
                return true;
            }
            // Anything else goes to the PHPUnit handler, so it still fails the test.
            return $anterior ? call_user_func_array($anterior, func_get_args()) : false;
        });
        ob_start();
        try {
            call_user_func($exportar);
        } catch (\Exception $e) {
            ob_end_clean();
            restore_error_handler();
            throw $e;
        }
        restore_error_handler();
        return ob_get_clean();
    }

    /**
     * Control: without the role scope the tutor gets all 3 groups (the defect being fixed).
     */
    public function test_controle_sem_escopo_exporta_os_3_grupos(): void {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report();
        $csv = $this->capturar_csv(function () use ($report) {
            $report->render_report_csv('boletim');
        });
        $this->assertEquals(3, $this->contar_grupos($csv), $csv);
    }

    /**
     * Tutor in 1 of 3 groups exports only that group.
     */
    public function test_tutor_em_1_de_3_grupos_exporta_1(): void {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report();
        $csv = $this->capturar_csv(function () use ($report) {
            report_unasus_exportar_csv($report, 'boletim');
        });
        $this->assertEquals(1, $this->contar_grupos($csv), $csv);
        $this->assertStringContainsString('Grupo A', $csv);
    }

    /**
     * Groups forged in the request do not widen the scope.
     */
    public function test_filtro_forjado_nao_amplia_o_escopo(): void {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report([$this->grupos[1], $this->grupos[2]]);
        $csv = $this->capturar_csv(function () use ($report) {
            report_unasus_exportar_csv($report, 'boletim');
        });
        $this->assertEquals(1, $this->contar_grupos($csv), $csv);
        $this->assertStringContainsString('Grupo A', $csv);
    }

    /**
     * With view_all all groups are exported.
     */
    public function test_view_all_exporta_os_3_grupos(): void {
        $this->usuario(['report/unasus:view_all', 'report/unasus:view_tutoria'], []);
        $report = $this->report();
        $csv = $this->capturar_csv(function () use ($report) {
            report_unasus_exportar_csv($report, 'boletim');
        });
        $this->assertEquals(3, $this->contar_grupos($csv), $csv);
    }

    /**
     * A tutor with no group gets an error and no CSV at all.
     */
    public function test_tutor_sem_grupo_nao_exporta_nada(): void {
        $this->usuario(['report/unasus:view_tutoria'], []);
        $report = $this->report();
        ob_start();
        try {
            report_unasus_exportar_csv($report, 'boletim');
            ob_end_clean();
            $this->fail('Expected moodle_exception csv_sem_grupo.');
        } catch (\moodle_exception $e) {
            $saida = ob_get_clean();
            $this->assertEquals('csv_sem_grupo', $e->errorcode);
            $this->assertSame('', $saida);
        }
    }
}
