<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/report/unasus/activities_datastructures.php');

/**
 * Test Datastructures
 * @group report_unasus
 */
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
        $year_ago = $now - 60 * 60 * 24 * 365;


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
        $activity->submission_date = $now;
        $this->assertEquals(false, $data->is_submission_due());

        $activity->submission_date = 0;
        $this->assertEquals(false, $data->is_submission_due());

        $activity->submission_date = $year_ago;
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
        $year_ago = $now - 60 * 60 * 24 * 365;

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

    public function test_report_unasus_data_activity_status() {
        $now = time();
        $year_ago = $now - 60 * 60 * 24 * 365;

        //
        // Dado com submission em draft e com nota
        //
        $assign_draft = new stdClass();
        $assign_draft->userid = 1;
        $assign_draft->grade = 5;
        $assign_draft->polo = null;
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
        $assign_draft->polo = null;
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
        $assign_submitted->polo = null;
        $assign_submitted->submission_modified = $year_ago;  // construtor usa submission_modified para status='submitted'
        $assign_submitted->grade_created = $year_ago;
        $assign_submitted->status = 'submitted';

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = new report_unasus_data_activity($activity, $assign_submitted);

        $this->assertEquals(true, $data->has_submitted());
    }

    public function test_report_unasus_data_activity_offline() {
        $now = time();
        $year_ago = $now - 60 * 60 * 24 * 365;
        $next_year = $now + 60 * 60 * 24 * 365;

        /**
         * Criacao de uma atividade sem nota e sem prazo.
         *
         * @var stdClass $assign_offline
         */
        $assign_offline = new stdClass();
        $assign_offline->userid = 1;
        $assign_offline->grade = null;
        $assign_offline->polo = null;
        $assign_offline->submission_date = null;
        $assign_offline->submission_modified = null;
        $assign_offline->status = null;
        $assign_offline->grade_created = null;
        $assign_offline->grade_modified = null;

        /**
         * Atividade offline com data de entrega, sem prazo, sem grupo e sem grupo
         *
         * @var report_unasus_activity $activity
         */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));

        /** @var report_unasus_data $data */
        $data = new report_unasus_data_activity($activity, $assign_offline);

        $this->assertEquals(false, $data->has_submitted());
        $this->assertEquals(false, $data->has_grade());
        $this->assertEquals(false, $data->is_submission_due());
        $this->assertEquals(false, $activity->has_grouping());


        /**
         * Atividade offline com data de entrega e com prazo e com grupo e com grupo
         *
         * @var report_unasus_activity $activity
         */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));
        $activity->deadline = $year_ago;
        $activity->grouping = 1;

        /** @var report_unasus_data_activity $data */
        $data = new report_unasus_data_activity($activity, $assign_offline);

        $this->assertEquals(false, $data->has_submitted());
        $this->assertEquals(false, $data->has_grade());
        $this->assertEquals(false, $data->is_submission_due());
        $this->assertEquals(true, $data->is_grade_needed());
        $this->assertEquals(true, $activity->has_grouping());


        /**
         * Criacao de uma atividade com nota e sem prazo.
         *
         * @var stdClass $assign_offline
         */
        $assign_offline->grade = 7;
        $assign_offline->grade_created = $now;

        /** @var report_unasus_data_activity $data */
        $data = new report_unasus_data_activity($activity, $assign_offline);

        $this->assertEquals(true, $data->has_grade());
        $this->assertEquals(true, $data->has_submitted());
        $this->assertEquals(false, $data->is_submission_due());
        $this->assertEquals(true, $data->is_grade_needed());


        $activity->deadline = $next_year;

        /**
         * Atividade pro ano que vem com nota
         *
         * @var report_unasus_data_activity $data
         */
        $data = new report_unasus_data_activity($activity, $assign_offline);
        $this->assertEquals(true, $data->has_grade());
        $this->assertEquals(true, $data->has_submitted());
        $this->assertEquals(false, $data->is_submission_due());
        $this->assertEquals(true, $data->is_grade_needed());

        $assign_offline->grade = null;
        $assign_offline->grade_created = null;

        /**
         * Atividade pro ano que vem sem nota
         *
         * @var report_unasus_data_activity $data
         */
        $data = new report_unasus_data_activity($activity, $assign_offline);
        $this->assertEquals(false, $data->has_grade());
        $this->assertEquals(false, $data->has_submitted());
        $this->assertEquals(false, $data->is_submission_due());
        $this->assertEquals(false, $data->is_grade_needed());


    }

    // -----------------------------------------------------------------------
    // report_unasus_activity — construtor e flags
    // -----------------------------------------------------------------------

    public function test_report_unasus_activity_invalid_constructor() {
        // has_submission deve ser bool
        try {
            $this->getMockForAbstractClass('report_unasus_activity', array(1, true));
            $this->fail('Esperada InvalidArgumentException para has_submission não-bool');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
        }

        // has_grade deve ser bool
        try {
            $this->getMockForAbstractClass('report_unasus_activity', array(true, 'sim'));
            $this->fail('Esperada InvalidArgumentException para has_grade não-bool');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
        }
    }

    public function test_report_unasus_activity_flags() {
        $with_submission    = $this->getMockForAbstractClass('report_unasus_activity', array(true,  true));
        $without_submission = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));

        $this->assertTrue($with_submission->has_submission());
        $this->assertFalse($without_submission->has_submission());

        // has_grouping: sem agrupamento
        $this->assertFalse($with_submission->has_grouping());

        // has_grouping: com agrupamento
        $with_submission->grouping = 5;
        $this->assertTrue($with_submission->has_grouping());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data — has_submitted e has_grade na classe base
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_has_submitted_and_grade() {
        $now = time();

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        // Sem submissão e sem nota
        $this->assertFalse($data->has_submitted());
        $this->assertFalse($data->has_grade());

        // Com data de submissão → entregue
        $data->submission_date = $now;
        $this->assertTrue($data->has_submitted());

        // Com nota e data de nota → tem nota
        $data->grade      = 7.5;
        $data->grade_date = $now;
        $this->assertTrue($data->has_grade());

        // Nota sem grade_date → não tem nota
        $data->grade_date = null;
        $this->assertFalse($data->has_grade());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data — is_submission_due com dados históricos
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_submission_historical() {
        $now      = time();
        $year_ago = $now - 60 * 60 * 24 * 365;

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        // Entregue ANTES do prazo → não está em atraso
        $activity->deadline    = $now;
        $data->submission_date = $year_ago;
        $this->assertFalse($data->is_submission_due());

        // Entregue DEPOIS do prazo → em atraso
        $activity->deadline    = $year_ago;
        $data->submission_date = $now;
        $this->assertTrue($data->is_submission_due());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data — is_activity_pending
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_is_activity_pending() {
        $now       = time();
        $year_ago  = $now - 60 * 60 * 24 * 365;
        $next_year = $now + 60 * 60 * 24 * 365;

        // Com nota habilitada e nota atribuída → NÃO pendente
        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));
        $data->grade      = 5;
        $data->grade_date = $now;
        $this->assertFalse($data->is_activity_pending());

        // Com envio habilitado e enviada → NÃO pendente
        /** @var report_unasus_activity $activity2 */
        $activity2 = $this->getMockForAbstractClass('report_unasus_activity', array(true, false));
        /** @var report_unasus_data $data2 */
        $data2 = $this->getMockForAbstractClass('report_unasus_data', array(&$activity2));
        $data2->submission_date = $year_ago;
        $this->assertFalse($data2->is_activity_pending());

        // Atividade offline, sem nota, prazo futuro → NÃO pendente
        /** @var report_unasus_activity $activity3 */
        $activity3 = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));
        $activity3->deadline = $next_year;
        /** @var report_unasus_data $data3 */
        $data3 = $this->getMockForAbstractClass('report_unasus_data', array(&$activity3));
        $this->assertFalse($data3->is_activity_pending());

        // Com envio habilitado, não enviada, prazo vencido → pendente
        /** @var report_unasus_activity $activity4 */
        $activity4 = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        $activity4->deadline = $year_ago;
        /** @var report_unasus_data $data4 */
        $data4 = $this->getMockForAbstractClass('report_unasus_data', array(&$activity4));
        $this->assertTrue($data4->is_activity_pending());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data — is_a_future_due
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_is_a_future_due() {
        $now       = time();
        $year_ago  = $now - 60 * 60 * 24 * 365;
        $next_year = $now + 60 * 60 * 24 * 365;

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        // Com nota → não é futuro
        $data->grade      = 5;
        $data->grade_date = $now;
        $this->assertFalse($data->is_a_future_due());

        // Sem nota, sem prazo → sempre futuro
        $data->grade        = null;
        $data->grade_date   = null;
        $activity->deadline = 0;
        $this->assertTrue($data->is_a_future_due());

        // Sem nota, prazo futuro → futuro
        $activity->deadline = $next_year;
        $this->assertTrue($data->is_a_future_due());

        // Sem nota, prazo passado → NÃO futuro
        $activity->deadline = $year_ago;
        $this->assertFalse($data->is_a_future_due());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data_activity — tratamentos especiais do construtor
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_activity_grade_minus_one() {
        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));

        $db_model                 = new stdClass();
        $db_model->userid         = 1;
        $db_model->polo           = null;
        $db_model->grade          = -1; // Moodle usa -1 para indicar ausência de escala
        $db_model->grade_created  = null;
        $db_model->grade_modified = null;
        $db_model->status         = 'new';

        $data = new report_unasus_data_activity($activity, $db_model);

        // Grade -1 deve ser tratado como ausência de nota
        $this->assertFalse($data->has_grade());
    }

    public function test_report_unasus_data_activity_status_new() {
        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));

        $db_model                 = new stdClass();
        $db_model->userid         = 1;
        $db_model->polo           = null;
        $db_model->grade          = null;
        $db_model->grade_created  = null;
        $db_model->grade_modified = null;
        $db_model->status         = 'new';

        $data = new report_unasus_data_activity($activity, $db_model);

        // Status 'new' não é considerado entrega válida
        $this->assertFalse($data->has_submitted());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data_forum
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_forum() {
        $now = time();

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));

        // Com submissão e nota
        $db_with                  = new stdClass();
        $db_with->userid          = 1;
        $db_with->polo            = null;
        $db_with->grade           = 8.0;
        $db_with->grademax        = 10;
        $db_with->submission_date = $now;
        $db_with->timemodified    = $now;

        $data_with = new report_unasus_data_forum($activity, $db_with);
        $this->assertTrue($data_with->has_submitted());
        $this->assertTrue($data_with->has_grade());

        // Sem submissão e sem nota
        $db_without                  = new stdClass();
        $db_without->userid          = 1;
        $db_without->polo            = null;
        $db_without->grade           = null;
        $db_without->grademax        = null;
        $db_without->submission_date = null;
        $db_without->timemodified    = null;

        $data_without = new report_unasus_data_forum($activity, $db_without);
        $this->assertFalse($data_without->has_submitted());
        $this->assertFalse($data_without->has_grade());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data_quiz
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_quiz() {
        $now = time();

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));

        // Com submissão e nota normal
        $db_model                  = new stdClass();
        $db_model->userid          = 1;
        $db_model->polo            = null;
        $db_model->grade           = 7.5;
        $db_model->grademax        = 10;
        $db_model->submission_date = $now;
        $db_model->grade_date      = $now;

        $data = new report_unasus_data_quiz($activity, $db_model);
        $this->assertTrue($data->has_submitted());
        $this->assertTrue($data->has_grade());

        // Grade -1 deve ser tratado como ausência de nota
        $db_model->grade = -1;
        $data2 = new report_unasus_data_quiz($activity, $db_model);
        $this->assertFalse($data2->has_grade());
    }

    // -----------------------------------------------------------------------
    // report_unasus_data — is_member_of
    // -----------------------------------------------------------------------

    public function test_report_unasus_data_is_member_of() {
        /** @var report_unasus_activity $activity */
        $activity            = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        $activity->course_id = 10;
        $activity->grouping  = '0';

        /** @var report_unasus_data $data */
        $data         = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));
        $data->userid = 42;

        // Agrupamento 0 → todos são membros
        $this->assertTrue($data->is_member_of(array()));

        // Agrupamento definido, usuário presente no grupo
        $activity->grouping = '5';
        $agrupamentos = array('5' => array(10 => array(42 => true)));
        $this->assertTrue($data->is_member_of($agrupamentos));

        // Agrupamento definido, usuário ausente do grupo
        $agrupamentos_sem_usuario = array('5' => array(10 => array(99 => true)));
        $this->assertFalse($data->is_member_of($agrupamentos_sem_usuario));

        // Agrupamento não encontrado na estrutura
        $this->assertFalse($data->is_member_of(array()));
    }

    // -----------------------------------------------------------------------
    // Testes de borda — limites de datas
    // -----------------------------------------------------------------------

    public function test_is_submission_due_exactly_at_deadline() {
        // Limite inferior: entrega exatamente no prazo (deadline == submission_date).
        // A comparação interna é deadline < submission_date, portanto igualdade → false.
        $deadline = mktime(12, 0, 0, 6, 15, 2024);

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        $activity->deadline = $deadline;

        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));
        $data->submission_date = $deadline;

        $this->assertFalse($data->is_submission_due());
    }

    public function test_is_submission_due_one_second_after_deadline() {
        // Um segundo após o prazo já é considerado atraso.
        $deadline = mktime(12, 0, 0, 6, 15, 2024);

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        $activity->deadline = $deadline;

        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));
        $data->submission_date = $deadline + 1;

        $this->assertTrue($data->is_submission_due());
    }

    // -----------------------------------------------------------------------
    // Testes de borda — nota zero é diferente de nota ausente
    // -----------------------------------------------------------------------

    public function test_grade_zero_with_grade_date_is_valid_grade() {
        // Nota 0 com data de avaliação é uma nota válida (diferente de null e de -1).
        $now = time();

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $data->grade      = 0;
        $data->grade_date = $now;

        $this->assertTrue($data->has_grade());
    }

    public function test_grade_zero_without_grade_date_is_not_valid_grade() {
        // Nota 0 sem data de avaliação não é considerada nota válida pelo relatório.
        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(true, true));
        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $data->grade      = 0;
        $data->grade_date = null;

        $this->assertFalse($data->has_grade());
    }

    // -----------------------------------------------------------------------
    // Testes de borda — atividade offline exatamente no prazo (> vs >=)
    // -----------------------------------------------------------------------

    public function test_is_grade_needed_offline_activity_at_deadline_boundary() {
        // Para atividade offline, a isenção de nota usa deadline > now (estritamente maior).
        // Quando deadline == now, a isenção não se aplica → nota IS necessária.
        $now = time();

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));
        $activity->deadline = $now;

        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $this->assertTrue($data->is_grade_needed());
    }

    public function test_is_activity_pending_offline_at_deadline_boundary() {
        // Para atividade offline, a isenção de pendência usa deadline > now (estritamente maior).
        // Quando deadline == now, a isenção não se aplica → atividade IS pendente.
        $now = time();

        /** @var report_unasus_activity $activity */
        $activity = $this->getMockForAbstractClass('report_unasus_activity', array(false, true));
        $activity->deadline = $now;

        /** @var report_unasus_data $data */
        $data = $this->getMockForAbstractClass('report_unasus_data', array(&$activity));

        $this->assertTrue($data->is_activity_pending());
    }
}