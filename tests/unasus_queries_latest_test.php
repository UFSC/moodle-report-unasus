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
 * Tests for the assignment and forum queries of the reports.
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
 * The assignment query reads the latest attempt; the forum query reads the first post.
 *
 * MySQL 5.7 merges derived tables and drops their ORDER BY, so a "latest attempt" picked
 * by ordering a subquery and grouping outside it returns an arbitrary attempt there.
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      report_unasus
 */
class unasus_queries_latest_test extends advanced_testcase {
    /** @var stdClass */
    protected $course;
    /** @var int relationship.id */
    protected $relationshipid;
    /** @var int relationship_groups.id */
    protected $grupo;
    /** @var stdClass[] Students keyed by scenario name. */
    protected $students = [];

    /**
     * Creates a course and one tutoring group with one student per scenario.
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();

        // The report only builds with activities that track completion.
        set_config('enablecompletion', 1);
        set_config('local_tutores_student_roles', 'student');
        set_config('local_tutores_tutor_roles', 'teacher');
        set_config('local_tutores_orientador_roles', 'editingteacher');
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);

        $category = $gen->create_category();
        $catcontext = context_coursecat::instance($category->id);
        $this->course = $gen->create_course(['category' => $category->id, 'enablecompletion' => 1]);

        $this->relationshipid = relationship_add_relationship((object) [
            'contextid' => $catcontext->id,
            'name' => 'Tutoria',
            'tags' => ['grupo_tutoria'],
        ]);
        $rcestudante = relationship_add_cohort((object) [
            'relationshipid' => $this->relationshipid,
            'cohortid' => $gen->create_cohort(['contextid' => $catcontext->id])->id,
            'roleid' => $studentroleid,
            'allowdupsingroups' => 0,
            'uniformdistribution' => 0,
        ]);
        $this->grupo = relationship_add_group((object) [
            'relationshipid' => $this->relationshipid,
            'name' => 'Grupo A',
            'userlimit' => 0,
            'uniformdistribution' => 0,
        ]);

        $names = ['nosubmission', 'oneattempt', 'reopened', 'threeattempts', 'latestungraded', 'otherassign'];
        foreach ($names as $name) {
            $user = $gen->create_user(['firstname' => $name, 'lastname' => 'student']);
            $gen->enrol_user($user->id, $this->course->id, $studentroleid);
            relationship_add_member($this->grupo, $rcestudante, $user->id);
            $this->students[$name] = $user;
        }
    }

    /**
     * Leaves no report in the factory singleton for the next test classes.
     */
    protected function tearDown() {
        $singleton = new ReflectionProperty('report_unasus_factory', 'report');
        $singleton->setAccessible(true);
        $singleton->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Builds a fresh report, as index.php does, so the queries read its filters.
     */
    protected function report() {
        $singleton = new ReflectionProperty('report_unasus_factory', 'report');
        $singleton->setAccessible(true);
        $singleton->setValue(null, null);
        $_GET['relatorio'] = 'entrega_de_atividades';
        $_GET['course'] = $this->course->id;
        report_unasus_factory::singleton();
    }

    /**
     * Inserts one submission attempt and, optionally, its grade.
     *
     * @param int $assignid Assignment id.
     * @param string $name Scenario name.
     * @param int $attempt Attempt number.
     * @param bool $latest Whether it is the latest attempt.
     * @param string $status Submission status.
     * @param float|null $grade Grade of the attempt, null for none.
     */
    protected function attempt($assignid, $name, $attempt, $latest, $status, $grade) {
        global $DB;
        $userid = $this->students[$name]->id;
        $time = 1000000 + $attempt * 100;
        $DB->insert_record('assign_submission', (object) [
            'assignment' => $assignid,
            'userid' => $userid,
            'timecreated' => $time,
            'timemodified' => $time + 1,
            'status' => $status,
            'groupid' => 0,
            'attemptnumber' => $attempt,
            'latest' => $latest ? 1 : 0,
        ]);
        if ($grade !== null) {
            $DB->insert_record('assign_grades', (object) [
                'assignment' => $assignid,
                'userid' => $userid,
                'timecreated' => $time + 2,
                'timemodified' => $time + 3,
                'grader' => 2,
                'grade' => $grade,
                'attemptnumber' => $attempt,
            ]);
        }
    }

    /**
     * Runs a query of the report for the tutoring group, keyed by scenario name.
     *
     * @param string $sql Query.
     * @param array $params Query parameters besides course, relationship and group.
     * @return stdClass[]
     */
    protected function rows($sql, array $params) {
        global $DB;
        $params += [
            'courseid' => $this->course->id,
            'courseid2' => $this->course->id,
            'relationship_id' => $this->relationshipid,
            'grupo' => $this->grupo,
        ];
        $byname = [];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $row) {
            foreach ($this->students as $name => $user) {
                if ($user->id == $row->userid) {
                    $this->assertArrayNotHasKey($name, $byname, "Duplicated row for {$name}");
                    $byname[$name] = $row;
                }
            }
        }
        $rs->close();
        return $byname;
    }

    /**
     * Each student gets one row with the status and the grade of the latest attempt.
     *
     * The older attempts are inserted first, so they have the lower ids: the order a
     * plain GROUP BY tends to keep when the ordering of the subquery is dropped.
     */
    public function test_assignment_reads_latest_attempt() {
        $gen = $this->getDataGenerator();
        $assign = $gen->create_module('assign', ['course' => $this->course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $other = $gen->create_module('assign', ['course' => $this->course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);

        $this->attempt($assign->id, 'oneattempt', 0, true, 'submitted', 7);

        $this->attempt($assign->id, 'reopened', 0, false, 'reopened', 4);
        $this->attempt($assign->id, 'reopened', 1, true, 'submitted', 9);

        $this->attempt($assign->id, 'threeattempts', 0, false, 'reopened', 2);
        $this->attempt($assign->id, 'threeattempts', 1, false, 'reopened', 5);
        $this->attempt($assign->id, 'threeattempts', 2, true, 'submitted', 8);

        // The latest attempt has no grade yet: the grade of the older one must not show.
        $this->attempt($assign->id, 'latestungraded', 0, false, 'reopened', 6);
        $this->attempt($assign->id, 'latestungraded', 1, true, 'submitted', null);

        // An attempt in another assignment must not leak into this one.
        $this->attempt($other->id, 'otherassign', 0, true, 'submitted', 10);

        $this->report();
        $rows = $this->rows(query_atividades_from_users(null), [
            'assignmentid' => $assign->id,
            'assignmentid2' => $assign->id,
        ]);

        $this->assertCount(count($this->students), $rows);
        $expected = [
            'nosubmission' => [null, null, null],
            'oneattempt' => ['submitted', 7, 1000000],
            'reopened' => ['submitted', 9, 1000100],
            'threeattempts' => ['submitted', 8, 1000200],
            'latestungraded' => ['submitted', null, 1000100],
            'otherassign' => [null, null, null],
        ];
        foreach ($expected as $name => $values) {
            list($status, $grade, $created) = $values;
            $row = $rows[$name];
            $this->assertSame($status, $row->status, "Status of {$name}");
            $this->assertEquals($grade, $row->grade, "Grade of {$name}");
            $this->assertEquals($created, $row->submission_date, "Submission date of {$name}");
        }
    }

    /**
     * The forum submission date is the first post of the student, in any discussion.
     */
    public function test_forum_reads_first_post() {
        global $DB;
        $gen = $this->getDataGenerator();
        $forumgen = $gen->get_plugin_generator('mod_forum');
        $forum = $gen->create_module('forum', ['course' => $this->course->id, 'name' => 'Forum X', 'completion' => COMPLETION_TRACKING_MANUAL]);
        $otherforum = $gen->create_module('forum', ['course' => $this->course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);

        $discussion = function($forumid, $name, $time) use ($forumgen) {
            return $forumgen->create_discussion([
                'course' => $this->course->id,
                'forum' => $forumid,
                'userid' => $this->students[$name]->id,
                'timemodified' => $time,
            ]);
        };
        $post = function($discussionid, $parent, $name, $time) use ($DB) {
            // Post creation time is set directly: the generator stamps the current time.
            $record = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post([
                'discussion' => $discussionid,
                'parent' => $parent,
                'userid' => $this->students[$name]->id,
            ]);
            $DB->set_field('forum_posts', 'created', $time, ['id' => $record->id]);
        };

        // One post only.
        $d1 = $discussion($forum->id, 'oneattempt', 5000);
        $DB->set_field('forum_posts', 'created', 5000, ['discussion' => $d1->id]);

        // Three posts, the earliest one last in id and in discussion order, so a row picked by
        // GROUP BY without aggregation would not be it.
        $d2 = $discussion($forum->id, 'reopened', 7000);
        $DB->set_field('forum_posts', 'created', 7000, ['discussion' => $d2->id]);
        $post($d1->id, $d1->firstpost, 'reopened', 9000);
        $post($d2->id, $d2->firstpost, 'reopened', 3000);

        // A post in another forum must not count.
        $d3 = $discussion($otherforum->id, 'otherassign', 1000);
        $DB->set_field('forum_posts', 'created', 1000, ['discussion' => $d3->id]);

        $this->report();
        $rows = $this->rows(query_postagens_forum_from_users(null), ['forumid' => $forum->id]);

        $this->assertCount(count($this->students), $rows);
        $this->assertEquals(5000, $rows['oneattempt']->submission_date);
        $this->assertEquals(1, $rows['oneattempt']->has_post);
        $this->assertEquals(3000, $rows['reopened']->submission_date);
        $this->assertEquals(1, $rows['reopened']->has_post);
        $this->assertEquals('Forum X', $rows['reopened']->forum_name);
        $this->assertNull($rows['nosubmission']->submission_date);
        $this->assertEquals(0, $rows['nosubmission']->has_post);
        $this->assertEquals(0, $rows['otherassign']->has_post);
    }
}
