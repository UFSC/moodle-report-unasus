<?php

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

    abstract function __toString();
}

class report_unasus_assign_activity extends report_unasus_activity {

    public function  __construct($db_model) {

        $has_submission = !$db_model->nosubmissions;
        $has_grade = ((int)$db_model->grade) == 0 ? false : true ;

        parent::__construct($has_submission, $has_grade);

        $this->id = $db_model->assign_id;
        $this->name = $db_model->assign_name;
        $this->deadline = $db_model->duedate;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
    }

    public function __toString() {
        $cm = get_coursemodule_from_instance('assign', $this->id, $this->course_id, null, MUST_EXIST);
        $atividade_url = new moodle_url('/mod/assign/view.php', array('id' => $cm->id));
        return html_writer::link($atividade_url, $this->name);
    }
}

class report_unasus_forum_activity extends report_unasus_activity {

    public function  __construct($db_model) {
        parent::__construct(true, true);

        $this->id = $db_model->forum_id;
        $this->name = $db_model->forum_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
    }

    public function __toString() {
        $cm = get_coursemodule_from_instance('forum', $this->id, $this->course_id, null, MUST_EXIST);
        $forum_url = new moodle_url('/mod/forum/view.php', array('id' => $cm->id));
        return html_writer::link($forum_url, $this->name);
    }
}

abstract class report_unasus_data {

    public $source_activity;
    public $userid;
    public $grade;
    public $submission_date;
    public $grade_date;

    /**
     * @param report_unasus_activity $source_activity qual a atividade esta informação se refere
     */
    public function  __construct(report_unasus_activity &$source_activity) {
        $this->source_activity = $source_activity;
    }

    /**
     * Houve um envio de atividade?
     * @return bool true se existe um envio ou false caso contrário
     */
    public function has_submitted() {
        return !is_null($this->submission_date);
    }

    /**
     * Atividade possui nota?
     * @return bool true se tiver ou false se não
     */
    public function has_grade() {
        return !is_null($this->grade) && !is_null($this->grade_date);
    }

    /**
     * Retorna os dias de atraso em relação a entrega de atividades
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

        return (int)$duediff->format("%a");
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
            $duediff = $deadline->diff($this->grade_date);
        } else {
            // se não possui nota, o atraso é relacionado a um dado atual
            // usaremos a diferença do deadline com a data atual
            $duediff = $deadline->diff(date_create());
        }
        return (int)$duediff->format("%a");
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
            // se a atividade possui envio e não foi feito um envio
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
}

class report_unasus_data_activity extends report_unasus_data{

    public function  __construct(report_unasus_activity &$source_activity, $db_model) {

        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        if (!is_null($db_model->grade) && $db_model->grade != -1) {
            $this->grade = (float)$db_model->grade;
        }
        $this->submission_date = ( !is_null($db_model->submission_date) ) ? $db_model->submission_date : $db_model->submission_modified;
        $this->grade_date = ( !is_null($db_model->grade_created) ) ? $db_model->grade_created : $db_model->grade_modified;
    }

}

class report_unasus_data_forum extends report_unasus_data{

    public function  __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        if (!is_null($db_model->grade) && $db_model->grade != -1) {
            $this->grade = (float)$db_model->grade;
        }
        $this->submission_date = $db_model->submission_date;
        $this->grade_date = $db_model->timemodified;
    }

}

