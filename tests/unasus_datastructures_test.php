<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/report/unasus/activities_datastructures.php');

class unasus_datastructures_testcase extends advanced_testcase {

    public function test_report_unasus_activity() {
        $now = time();
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));

        // Atividades com prazo
        $activity->deadline = $now;
        // Retornam true quando questionadas se possuem prazo.
        $this->assertEquals(true, $activity->has_deadline());
    }

    public function test_report_unasus_data_submission() {
        $now = time();
        $year_ago = $now-60*60*24*365;


        //
        // Para atividades sem prazos
        //

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $activity->deadline = 0;

        // Atividades não entreges, nunca estão em atraso
        $this->assertEquals(false, $data->is_submission_due());

        // Atividades entregues, estão sempre em dia
        $activity->submission_date=$now;
        $this->assertEquals(false, $data->is_submission_due());

        $activity->submission_date=0;
        $this->assertEquals(false, $data->is_submission_due());

        $activity->submission_date=$year_ago;
        $this->assertEquals(false, $data->is_submission_due());

        //
        // Para atividades com prazos e com entrega habilitados
        //

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $activity->deadline = $year_ago;

        // atividades não entregue, após o prazo, estão em atraso
        $this->assertEquals(true, $data->is_submission_due());

        //
        // Para atividades com prazos e sem entrega habilitados (offline)
        //

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $activity->deadline = $year_ago;

        // não deve retornar que possui uma entrega
        $this->assertEquals(false, $data->has_submitted());

        // nunca estará com a entrega em atraso, já que não possui entrega
        $this->assertEquals(false, $data->is_submission_due());
    }

    public function test_report_unasus_data_grade() {
        $now = time();
        $year_ago = $now-60*60*24*365;

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        //
        // Para atividades sem prazo e com envio ativado
        //

        $activity->deadline = null;

        // Se não houver uma entrega, uma nota não é necessária
        $this->assertEquals(false, $data->is_grade_needed());
        $this->assertEquals(false, $data->grade_due_days());

        // Se houver uma entrega, uma nota é necessária (independente de quando for a entrega)
        $data->submission_date = $now;
        $this->assertEquals(true, $data->is_grade_needed());

        $data->submission_date = $year_ago;
        $this->assertEquals(true, $data->is_grade_needed());

        // Se houver uma entrega e foi dado uma nota, ainda deve retornar que uma nota é necessária
        $data->grade = 5;
        $data->grade_date = $now;
        $this->assertEquals(true, $data->is_grade_needed());

    }

    public function test_report_unasus_data_activity() {
        $now = time();
        $year_ago = $now-60*60*24*365;

        //
        // Dado com submission em draft e com nota
        //
        $assign_draft = new stdClass();
        $assign_draft->userid = 1;
        $assign_draft->grade = 5;
        $assign_draft->submission_date = $year_ago;
        $assign_draft->grade_created = $year_ago;
        $assign_draft->status = 'draft';

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = new report_unasus_data_activity($activity, $assign_draft);

        // HACK: consideramos como enviado (mesmo que esteja em draft), se já tiver nota, para não estregar os outros relatórios
        $this->assertEquals(true, $data->has_submitted());

        // O fato de ser draft não deve influenciar o comando de verificar a existência de nota
        $this->assertEquals(true, $data->has_grade());

        //
        // Dado com submission em draft e sem nota
        //
        $assign_draft = new stdClass();
        $assign_draft->userid = 1;
        $assign_draft->grade = null;
        $assign_draft->grade_created = null;
        $assign_draft->grade_modified = null;
        $assign_draft->submission_date = $year_ago;
        $assign_draft->status = 'draft';

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = new report_unasus_data_activity($activity, $assign_draft);

        // Dado com envio em draft, não deve ser considerado como enviado
        $this->assertEquals(false, $data->has_submitted());

        // O fato de ser draft não deve influenciar o comando de verificar a existência de nota
        $this->assertEquals(false, $data->has_grade());

        //
        // Dado com submission em correto
        //
        $assign_submitted = new stdClass();
        $assign_submitted->userid = 1;
        $assign_submitted->grade = 5;
        $assign_submitted->submission_date = $year_ago;
        $assign_submitted->grade_created = $year_ago;
        $assign_submitted->status = 'submitted';

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = new report_unasus_data_activity($activity, $assign_submitted);

        $this->assertEquals(true, $data->has_submitted());
    }
}