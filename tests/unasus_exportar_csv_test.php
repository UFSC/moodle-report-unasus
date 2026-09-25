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
class unasus_exportar_csv_test extends advanced_testcase {
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
    protected function setUp() {
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
        $catcontext = context_coursecat::instance($category->id);
        $this->course = $gen->create_course([
            'category' => $category->id,
            'visible' => 1,
            'enablecompletion' => 1,
        ]);
        $this->context = context_course::instance($this->course->id);
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
     * Leaves no report in the factory singleton for the next test classes.
     */
    protected function tearDown() {
        $this->limpar_singleton();
        parent::tearDown();
    }

    /**
     * Drops the report kept by the factory singleton.
     *
     * @return void
     */
    protected function limpar_singleton() {
        $singleton = new ReflectionProperty('report_unasus_factory', 'report');
        $singleton->setAccessible(true);
        $singleton->setValue(null, null);
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
     * Builds a fresh report from request parameters, as index.php does.
     *
     * @param int[]|null $tutores Tutoring filter sent with the request.
     * @param string $relatorio Report name.
     * @return report_unasus_factory
     */
    protected function report($tutores = null, $relatorio = 'boletim') {
        $this->limpar_singleton();
        $_GET['relatorio'] = $relatorio;
        $_GET['course'] = $this->course->id;
        if ($tutores !== null) {
            $_GET['tutores'] = $tutores;
        }
        return report_unasus_factory::singleton();
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
        $saida = '';
        try {
            call_user_func($exportar);
        } finally {
            // Also on PHP 7 Errors, so no buffer or handler leaks into the next tests.
            $saida = ob_get_clean();
            restore_error_handler();
        }
        return $saida;
    }

    /**
     * Control: without the role scope the tutor gets all 3 groups (the defect being fixed).
     */
    public function test_controle_sem_escopo_exporta_os_3_grupos() {
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
    public function test_tutor_em_1_de_3_grupos_exporta_1() {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report();
        $csv = $this->capturar_csv(function () use ($report) {
            report_unasus_exportar_csv($report, 'boletim');
        });
        $this->assertEquals(1, $this->contar_grupos($csv), $csv);
        $this->assertContains('Grupo A', $csv);
    }

    /**
     * Groups forged in the request do not widen the scope.
     */
    public function test_filtro_forjado_nao_amplia_o_escopo() {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report([$this->grupos[1], $this->grupos[2]]);
        $csv = $this->capturar_csv(function () use ($report) {
            report_unasus_exportar_csv($report, 'boletim');
        });
        $this->assertEquals(1, $this->contar_grupos($csv), $csv);
        $this->assertContains('Grupo A', $csv);
    }

    /**
     * With view_all all groups are exported.
     */
    public function test_view_all_exporta_os_3_grupos() {
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
    public function test_tutor_sem_grupo_nao_exporta_nada() {
        $this->usuario(['report/unasus:view_tutoria'], []);
        $report = $this->report();
        $erro = null;
        ob_start();
        try {
            report_unasus_exportar_csv($report, 'boletim');
        } catch (moodle_exception $e) {
            $erro = $e;
        } finally {
            $saida = ob_get_clean();
        }
        $this->assertNotNull($erro, 'Expected moodle_exception csv_sem_grupo.');
        $this->assertEquals('csv_sem_grupo', $erro->errorcode);
        $this->assertSame('', $saida);
    }

    /**
     * Graph control: without the role scope the tutor gets all 3 groups (the defect being fixed).
     *
     * @covers ::report_unasus_dados_grafico
     */
    public function test_grafico_controle_sem_escopo_traz_os_3_grupos() {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report(null, 'atividades_vs_notas');
        $this->assertCount(3, $report->get_dados_grafico());
    }

    /**
     * Tutor in 1 of 3 groups gets graph data for that group only.
     *
     * @covers ::report_unasus_dados_grafico
     */
    public function test_grafico_tutor_em_1_de_3_grupos_traz_1() {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report(null, 'atividades_vs_notas');
        $this->assertCount(1, report_unasus_dados_grafico($report));
    }

    /**
     * Groups forged in the request do not widen the graph scope.
     *
     * @covers ::report_unasus_dados_grafico
     */
    public function test_grafico_filtro_forjado_nao_amplia_o_escopo() {
        $this->usuario(['report/unasus:view_tutoria'], [$this->grupos[0]]);
        $report = $this->report([$this->grupos[1], $this->grupos[2]], 'atividades_vs_notas');
        $this->assertCount(1, report_unasus_dados_grafico($report));
    }

    /**
     * With view_all the graph has all groups.
     *
     * @covers ::report_unasus_dados_grafico
     */
    public function test_grafico_view_all_traz_os_3_grupos() {
        $this->usuario(['report/unasus:view_all', 'report/unasus:view_tutoria'], []);
        $report = $this->report(null, 'atividades_vs_notas');
        $this->assertCount(3, report_unasus_dados_grafico($report));
    }

    /**
     * A tutor with no group gets no graph data at all.
     *
     * @covers ::report_unasus_dados_grafico
     */
    public function test_grafico_tutor_sem_grupo_nao_traz_dados() {
        $this->usuario(['report/unasus:view_tutoria'], []);
        $report = $this->report(null, 'atividades_vs_notas');
        $this->assertNull(report_unasus_dados_grafico($report));
    }
}
