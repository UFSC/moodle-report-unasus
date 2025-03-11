<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Representa uma atividade (tarefa, forum, quiz)
 *
 * @property $deadline
 */
abstract class report_unasus_activity {

    const MAX_NAME_LENGTH = 22;

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

    /**
     * Monta a forma de apresentação do nome da atividade padrão
     *
     * @return string Contendo o nome da atividade devidamente formatado
     */
    protected function formatted_name() {
//        $initial_name = explode(' ', substr($this->name, 0, 8))[0];
//
//        $final_name = trim(substr(substr($this->name, 8),-7));
//        $final_name = (strlen($final_name) == 0) ? '' : "<br>...$final_name";

//        $initial_name = $this->name;
        $initial_name = substr($this->name, 0, self::MAX_NAME_LENGTH);
        $final_name = '';


        return $initial_name.$final_name;
    }

    abstract function __toString();
}

abstract class report_unasus_activity_config {

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
}

class report_unasus_assign_activity_report_config {

    public function __construct($db_model) {

        $this->id = $db_model->assign_id;
        $this->name = $db_model->assign_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
    }
}
class report_unasus_generic_activity_report_config {

    public function __construct($db_model) {

        $this->id = $db_model->activity_id;
        $this->name = $db_model->activity_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
    }
}
class report_unasus_generic_activity extends report_unasus_activity {

    public function __construct($db_model) {

        $has_submission = !$db_model->nosubmissions;
        $has_grade = ((int) $db_model->grade) == 0 ? false : true;

        parent::__construct($has_submission, $has_grade);

        $this->id = $db_model->activity_id;
        $this->name = $db_model->activity_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->grouping = $db_model->grouping_id;
        $this->coursemoduleid = $db_model->coursemoduleid;
    }

    public function __toString() {

        $name = $this->formatted_name();
        $atividade_url = new moodle_url('/mod/'.$this->module_name.'/view.php', array('id' => $this->coursemoduleid, 'target' => '_blank'));
        return html_writer::link($atividade_url, $name, array('target' => '_blank'));
    }
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
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->grouping = $db_model->grouping_id;
        $this->coursemoduleid = $db_model->coursemoduleid;
    }

    public function __toString() {

        $name = $this->formatted_name();
        $atividade_url = new moodle_url('/mod/assign/view.php', array('id' => $this->coursemoduleid, 'target' => '_blank'));
        return html_writer::link($atividade_url, $name, array('target' => '_blank'));
    }
}

class report_unasus_forum_activity_report_config {

    public function __construct($db_model) {

        $this->id = $db_model->forum_id;
        $this->name = $db_model->forum_name;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
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
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->grouping = $db_model->grouping_id;
        $this->coursemoduleid = $db_model->coursemoduleid;
    }

    public function __toString() {
        $name = $this->formatted_name();
        $forum_url = new moodle_url('/mod/forum/view.php', array('id' => $this->coursemoduleid, 'target' => '_blank'));
        return html_writer::link($forum_url, $name, array('target' => '_blank'));
    }

}

class report_unasus_quiz_activity_report_config {

    public function __construct($db_model) {

        $this->id = $db_model->quiz_id;
        $this->name = $db_model->quiz_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
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
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->grouping = $db_model->grouping_id;
        $this->coursemoduleid = $db_model->coursemoduleid;
    }

    public function __toString() {
        switch (strtolower($this->name)) {
            case 'questões avaliativas - enfermeiros':
                $name = 'Q.Enf.';
                break;
            case 'questões avaliativas - médicos':
                $name = 'Q.Méd.';
                break;
            case 'questões avaliativas - dentistas':
                $name = 'Q.Dent.';
                break;
            default:
                $name = $this->formatted_name();


        }

        $quiz_url = new moodle_url('/mod/quiz/view.php', array('id' => $this->coursemoduleid, 'target' => '_blank'));
        return html_writer::link($quiz_url, $name, array('target' => '_blank'));
    }

}

class report_unasus_db_activity_report_config {

    public function __construct($db_model) {

        $this->id = $db_model->database_id;
        $this->name = $db_model->database_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
    }
}

class report_unasus_db_activity extends report_unasus_activity {

    public function __construct($db_model) {

        parent::__construct(true, true);
        $this->id = $db_model->database_id;
        $this->name = $db_model->database_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->grouping = $db_model->grouping_id;
        $this->coursemoduleid = $db_model->coursemoduleid;
    }

    public function __toString() {
        $name = $this->formatted_name();
        $db_url = new moodle_url('/mod/data/view.php', array('id' => $this->coursemoduleid, 'target' => '_blank'));
        return html_writer::link($db_url, $name, array('target' => '_blank'));
    }

}

class report_unasus_scorm_activity_report_config {

    public function __construct($db_model) {

        $this->id = $db_model->scorm_id;
        $this->name = $db_model->scorm_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
    }
}

class report_unasus_scorm_activity extends report_unasus_activity {

    public function __construct($db_model) {

        parent::__construct(false, true);
        $this->id = $db_model->scorm_id;
        $this->name = $db_model->scorm_name;
        $this->deadline = $db_model->completionexpected;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->grouping = $db_model->grouping_id;
        $this->coursemoduleid = $db_model->coursemoduleid;
    }

    public function __toString() {
        $name = $this->formatted_name();
        $scorm_url = new moodle_url('/mod/scorm/view.php', array('id' => $this->coursemoduleid, 'target' => '_blank'));
        return html_writer::link($scorm_url, $name, array('target' => '_blank'));
    }

}

class report_unasus_lti_activity_report_config {

    public function __construct($db_model) {

        $this->id = $db_model->lti_id;
        $this->name = $db_model->name;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
    }
}

class report_unasus_lti_activity extends report_unasus_activity {

    public $position;
    public $coursemoduleid;
    public $baseurl;
    public $consumer_key;
    public $custom_parameters;

    public function __construct($db_model) {
        parent::__construct(false, true); //$has_submission, $has_grade
        $this->id = $db_model->lti_id;
        $this->name = $db_model->name;
        $this->deadline = $db_model->completionexpected;
        $this->position = isset($db_model->position) ? $db_model->position : null;
        $this->course_id = $db_model->course_id;
        $this->course_name = $db_model->course_name;
        $this->coursemoduleid = $db_model->coursemoduleid;
        $this->module_id = $db_model->module_id;
        $this->module_name = $db_model->module_name;
        $this->baseurl = isset($db_model->baseurl) ? $db_model->baseurl : null;
        $this->consumer_key = isset($db_model->consumer_key) ? $db_model->consumer_key : null;
        $this->grouping = $db_model->grouping_id;
        $this->custom_parameters = isset($db_model->custom_parameters) ? $db_model->custom_parameters : null;
    }

    /**
     * @todo verificar parametro 'lti', tá vindo vazio e dado erro na linha seguinte $cm->id
     */
    public function __toString() {
        $name = $this->formatted_name();
        $lti_url = new moodle_url('/mod/lti/view.php', array('id' => $this->coursemoduleid, 'target' => '_blank'));
        return html_writer::link($lti_url, $name, array('target' => '_blank'));
    }

    public function has_submission() {
        return (!empty($this->has_submission));
    }

}


class report_unasus_lti_activity2 extends report_unasus_generic_activity {

    public $position;
    public $coursemoduleid;
    public $baseurl;
    public $consumer_key;
    public $custom_parameters;

    public function __construct($db_model) {
        parent::__construct($db_model);
        $this->position = isset($db_model->position) ? $db_model->position : null;
        $this->baseurl = isset($db_model->baseurl) ? $db_model->baseurl : null;
        $this->consumer_key = isset($db_model->consumer_key) ? $db_model->consumer_key : null;
        $this->custom_parameters = isset($db_model->custom_parameters) ? $db_model->custom_parameters : null;
    }

}

class report_unasus_lti_activity_tcc extends report_unasus_lti_activity{

    public $tcc_definition;

    public function __construct($db_model, $tcc_definition) {
        parent::__construct($db_model);
        $this->tcc_definition = $tcc_definition;
    }
}

class report_unasus_lti_activity_tcc2 extends report_unasus_lti_activity2{

    public $tcc_definition;

    public function __construct($db_model, $tcc_definition) {
        parent::__construct($db_model);
        $this->tcc_definition = $tcc_definition;
    }
}

class report_unasus_chapter_tcc_activity extends report_unasus_activity {

    public $position;
    public $created_at;
    public $updated_at;
    public $source_activity;

    public function __construct($db_model, $source_activity) {
        parent::__construct(false, false); //$has_submission, $has_grade
        $this->id = $db_model->id;
        $this->name = $db_model->title;
        $this->position = $db_model->position;
        $this->created_at = $db_model->created_at;
        $this->updated_at = $db_model->updated_at;
        $this->source_activity = $source_activity;
    }

    public function __toString() {
        $name = $this->formatted_name();
        return html_writer::label(substr($this->source_activity->name.' - '.$name, 0, self::MAX_NAME_LENGTH),
            null, false, array('class' => 'c_body'));
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
     * Data final de entrega da atividade
     *
     * @return bool true se tiver ou false se não
     */
    public function deadline_date() {
        $deadline_date = false;
        if ($this->source_activity->deadline > 0) {
            $deadline_date = report_unasus_get_datetime_from_unixtime($this->source_activity->deadline);
        }
        return $deadline_date;
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

        $deadline = report_unasus_get_datetime_from_unixtime($this->source_activity->deadline);

        if ($this->has_submitted()) {
            // se foi enviada, o atraso será relacionado a um dado histórico
            // usaremos a diferença entre a data de envio e a data esperada
            $duediff = $deadline->diff(report_unasus_get_datetime_from_unixtime($this->submission_date));
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
//
//        if (!$this->is_grade_needed()) {
//            return false;
//        }

        if (  ($this->source_activity->has_submission) &&
              (!empty($this->submission_date)) &&
              ($this->submission_date > 0) ) {
            // se a atividade possui entrega ativada
            // o prazo é contato a partir da data de envio
            $deadline = report_unasus_get_datetime_from_unixtime($this->submission_date);
        } else {
            // se a atividade não possui entrega ativada
            // o prazo é contato a partir da data esperada de entrega
            $deadline = report_unasus_get_datetime_from_unixtime($this->source_activity->deadline);
        }

        if ($this->has_grade()) {
            // se possui nota, o atraso é relacionado a um dado histórico
            // usaremos a diferença do deadline com a data de envio da nota

            $grade_datetime = report_unasus_get_datetime_from_unixtime($this->grade_date);
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
        $result = true;
        if (!$this->source_activity->has_grade) {
            // se a atividade não possui nota habilitado
            // não é necessário enviar a nota
            $result = false;
        } elseif ($this->source_activity->has_submission && !$this->has_submitted()) {
            // se a atividade precisa que seja enviado e não foi feito um envio
            // não é necessário enviar uma nota
            $result = false;
        } elseif (!$this->source_activity->has_submission && !$this->has_grade() && $this->source_activity->deadline > $now) {
            // se a atividade não possui envio, não possui nota enviada
            // e ainda não chegou a data esperada de entrega,
            // não é necessário enviar uma nota
            $result = false;
        } elseif (!$this->source_activity->has_submission && !$this->has_grade() && $this->source_activity->deadline == 0) {
            // se a atividade não possui envio, não possui nota enviada
            // e não tem tem prazo de entrega,
            // não é necessário enviar uma nota
            $result = false;
        }

        return $result;
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
            return ( ($this->source_activity->deadline > 0) &&
                     ($this->source_activity->deadline < time()) );
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
        $grade = isset($db_model->grade) ? $db_model->grade : null;
        if (!is_null($grade) && $grade != -1) {
            $this->grade = (float) $grade;
        }
        $this->grademax = isset($db_model->grademax) ? $db_model->grademax : null;
        $this->status = $db_model->status;
//        $this->submission_date = (!is_null($db_model->submission_date)) ? $db_model->submission_date : $db_model->submission_modified;
        $this->submission_date = ($this->status == 'submitted') ? $db_model->submission_modified : null;
        $this->grade_date = isset($db_model->grade_created) ? $db_model->grade_created : $db_model->grade_modified;
    }

    /**
     * Houve um envio de atividade?
     *
     * @return bool true se existe um envio ou false caso contrário
     */
    public function has_submitted() {

        // Houve entrega
        if (!empty($this->submission_date)) {
            // se não for novo ou rascunho, antão enviou
            if ( !in_array($this->status, array("new", "draft")) ) {
                return true;
            // mesmo em rascunho, se tiver nota, considera como submetido
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
        $grade = isset($db_model->grade) ? $db_model->grade : null;
        if (!is_null($grade) && $grade != -1) {
            $this->grade = (float) $grade;
        }
        $this->submission_date = isset($db_model->submission_date) ? $db_model->submission_date : null;
        $this->grade_date = isset($db_model->timemodified) ? $db_model->timemodified : 0 ;
        $this->grademax = isset($db_model->grademax) ? $db_model->grademax : null;
    }

    /**
     * Houve um envio de atividade?
     *
     * @return bool true se existe um envio ou false caso contrário
     */
    public function has_submitted() {

        // Houve entrega
        if (!empty($this->submission_date)) {
            return true;
        } else {
            // Se for uma atividade offline, vamos considerar a data de avaliação como entrega
            if (!$this->source_activity->has_submission()) {
                return $this->has_grade();
            }
        }

        return false;
    }

}

class report_unasus_data_quiz extends report_unasus_data {

    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->polo = $db_model->polo;
        $grade = isset($db_model->grade) ? $db_model->grade : null;
        if (!is_null($grade) && $grade != -1) {
            $this->grade = (float) $grade;
        }
        $this->grademax = isset($db_model->grademax) ? $db_model->grademax : null;
        $this->submission_date = isset($db_model->submission_date) ? $db_model->submission_date : null;
        $this->grade_date = isset($db_model->grade_date) ? $db_model->grade_date : null;

    }

}

class report_unasus_data_db extends report_unasus_data {

    public $databaseid;
    public $grademax;
    public $itemname;

    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->databaseid = isset($db_model->databaseid) ? $db_model->databaseid : null;
        $grade = isset($db_model->grade) ? $db_model->grade : null;
        if (!is_null($grade) && $grade != -1) {
            $this->grade = (float) $grade;
        }
        $this->grademax = isset($db_model->grademax) ? $db_model->grademax : null;
        $this->itemname = isset($db_model->itemname) ? $db_model->itemname : null;
        $this->submission_date = isset($db_model->submission_date) ? $db_model->submission_date : null;
        $this->grade_date = isset($db_model->grade_date) ? $db_model->grade_date : null;

    }

}

class report_unasus_data_scorm extends report_unasus_data {

    public $scormid;
    public $grademax;
    public $itemname;
    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->scormid = $db_model->scormid;
        $grade = isset($db_model->grade) ? $db_model->grade : null;
        if (!is_null($grade) && $grade != -1) {
            $this->grade = (float) $grade;
        }
        $this->grademax = isset($db_model->grademax) ? $db_model->grademax : null;
        $this->itemname = isset($db_model->itemname) ? $db_model->itemname : null;
        $this->submission_date = isset($db_model->submission_date) ? $db_model->submission_date : null;
    }

    public function has_grade() {
        return !is_null($this->grade);
    }

//    public function is_grade_needed() {
//        return parent::is_grade_needed();
//    }

    public function has_submitted() {
        return $this->submission_date && $this->has_grade() && $this->grade == $this->grademax;
    }

}


class report_unasus_data_lti extends report_unasus_data {

    public $lti_id;
    public $grademax;
    public $itemname;

    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity);

        $this->userid = $db_model->userid;
        $this->lti_id = isset($db_model->lti_id) ? $db_model->lti_id : null;
        $grade = isset($db_model->grade) ? $db_model->grade : null;
        if (!is_null($grade) && $grade != -1) {
            $this->grade = (float) $grade;
        }
        $this->grademax = isset($db_model->grademax) ? $db_model->grademax : null;
        $this->itemname = isset($db_model->itemname) ? $db_model->itemname : null;
        $this->submission_date = isset($db_model->submission_date) ? $db_model->submission_date : null;
        $this->grade_date = isset($db_model->submission_date) ? $db_model->submission_date : null;
    }

    public function has_grade() {
        return !is_null($this->grade);
    }

//    public function is_grade_needed() {
//        return parent::is_grade_needed();
//    }

    public function has_submitted() {
//        return parent::is_grade_needed();
        return (!is_null($this->grade) or !is_null($this->submission_date));
//
    }


}

class report_unasus_data_lti_tcc extends report_unasus_data_lti {

    public $status;
    public $state_date;
    public $grade_tcc;
    public $coursemoduleid;
    public $grademax;

    private static $submitted_status = array('review', 'draft'); // Revisão e Rascunho
    private static $evaluated_status = array('done'); // Avaliado

    public function __construct(report_unasus_activity &$source_activity, $db_model) {
        parent::__construct($source_activity, $db_model);
        $this->userid = $db_model->userid;
        $this->coursemoduleid = $db_model->coursemoduleid;
        $this->cohort = isset($db_model->cohort) ? $db_model->cohort : null;
        $this->polo = $db_model->polo;
        $this->status = $db_model->status;
        $this->state_date = $db_model->state_date;
        $this->grade_tcc = $db_model->grade_tcc;
        $this->grademax = isset($db_model->grademax) ? $db_model->grademax : null;
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
        $text = (is_null($this->name) || $this->name == '') ? 'M.Final ' : $this->name;
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
