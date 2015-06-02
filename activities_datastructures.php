<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Representa uma atividade (tarefa, forum, quiz)
 *
 * @property $deadline
 */
abstract class report_unasus_activity {

    public $id;
    public $name;
    public $deadline;
    public $has_submission;
    public $has_grade;
    public $course_id;
    public $course_name;
    public $grouping;

    public function __construct($has_submission, $has_grade) {
        if (!is_bool($has_submission) || !is_bool($has_grade)) {
            throw new InvalidArgumentException;
        }

        $this->has_submission = $has_submission;
        $this->has_grade = $has_grade;
    }

    /**
     * Esta atividade possui um prazo?
     *
     * @return bool true se tiver prazo definido ou false caso contrário
     */
    public function has_deadline() {
        return (!empty($this->deadline));
    }

    /**
     * Esta atividade possui uma entrega?
     *
     * @return bool true se tiver entrega, seja de arquivo ou texto online
     */
    public function has_submission() {
        return (!empty($this->has_submission));
    }

    /**
     * Esta atividade tem agrupamento?
     */
    public function has_grouping() {
        return (!empty($this->grouping));
    }

    abstract function __toString();
}

class report_unasus_assign_activity extends report_unasus_activity {

    public function __construct($db_model) {

        $has_submission = !$db_model->nosubmissions;
        $has_grade = ((int) $db_model->grade) == 0 ? false : true;

        parent::__construct($has_submission, $has_grade);

        $this->id = $db_model->assign_id;
        $this->name = $db_model->assign_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->grouping = $db_model->grouping_id;
    }

    public function __toString() {
        $cm = get_coursemodule_from_instance('assign', $this->id, $this->course_id, null, MUST_EXIST);
        $atividade_url = new moodle_url('/mod/assign/view.php', array('id' => $cm->id, 'target' => '_blank'));
        return html_writer::link($atividade_url, $this->name, array('target' => '_blank'));
    }

}

class report_unasus_forum_activity extends report_unasus_activity {

    public function __construct($db_model) {
        parent::__construct(true, true);

        $this->id = $db_model->forum_id;
        $this->name = $db_model->forum_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->grouping = $db_model->grouping_id;
    }

    public function __toString() {
        $cm = get_coursemodule_from_instance('forum', $this->id, $this->course_id, null, MUST_EXIST);
        $forum_url = new moodle_url('/mod/forum/view.php', array('id' => $cm->id, 'target' => '_blank'));
        return html_writer::link($forum_url, $this->name, array('target' => '_blank'));
    }

}

class report_unasus_quiz_activity extends report_unasus_activity {

    public function __construct($db_model) {

        $has_grade = ((int) $db_model->grade) == 0 ? false : true;

        parent::__construct(true, $has_grade);
        $this->id = $db_model->quiz_id;
        $this->name = $db_model->quiz_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->grouping = $db_model->grouping_id;
    }

    public function __toString() {
        $cm = get_coursemodule_from_instance('quiz', $this->id, $this->course_id, null, IGNORE_MISSING);
        $quiz_url = new moodle_url('/mod/quiz/view.php', array('id' => $cm->id, 'target' => '_blank'));
        return html_writer::link($quiz_url, $this->name, array('target' => '_blank'));
    }

}

class report_unasus_db_activity extends report_unasus_activity {

    public function __construct($db_model) {

        parent::__construct(true, false);
        $this->id = $db_model->database_id;
        $this->name = $db_model->database_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->grouping = $db_model->grouping_id;
    }

    public function __toString() {
        $cm = get_coursemodule_from_instance('data', $this->id, $this->course_id, null, IGNORE_MISSING);
        $db_url = new moodle_url('/mod/data/view.php', array('id' => $cm->id, 'target' => '_blank'));
        return html_writer::link($db_url, $this->name, array('target' => '_blank'));
    }

}

class report_unasus_scorm_activity extends report_unasus_activity {

    public function __construct($db_model) {

        $has_grade = ((int) $db_model->grade) == 0 ? false : true;

        parent::__construct(true, $has_grade);
        $this->id = $db_model->scorm_id;
        $this->name = $db_model->scorm_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->grouping = $db_model->grouping_id;
    }

    public function __toString() {
        $cm = get_coursemodule_from_instance('db', $this->id, $this->course_id, null, IGNORE_MISSING);
        $scorm_url = new moodle_url('/mod/scorm/view.php', array('id' => $cm->id, 'target' => '_blank'));
        return html_writer::link($scorm_url, $this->name, array('target' => '_blank'));
    }

}

class report_unasus_lti_activity extends report_unasus_activity {

    public function __construct($db_model) {
        parent::__construct(true, true);

        $this->id = $db_model->id;
        $this->name = $db_model->name;
        $this->deadline = $db_model->completionexpected;
        $this->position = $db_model->position;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->course_module_id = $db_model->course_module_id;
        $this->baseurl = $db_model->baseurl;
        $this->consumer_key = $db_model->consumer_key;
        $this->grouping = $db_model->grouping_id;
    }

    /**
     * @todo verificar parametro 'lti', tá vindo vazio e dado erro na linha seguinte $cm->id
     */
    public function __toString() {
        $lti_url = new moodle_url('/mod/lti/view.php', array('id' => $this->course_module_id, 'target' => '_blank'));
        return html_writer::link($lti_url, $this->name, array('target' => '_blank'));
    }

}

/**
 * Representa dados de usuário a respeito de alguma atividade
 *
 * Esta estrutura será utilizada para responder questões como, nome, data de envio, se houve envio, se houve nota, etc.
 */
abstract class report_unasus_data {

    public $source_activity;
    public $userid;
    public $cohort;
    public $polo;
    public $grade;
    public $submission_date;
    public $grade_date;

    /**
     * @param report_unasus_activity $source_activity qual a atividade esta informação se refere
     */
    public function __construct(report_unasus_activity &$source_activity) {
        $this->source_activity = $source_activity;
    }

    /**
     * Houve um envio de atividade?
     *
     * @return bool true se existe um envio ou false caso contrário
     */
    public function has_submitted() {
        return !is_null($this->submission_date);
    }

    /**
     * Atividade possui nota?
     *
     * @return bool true se tiver ou false se não
     */
    public function has_grade() {
        return !is_null($this->grade) && !is_null($this->grade_date);
    }

    /**
     * Retorna os dias de atraso em relação a entrega de atividades
     *
     * @return bool
     */
    public function submission_due_days() {
        if (!$this->is_submission_due()) {
            return false;
        }

        $deadline = get_datetime_from_unixtime($this->source_activity->deadline);

        if ($this->has_submitted()) {
            // se foi enviada, o atraso será relacionado a um dado histórico
            // usaremos a diferença entre a data de envio e a data esperada
            $duediff = $deadline->diff(get_datetime_from_unixtime($this->submission_date));
        } else {
            // se não foi enviada, o atraso será relacionado a um dado atual
            // usaremos a diferença entre data a atual e a data esperada
            $duediff = $deadline->diff(date_create());
        }

        return (int) $duediff->format("%a");
    }

    /**
     * Retorna dias de atraso em relação a submissão da nota
     *
     * se a atividade possui envio:
     * - o atraso é contabilizado em relação a entrega da atividade
     *
     * se a atividade não possui envio (atividade offline):
     * - o atraso é contabilizado em relação a data esperada de entrega da atividade
     *
     * @see is_grade_needed()
     * @return bool|int false se não estiver em atraso ou o número de dias em atraso
     */
    public function grade_due_days() {
        if (!$this->is_grade_needed()) {
            return false;
        }

        if ($this->source_activity->has_submission) {
            // se a atividade possui entrega ativada
            // o prazo é contato a partir da data de envio
            $deadline = get_datetime_from_unixtime($this->submission_date);
        } else {
            // se a atividade não possui entrega ativada
            // o prazo é contato a partir da data esperada de entrega
            $deadline = get_datetime_from_unixtime($this->source_activity->deadline);
        }

        if ($this->has_grade()) {
            // se possui nota, o atraso é relacionado a um dado histórico
            // usaremos a diferença do deadline com a data de envio da nota

            $grade_datetime = get_datetime_from_unixtime($this->grade_date);
            $duediff = $deadline->diff($grade_datetime);
        } else {
            // se não possui nota, o atraso é relacionado a um dado atual
            // usaremos a diferença do deadline com a data atual
            $duediff = $deadline->diff(date_create());
        }
        return (int) $duediff->format("%a");
    }

    /**
     * Retorna se as condições para se ter uma nota já foram cumpridas
     *
     * Este método considera apenas os casos negativos, quando os pré-requisitos
     * para se ter nota, não foram cumpridos, ele não considera o fato da nota já ter sido atribuída.
     *
     * Isto é proposital, para que a função grade_due_days funcione em relatórios com dados históricos
     *
     * @see grade_due_days()
     * @return bool
     */
    public function is_grade_needed() {

        $now = time();

        if (!$this->source_activity->has_grade) {
            // se a atividade não possui nota habilitado
            // não é necessário enviar a nota
            return false;
        } else if ($this->source_activity->has_submission && !$this->has_submitted()) {
            // se a atividade precisa que seja enviado e não foi feito um envio
            // não é necessário enviar uma nota
            return false;
        } else if (!$this->source_activity->has_submission && !$this->has_grade() && $this->source_activity->deadline > $now) {
            // se a atividade não possui envio, não possui nota enviada
            // e ainda não chegou a data esperada de entrega,
            // não é necessário enviar uma nota
            return false;
        }

        return true;
    }

    /**
     * Retorna se as condições para se ter uma atividade completa já foram cumpridas
     *
     *
     * @see grade_due_days()
     * @return bool
     */
    public function is_activity_pending() {

        $now = time();

        if ($this->source_activity->has_grade && $this->has_grade()) {
            // se a atividade possui nota habilitado e possui nota
            // a atividade não está pendente
            return false;
        } else if ($this->source_activity->has_submission && $this->has_submitted()) {
            // se a atividade precisa que seja enviado e foi feito um envio
            // a atividade não está pendente
            return false;
        } else if (!$this->source_activity->has_submission && !$this->has_grade() && $this->source_activity->deadline > $now) {
            // se a atividade não possui envio, não possui nota enviada
            // e ainda não chegou a data esperada de entrega,
            // a atividade não está pendente
            return false;
        }

        return true;
    }

    /**
     * Verifica se o usuário é membro do grupo da atividade
     *
     *
     * @param $agrupamentos_membros array Membros por grouping, course_id e user_id
     * @see grade_due_days()
     * @return bool
     */
    public function is_member_of($agrupamentos_membros) {
        $a_grouping = $this->source_activity->grouping;

        // Se atividade não for agrupada (grouping == "0") então todos os estudantes são membros
        $is_member = $a_grouping == "0" ? true : false;

        if (!$is_member && array_key_exists($a_grouping, $agrupamentos_membros)) {
            // Se atividade for agrupada pesquisa o usuário no grupo da atividade
            $a_user = $this->userid;
            $a_course = $this->source_activity->course_id;
            $a_groupings = $agrupamentos_membros[$a_grouping];

            if (array_key_exists($a_course, $a_groupings)) {
                $a_courses = $a_groupings[$a_course];
                $is_member = array_key_exists($a_user, $a_courses);
            }
        }
        return $is_member;
    }

    /**
     * Retorna se está em atraso ou se foi entregue em atraso (no caso de dados históricos)
     *
     * As seguintes convenções estão sendo adotadas:
     *
     * - atividades sem prazo, não estão em atraso
     * - um dado é considerado histórico quando houver uma data de envio
     *
     * @return bool false se não houver prazo ou se não extiver em atraso e true se estiver em atraso
     */
    public function is_submission_due() {
        // Se não existe um prazo ou se o envio não está habilitado, não está em atraso
        if (!$this->source_activity->has_deadline() || !$this->source_activity->has_submission) {
            return false;
        }

        if ($this->has_submitted()) {
            // Se foi enviada, o atraso será relacionado a um dado histórico
            // Usaremos a diferença entre a data de envio e a data esperada
            return $this->source_activity->deadline < $this->submission_date;
        } else {
            // Se não foi enviada, o atraso será relacionado a um dado atual
            // Usaremos a diferença entre a atual e a data esperada
            return $this->source_activity->deadline < time();
        }
    }

    /**
     * Retorna se a atividade é para o futuro, ou seja a data de entrega da atividade é maior
     * que o dia de hoje
     *
     * - Atividade não tem nota
     * - Atividade tem um deadline e este é maior que o dia de hoje
     * - Atividade que possui entrega, esta data é maior que o dia de hoje
     *
     * @return bool
     */
    public function is_a_future_due() {
        $now = time();

        // Se atividade ja tiver nota, mesmo que seja uma atividade futura esta tudo ok
        if ($this->has_grade()) {
            return false;
        }

        // Se a atividade não tem prazo, a atividade é sempre para o futuro
        if (!$this->source_activity->has_deadline()) {
            return true;
        }

        // A data de avaliacao é maior do que a data atual
        if ($this->source_activity->deadline > $now) {
            return true;
        }

        // Se ele nao tiver nota e sua entrega estiver atrasada ou necessita de nota, não é uma atividade pro futuro
        return false;
    }

}

class report_unasus_data_activity extends report_unasus_data {

    public $status;

    public function __construct(report_unasus_activity &$source_activity, $db_model) {

        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->cohort = isset($db_model->cohort) ? $db_model->cohort : null;
        $this->polo = $db_model->polo;
        if (!is_null($db_model->grade) && $db_model->grade != -1) {
            $this->grade = (float) $db_model->grade;
        }
        $this->grademax = $db_model->grademax;
        $this->status = $db_model->status;
        $this->submission_date = (!is_null($db_model->submission_date)) ? $db_model->submission_date : $db_model->submission_modified;
        $this->grade_date = (!is_null($db_model->grade_created)) ? $db_model->grade_created : $db_model->grade_modified;
    }

    /**
     * Houve um envio de atividade?
     *
     * @return bool true se existe um envio ou false caso contrário
     */
    public function has_submitted() {

        // Houve entrega
        if (!empty($this->submission_date)) {
            if ($this->status != 'draft') {
                return true;
            } elseif ($this->has_grade()) {
                return true;
            }
        } else {
            // Se for uma atividade offline, vamos considerar a data de avaliação como entrega
            if (!$this->source_activity->has_submission()) {
                return $this->has_grade();
            }
        }

        return false;
    }

}

class report_unasus_data_forum extends report_unasus_data {

    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->polo = $db_model->polo;
        if (!is_null($db_model->grade) && $db_model->grade != -1) {
            $this->grade = (float) $db_model->grade;
        }
        $this->submission_date = $db_model->submission_date;
        $this->grade_date = $db_model->timemodified;
        $this->grademax = $db_model->grademax;
    }

}

class report_unasus_data_quiz extends report_unasus_data {

    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->polo = $db_model->polo;
        if (!is_null($db_model->grade) && $db_model->grade != -1) {
            $this->grade = (float) $db_model->grade;
        }
        $this->grademax = $db_model->grademax;
        $this->submission_date = $db_model->submission_date;
        $this->grade_date = $db_model->grade_date;
    }

}

class report_unasus_data_lti extends report_unasus_data {

    public $status;
    public $state_date;
    public $grade_tcc;

    private static $submitted_status = array('review', 'draft'); // Revisão e Rascunho
    private static $evaluated_status = array('done'); // Avaliado

    public function __construct($db_model) {

        $this->userid = $db_model->userid;
        $this->cohort = isset($db_model->cohort) ? $db_model->cohort : null;
        $this->polo = $db_model->polo;
        $this->status = $db_model->status;
        $this->state_date = $db_model->state_date;
        $this->grade_tcc = $db_model->grade_tcc;
        $this->grademax = $db_model->grademax;
    }

    public function has_submitted() {
        return !is_null($this->submission_date) && in_array($this->status, self::$submitted_status);
    }

    public function has_submitted_chapters($chapter) {
        return in_array($this->status[$chapter], self::$submitted_status);
    }

    public function has_evaluated_chapters($chapter) {
        return in_array($this->status[$chapter], self::$evaluated_status);
    }

}

class report_unasus_final_grade {

    public $name;
    public $course_id;

    public function __construct($db_model) {
        $this->name = $db_model->itemname;
        $this->course_id = $db_model->course_id;
    }

    public function __toString() {
        $gradebook_url = new moodle_url('/grade/report/grader/index.php', array('id' => $this->course_id, 'target' => '_blank'));
        $text = (is_null($this->name) || $this->name == '') ? 'Média Final ' : $this->name;
        return html_writer::link($gradebook_url, $text, array('target' => '_blank'));
    }

}

class report_unasus_total_atividades_concluidas {

    public $name;
    public $course_id;
    public $total;

    public function __construct($total) {
        $this->total = $total;
    }

    public function __toString() {
        return get_string('atividades_concluidas', 'report_unasus');
    }

}

class report_unasus_data_nota_final extends report_unasus_data {

    public $userid;
    public $polo;
    public $grade;
    public $cohort;

    public function __construct($db_model) {
        $this->userid = $db_model->userid;
        $this->polo = $db_model->polo;
        $this->grademax = $db_model->grademax;
        // FIXME: esse dado não define o cohort, precisa definir.

        if (!is_null($db_model->grade) && $db_model->grade != -1) {
            $this->grade = (float) $db_model->grade;
        }
    }

    /**
     * Modulo possui nota final?
     *
     * @return bool true se tiver ou false se não
     */
    public function has_grade() {
        return !is_null($this->grade);
    }

}

/**
 * Representa um dado relacionado a um estudante que não faz parte da atividad em questão
 *
 * Um exemplo de uso é quando existe algum tipo de separação como agrupamentos, em que o estudante faz parte da disciplina
 * mas não tem acesso a uma determinada atividade.
 *
 * Outro caso de uso é no sistema de TCC onde um estudante pode não fazer parte de um Eixo.
 */
class report_unasus_data_empty extends report_unasus_data {

    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->cohort = isset($db_model->cohort) ? $db_model->cohort : null;
        $this->polo = $db_model->polo;
    }

    public function has_submitted() {
        return false;
    }

    public function has_grade() {
        return false;
    }

    public function submission_due_days() {
        return false;
    }

    public function grade_due_days() {
        return false;
    }

    public function is_grade_needed() {
        return false;
    }

    public function is_submission_due() {
        return false;
    }

    public function is_a_future_due() {
        return false;
    }

}