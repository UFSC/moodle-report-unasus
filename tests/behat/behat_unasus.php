<?php

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Behat\Exception\PendingException as PendingException;

class behat_unasus extends behat_base
{
    protected $relationshipcount = 0;
    protected $relationship_groupscount = 0;
    protected $relationship_memberscount = 0;
    protected $relationships = array();
    public $loremipsum = <<<EOD
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nulla non arcu lacinia neque faucibus fringilla. Vivamus porttitor turpis ac leo. Integer in sapien. Nullam eget nisl. Aliquam erat volutpat. Cras elementum. Mauris suscipit, ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Integer malesuada. Nullam lectus justo, vulputate eget mollis sed, tempor sed magna. Mauris elementum mauris vitae tortor. Aliquam erat volutpat.
Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Pellentesque ipsum. Cras pede libero, dapibus nec, pretium sit amet, tempor quis. Aliquam ante. Proin in tellus sit amet nibh dignissim sagittis. Vivamus porttitor turpis ac leo. Duis bibendum, lectus ut viverra rhoncus, dolor nunc faucibus libero, eget facilisis enim ipsum id lacus. In sem justo, commodo ut, suscipit at, pharetra vitae, orci. Aliquam erat volutpat. Nulla est.
Vivamus luctus egestas leo. Aenean fermentum risus id tortor. Mauris dictum facilisis augue. Aliquam erat volutpat. Aliquam ornare wisi eu metus. Aliquam id dolor. Duis condimentum augue id magna semper rutrum. Donec iaculis gravida nulla. Pellentesque ipsum. Etiam dictum tincidunt diam. Quisque tincidunt scelerisque libero. Etiam egestas wisi a erat.
Integer lacinia. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris tincidunt sem sed arcu. Nullam feugiat, turpis at pulvinar vulputate, erat libero tristique tellus, nec bibendum odio risus sit amet ante. Aliquam id dolor. Maecenas sollicitudin. Et harum quidem rerum facilis est et expedita distinctio. Mauris suscipit, ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Nullam dapibus fermentum ipsum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Pellentesque sapien. Duis risus. Mauris elementum mauris vitae tortor. Suspendisse nisl. Integer rutrum, orci vestibulum ullamcorper ultricies, lacus quam ultricies odio, vitae placerat pede sem sit amet enim.
In laoreet, magna id viverra tincidunt, sem odio bibendum justo, vel imperdiet sapien wisi sed libero. Proin pede metus, vulputate nec, fermentum fringilla, vehicula vitae, justo. Nullam justo enim, consectetuer nec, ullamcorper ac, vestibulum in, elit. Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? Maecenas lorem. Etiam posuere lacus quis dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Curabitur ligula sapien, pulvinar a vestibulum quis, facilisis vel sapien. Nam sed tellus id magna elementum tincidunt. Suspendisse nisl. Vivamus luctus egestas leo. Nulla non arcu lacinia neque faucibus fringilla. Etiam dui sem, fermentum vitae, sagittis id, malesuada in, quam. Etiam dictum tincidunt diam. Etiam commodo dui eget wisi. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Proin pede metus, vulputate nec, fermentum fringilla, vehicula vitae, justo. Duis ante orci, molestie vitae vehicula venenatis, tincidunt ac pede. Pellentesque sapien.
EOD;

    /**
     * Each element specifies:
     * - The data generator sufix used.
     * - The required fields.
     * - The mapping between other elements references and database field names.
     * @var array
     */
    protected static $elements = array(
        'relationships' => array(
            'datagenerator' => 'relationship',
            'required' => array('name'),
            'switchids' => array('category' => 'contextid')
        ),

        'relationship_groups' => array(
            'datagenerator' => 'relationship_groups',
            'required' => array('name'),
            'switchids' => array('relationship' => 'relationshipid')
        ),

        'relationship_members'=> array(
            'datagenerator' => 'relationship_members',
            'required' => array('user', 'group')
        ),
        'assigns'=> array(
            'datagenerator' => 'assign',
            'required' => array('course', 'idnumber', 'name')
        )
    );

    /**
     * @Given /^the following relationship "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     * @throws Exception
     * @throws PendingException
     */
    public function the_Following_Relationship_Exist($elementname, TableNode $data)
    {
        $this->_process_elements($elementname, $data);
    }

    /**
     * @Given /^the following relationship group "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     * @throws Exception
     * @throws PendingException
     */
    public function the_Following_Relationship_Groups_Exist($elementname, TableNode $data)
    {
        $this->_process_elements($elementname, $data);
    }

    /**
     * @Given /^the following users belongs to the relationship group as "(?P<element_string>(?:[^"]|\\")*)":$/
     * @throws Exception
     * @throws PendingException
     */
    public function the_Following_Users_Belongs_Relationship_Members($elementname, TableNode $data)
    {
        $this->_process_elements($elementname, $data);
    }

    /**
     * @Given /^the following activity "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     * @throws Exception
     * @throws PendingException
     */
    public function the_Following_Assign_Exist($elementname, TableNode $data)
    {
        $this->_process_elements($elementname, $data);
    }

    /**
     * Shared implementation for all "the following X exist" step definitions.
     * Handles required field validation, switchids resolution, preprocessing, and creation.
     *
     * @param string    $elementname
     * @param TableNode $data
     * @throws Exception
     * @throws PendingException
     */
    private function _process_elements($elementname, TableNode $data)
    {
        $elementdatagenerator = self::$elements[$elementname]['datagenerator'];
        $requiredfields = self::$elements[$elementname]['required'];
        $switchids = null;
        if (!empty(self::$elements[$elementname]['switchids'])) {
            $switchids = self::$elements[$elementname]['switchids'];
        }

        foreach ($data->getHash() as $elementdata) {

            // Check if all the required fields are there.
            foreach ($requiredfields as $requiredfield) {
                if (!isset($elementdata[$requiredfield])) {
                    throw new Exception($elementname . ' requires the field ' . $requiredfield . ' to be specified');
                }
            }

            // Switch from human-friendly references to ids.
            if (isset($switchids)) {
                foreach ($switchids as $element => $field) {
                    $methodname = 'get_' . $element . '_id';

                    // Not all the switch fields are required, default vars will be assigned by data generators.
                    if (isset($elementdata[$element])) {
                        // Temp $id var to avoid problems when $element == $field.
                        $id = $this->{$methodname}($elementdata[$element]);
                        unset($elementdata[$element]);
                        $elementdata[$field] = $id;
                    }
                }
            }

            // Preprocess the entities that requires a special treatment.
            if (method_exists($this, 'preprocess_' . $elementdatagenerator)) {
                $elementdata = $this->{'preprocess_' . $elementdatagenerator}($elementdata);
            }

            // Creates element.
            $methodname = 'create_' . $elementdatagenerator;
            if (method_exists($this, $methodname)) {
                // Using data generators directly.
                $this->{$methodname}($elementdata);
            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);
            } else {
                throw new PendingException($elementname . ' data generator is not implemented');
            }
        }
    }

    /**
     * Create a test relationship
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass relationship record
     */
    public function create_relationship($record = null, array $options=null) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/local/relationship/lib.php");

        $this->relationshipcount++;
        $i = $this->relationshipcount;

        $record = (array)$record;

        if (!isset($record['contextid'])) {
            $record['contextid'] = context_system::instance()->id;
        }

        if (!isset($record['name'])) {
            $record['name'] = 'Relationship ' . $i;
        }

        if (!isset($record['idnumber'])) {
            $record['idnumber'] = '';
        }

        if (!isset($record['description'])) {
            $record['description'] = "Test relationship $i\n$this->loremipsum";
        }

        if (!isset($record['descriptionformat'])) {
            $record['descriptionformat'] = FORMAT_MOODLE;
        }

        if (!isset($record['component'])) {
            $record['component'] = '';
        }

        $id = relationship_add_relationship((object)$record);

        $relationship = $DB->get_record('relationship', array('id' => $id), '*', MUST_EXIST);
        $this->relationships[$relationship->name] = $relationship->id;
        return $relationship;
    }

    /**
     * Get the context id of a category given its idnumber.
     * @param string $idnumber
     * @return int
     * @throws Exception
     */
    public function get_category_id($idnumber) {
        global $DB;
        $category = $DB->get_record('course_categories', array('idnumber' => $idnumber), 'id', MUST_EXIST);
        $context = context_coursecat::instance($category->id);
        return $context->id;
    }

    /**
     * Get the id of a relationship given its name.
     * @param string $name
     * @return int
     * @throws Exception
     */
    public function get_relationship_id($name) {
        if (!isset($this->relationships[$name])) {
            throw new Exception('The relationship "' . $name . '" was not found. Ensure it was created before the relationship group.');
        }
        return $this->relationships[$name];
    }

    /**
     * Create a test relationship_groups
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass relationship_groups record
     */
    public function create_relationship_groups($record = null, array $options=null) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/local/relationship/lib.php");

        $this->relationship_groupscount++;
        $i = $this->relationship_groupscount;

        $record = (array)$record;

        if (!isset($record['relationshipid'])) {
            $record['relationshipid'] = context_system::instance()->id;
        }

        if (!isset($record['name'])) {
            $record['name'] = 'Relationship_Group ' . $i;
        }

        if (!isset($record['userlimit'])) {
            $record['userlimit'] = 0;
        }

        if (!isset($record['uniformdistribution'])) {
            $record['uniformdistribution'] = 0;
        }

        $id = relationship_add_group((object)$record);

        return $DB->get_record('relationship_groups', array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * Create a test relationship_members
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass relationship_members record
     */
    public function create_relationship_members($record = null, array $options=null) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/local/relationship/lib.php");

        $this->relationship_memberscount++;

        $record = (array)$record;

        if (!isset($record['relationshipgroupid'])) {
            $sql = "SELECT rg.id
                    FROM {relationship_groups} rg
                    WHERE name = :name";

            $record['relationshipgroupid'] = $DB->get_field_sql($sql, array('name' => $record['group']));
        }

        if (!isset($record['relationshipcohortid'])) {
            $sql = "SELECT rc.id
                    FROM {relationship_cohorts} rc
                    WHERE rc.cohortid = (SELECT cm.cohortid
                                        FROM {cohort_members} cm
                                        WHERE userid = (SELECT u.id
                                                        FROM {user} u
                                                        WHERE u.username = :username))";

            $record['relationshipcohortid'] = $DB->get_field_sql($sql, array('username' => $record['user']));
        }

        if (!isset($record['userid'])) {
            $record['userid'] = $DB->get_field('user', 'id', array('username' => $record['user']), MUST_EXIST);
        }

        $id = relationship_add_member($record['relationshipgroupid'],
                                      $record['relationshipcohortid'],
                                      $record['userid']);

        return $DB->get_record('relationship_members', array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * Adiciona uma tag no relationship.
     *
     * @Given /^instance the tag "([^"]*)" at relationship "([^"]*)"$/
     */
    public function instance_the_tag_at_relationship($tag, $relationship) {
        global $DB;

        $adminid = $DB->get_field('user', 'id', array('username' => 'admin'), MUST_EXIST);

        $record = new stdClass();
        $record->userid = $adminid;
        $record->name = $tag;
        $record->rawname = $tag;
        $record->tagtype = 'default';
        $record->description = null;
        $record->descriptionformat = 0;
        $record->flag = 0;
        $record->timemodified = time();

        $DB->insert_record('tag', $record);

        $sql = "SELECT tag.id as tagid, relationship.id as relationshipid
                FROM {tag} tag, {relationship} relationship
                WHERE tag.name = :tag";

        $id = $DB->get_record_sql($sql, array('tag' => $tag));

        $record2 = new stdClass();
        $record2->tagid = $id->tagid;
        $record2->component = null;
        $record2->itemtype = 'relationship';
        $record2->itemid = $id->relationshipid;
        $record2->contextid = null;
        $record2->tiuserid = 0;
        $record2->ordering = 0;
        $record2->timecreated = time();
        $record2->timemodified = time();

        $DB->insert_record('tag_instance', $record2);
    }

    /**
     * Adiciona os cohorts criados no relationship.
     *
     * @Given /^add created cohorts at relationship "([^"]*)" on relationship_groups "([^"]*)"$/
     */
    public function add_created_cohorts_at_relationship($relationship, $relationship_group)
    {
        global $DB;

        $sql_relationship = "SELECT relationship.id
                             FROM {relationship} relationship
                             WHERE relationship.name = :relationship";

        $relationshipId = $DB->get_field_sql($sql_relationship, array('relationship' => $relationship));

        $sql = "SELECT DISTINCT role_assignments.userid as id, role_assignments.roleid, cohort.id as cohortid
                FROM {cohort} cohort, {role_assignments} role_assignments
                LEFT JOIN {cohort_members} cohort_members
                ON role_assignments.userid = cohort_members.userid
                WHERE cohort_members.cohortid = cohort.id";

        $sql_result = $DB->get_records_sql($sql);

        foreach ($sql_result as $id) {

            $sql_roleid = "SELECT COUNT(1)
                           FROM {relationship_cohorts} rc
                           WHERE rc.roleid = :roleid";

            $roleid = $DB->get_field_sql($sql_roleid, array('roleid' => $id->roleid));

            if ($roleid == 0) {
                $record = new stdClass();
                $record->relationshipid = $relationshipId;
                $record->roleid = $id->roleid;
                $record->cohortid = $id->cohortid;
                $record->allowdupsingroups = 0;
                $record->uniformdistribution = 0;
                $record->timecreated = time();
                $record->timemodified = time();

                $DB->insert_record('relationship_cohorts', $record);
            }
        }
    }

    /**
     * Adds the user to the specified cohort.
     * Obs: Ao adicionar o usuário ao grupo de cohort, automaticamente é adicionado o usuário
     * aos respectivos grupos de tutoria.
     *
     * @Given /^I add the user "([^"]*)" +with cohort "([^"]*)" to cohort members$/
     * @param string $user
     * @param string $cohort
     */
    public function i_add_user_to_cohort_members($user, $cohort) {
        global $DB;

        $sql_config = "SELECT COUNT(1)
                       FROM {config} config
                       WHERE config.name = 'local_tutores_student_roles'";

        $config = $DB->get_field_sql($sql_config);

        if ($config == 0) {
            set_config('local_tutores_student_roles', 'student');
            set_config('local_tutores_tutor_roles', 'editingteacher');
            set_config('local_tutores_orientador_roles', 'editingteacher');
        }

        $userId = $DB->get_field('user', 'id', array('username' => $user), MUST_EXIST);
        $cohortId = $DB->get_field('cohort', 'id', array('name' => 'Cohort ' . $cohort), MUST_EXIST);

        $record = new stdClass();
        $record->cohortid = $cohortId;
        $record->userid = $userId;
        $record->timeadded = time();

        $DB->insert_record('cohort_members', $record);

        if ($cohort == 'student') {
            $record_student_log = new stdClass();
            $record_student_log->userid = $userId;
            $record_student_log->timemodified = time();
            $record_student_log->plugin = null;
            $record_student_log->name = 'local_tutores_student_roles';
            $record_student_log->value = $cohort;

            $DB->insert_record('config_log', $record_student_log);
        }

        if ($cohort == 'teacher') {
            $record_teacher_log = new stdClass();
            $record_teacher_log->userid = $userId;
            $record_teacher_log->timemodified = time();
            $record_teacher_log->plugin = null;
            $record_teacher_log->name = 'local_tutores_tutor_roles';
            $record_teacher_log->value = 'editing' . $cohort;

            $DB->insert_record('config_log', $record_teacher_log);

            $record_editingteacher_log = new stdClass();
            $record_editingteacher_log->userid = $userId;
            $record_editingteacher_log->timemodified = time();
            $record_editingteacher_log->plugin = null;
            $record_editingteacher_log->name = 'local_tutores_orientador_roles';
            $record_editingteacher_log->value = 'editing' . $cohort;

            $DB->insert_record('config_log', $record_editingteacher_log);
        }
    }

    /**
     * Sets a grade for a user on an assign activity directly in the database.
     *
     * @Given /^I set the grade of activity "([^"]*)" for user "([^"]*)" to "([^"]*)"$/
     * @param string $idnumber
     * @param string $username
     * @param string $grade
     */
    public function i_set_grade_of_activity_for_user($idnumber, $username, $grade) {
        global $DB;

        $assignid = $DB->get_field_sql(
            "SELECT instance FROM {course_modules} WHERE idnumber = :idnumber",
            array('idnumber' => $idnumber),
            MUST_EXIST
        );

        $userid = $DB->get_field('user', 'id', array('username' => $username), MUST_EXIST);

        $now = time();

        // assign_grades.
        $existing = $DB->get_record('assign_grades', array('assignment' => $assignid, 'userid' => $userid));
        if ($existing) {
            $existing->grade = $grade;
            $existing->grader = 2;
            $existing->timemodified = $now;
            $DB->update_record('assign_grades', $existing);
        } else {
            $agrade = new stdClass();
            $agrade->assignment    = $assignid;
            $agrade->userid        = $userid;
            $agrade->timecreated   = $now;
            $agrade->timemodified  = $now;
            $agrade->grader        = 2;
            $agrade->grade         = $grade;
            $agrade->attemptnumber = 0;
            $DB->insert_record('assign_grades', $agrade);
        }

        // grade_items + grade_grades.
        $courseid = $DB->get_field_sql(
            "SELECT c.id FROM {course_modules} cm JOIN {course} c ON cm.course = c.id WHERE cm.idnumber = :idnumber",
            array('idnumber' => $idnumber),
            MUST_EXIST
        );

        $itemid = $DB->get_field('grade_items', 'id', array(
            'courseid'     => $courseid,
            'itemtype'     => 'mod',
            'itemmodule'   => 'assign',
            'iteminstance' => $assignid,
        ));

        if ($itemid) {
            $existinggg = $DB->get_record('grade_grades', array('itemid' => $itemid, 'userid' => $userid));
            if ($existinggg) {
                $existinggg->rawgrade    = $grade;
                $existinggg->finalgrade  = $grade;
                $existinggg->timemodified = $now;
                $DB->update_record('grade_grades', $existinggg);
            } else {
                $gg = new stdClass();
                $gg->itemid       = $itemid;
                $gg->userid       = $userid;
                $gg->rawgrade     = $grade;
                $gg->finalgrade   = $grade;
                $gg->timecreated  = $now;
                $gg->timemodified = $now;
                $DB->insert_record('grade_grades', $gg);
            }
        }
    }

    /**
     * Shifts the grade date for a user on an assign activity N days after the submission date.
     *
     * @Given /^I set the grade date of activity "([^"]*)" for user "([^"]*)" to "([^"]*)" days after submission$/
     * @param string $idnumber
     * @param string $username
     * @param string $days
     */
    public function i_set_grade_date_of_activity_for_user($idnumber, $username, $days) {
        global $DB;

        $assignid = $DB->get_field_sql(
            "SELECT instance FROM {course_modules} WHERE idnumber = :idnumber",
            array('idnumber' => $idnumber),
            MUST_EXIST
        );

        $userid = $DB->get_field('user', 'id', array('username' => $username), MUST_EXIST);

        $submissiontime = $DB->get_field('assign_submission', 'timemodified',
            array('assignment' => $assignid, 'userid' => $userid));

        if (!$submissiontime) {
            // Fallback: use completionexpected of the cm as reference.
            $submissiontime = $DB->get_field_sql(
                "SELECT completionexpected FROM {course_modules} WHERE idnumber = :idnumber",
                array('idnumber' => $idnumber)
            );
        }

        $newtime = (int)$submissiontime + ((int)$days * 86400);

        $DB->set_field('assign_grades', 'timemodified', $newtime,
            array('assignment' => $assignid, 'userid' => $userid));

        $courseid = $DB->get_field_sql(
            "SELECT c.id FROM {course_modules} cm JOIN {course} c ON cm.course = c.id WHERE cm.idnumber = :idnumber",
            array('idnumber' => $idnumber),
            MUST_EXIST
        );

        $itemid = $DB->get_field('grade_items', 'id', array(
            'courseid'     => $courseid,
            'itemtype'     => 'mod',
            'itemmodule'   => 'assign',
            'iteminstance' => $assignid,
        ));

        if ($itemid) {
            $DB->set_field('grade_grades', 'timemodified', $newtime,
                array('itemid' => $itemid, 'userid' => $userid));
        }
    }

    /**
     * Marks a course module as complete for a given user directly in the database.
     *
     * @Given /^I mark activity "([^"]*)" as complete for user "([^"]*)"$/
     * @param string $idnumber
     * @param string $username
     */
    public function i_mark_activity_as_complete_for_user($idnumber, $username) {
        global $DB;

        $cmid = $DB->get_field_sql(
            "SELECT id FROM {course_modules} WHERE idnumber = :idnumber",
            array('idnumber' => $idnumber),
            MUST_EXIST
        );

        $userid = $DB->get_field('user', 'id', array('username' => $username), MUST_EXIST);

        $existing = $DB->get_record('course_modules_completion',
            array('coursemoduleid' => $cmid, 'userid' => $userid));

        if ($existing) {
            $existing->completionstate = 1;
            $existing->timemodified    = time();
            $DB->update_record('course_modules_completion', $existing);
        } else {
            $record = new stdClass();
            $record->coursemoduleid  = $cmid;
            $record->userid          = $userid;
            $record->completionstate = 1;
            $record->viewed          = 1;
            $record->timemodified    = time();
            $DB->insert_record('course_modules_completion', $record);
        }
    }

    /**
     * Change submission date of an activity.
     *
     * @Given /^I set the submission date of activity "([^"]*)" to "([^"]*)" days after$/
     * @param string $idnumber
     * @param string $days
     */
    public function i_set_submission_date($idnumber, $days) {
        global $DB;

        $assign_assignid = $DB->get_field_sql(
            "SELECT instance FROM {course_modules} WHERE idnumber = :idnumber",
            array('idnumber' => $idnumber)
        );

        $unix_timestamp = $DB->get_field_sql(
            "SELECT completionexpected FROM {course_modules} WHERE instance = :instance",
            array('instance' => $assign_assignid)
        );

        // 86400 = 1 day in seconds.
        $new_unix_timestamp = $unix_timestamp + ($days * 86400);

        $DB->set_field('assign_submission', 'timemodified', $new_unix_timestamp, array('assignment' => $assign_assignid));
    }
}
