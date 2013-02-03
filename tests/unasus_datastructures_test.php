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
        /** @var report_unasus_data $data */
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
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $activity->deadline = $year_ago;

        // atividades não entregue, após o prazo, estão em atraso
        $this->assertEquals(true, $data->is_submission_due());

        //
        // Para atividades com prazos e sem entrega habilitados (offline)
        //

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $activity->deadline = $year_ago;

        // não deve retornar que possui uma entrega
        $this->assertEquals(false, $data->has_submitted());

        // nunca estará com a entrega em atraso, já que não possui entrega
        $this->assertEquals(false, $data->is_submission_due());
    }
}