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
    protected $last_unasus_error_message = '';
    protected $last_unasus_csv_content = '';
    protected $last_unasus_csv_rows = array();
    protected $loremipsum = <<<EOD
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
     * Legacy generic step kept for backward compatibility.
     * Prefer using "a basic unasus tutoria environment exists" when applicable.
     *
     * @Given /^the following relationship "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     * @throws Exception
     * @throws PendingException
     */
    public function the_Following_Relationship_Exist($elementname, TableNode $data)
    {
        $this->_process_elements($elementname, $data);
    }

    /**
     * Legacy generic step kept for backward compatibility.
     * Prefer using "a basic unasus tutoria environment exists" when applicable.
     *
     * @Given /^the following relationship group "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     * @throws Exception
     * @throws PendingException
     */
    public function the_Following_Relationship_Groups_Exist($elementname, TableNode $data)
    {
        $this->_process_elements($elementname, $data);
    }

    /**
     * Legacy generic step kept for backward compatibility.
     * Prefer using "the following tutoria memberships exist".
     *
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
            throw new \Exception('relationship_groups requires the field relationshipid to be specified');
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
                WHERE tag.name = :tag
                  AND relationship.name = :relationship";

        $id = $DB->get_record_sql($sql, array('tag' => $tag, 'relationship' => $relationship));

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
     * @Given /^add created cohorts at relationship "([^"]*)"$/
     */
    public function add_created_cohorts_at_relationship($relationship)
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
     * Composite setup step for the default tutoria relationship structure.
     * This step is preferred in new scenarios to reduce repeated setup blocks.
     *
     * @Given /^a basic unasus tutoria environment exists:$/
     */
    public function a_basic_unasus_tutoria_environment_exists() {
        $this->ensure_relationship_exists('relationship1', 'CAT1');
        $this->ensure_relationship_group_exists('relationship_group1', 'relationship1');
        $this->ensure_relationship_group_exists('relationship_group2', 'relationship1');
        $this->ensure_relationship_group_exists('relationship_group3', 'relationship1');
        $this->ensure_relationship_tag_exists('grupo_tutoria', 'relationship1');
        $this->add_created_cohorts_at_relationship('relationship1');
    }

    /**
     * Composite setup step to add multiple users to cohorts.
     * Columns: user, cohort
     *
     * @Given /^the following users are added to cohorts:$/
     */
    public function the_following_users_are_added_to_cohorts(\Behat\Gherkin\Node\TableNode $data) {
        foreach ($data->getHash() as $row) {
            if (!isset($row['user']) || !isset($row['cohort'])) {
                throw new \Exception('Step requires columns: user, cohort');
            }
            $this->i_add_user_to_cohort_members($row['user'], $row['cohort']);
        }
    }

    /**
     * Composite setup step for standard tutoria memberships.
     * Columns: user, group
     *
     * @Given /^the following tutoria memberships exist:$/
     */
    public function the_following_tutoria_memberships_exist(\Behat\Gherkin\Node\TableNode $data) {
        foreach ($data->getHash() as $row) {
            if (!isset($row['user']) || !isset($row['group'])) {
                throw new \Exception('Step requires columns: user, group');
            }
            $this->create_relationship_members(array(
                'user' => $row['user'],
                'group' => $row['group'],
            ));
        }
    }

    /**
     * Ensures a relationship exists and is mapped in the local cache.
     *
     * @param string $relationshipname
     * @param string $categoryidnumber
     * @return int
     */
    private function ensure_relationship_exists($relationshipname, $categoryidnumber) {
        global $DB;

        $existing = $DB->get_record('relationship', array('name' => $relationshipname), 'id');
        if ($existing) {
            $this->relationships[$relationshipname] = (int) $existing->id;
            return (int) $existing->id;
        }

        $contextid = $this->get_category_id($categoryidnumber);
        $relationship = $this->create_relationship(array(
            'name' => $relationshipname,
            'contextid' => $contextid,
        ));

        return (int) $relationship->id;
    }

    /**
     * Ensures a relationship group exists for a relationship.
     *
     * @param string $groupname
     * @param string $relationshipname
     * @return int
     */
    private function ensure_relationship_group_exists($groupname, $relationshipname) {
        global $DB;

        $relationshipid = $this->get_relationship_id($relationshipname);
        $existing = $DB->get_record('relationship_groups', array(
            'name' => $groupname,
            'relationshipid' => $relationshipid,
        ), 'id');

        if ($existing) {
            return (int) $existing->id;
        }

        $group = $this->create_relationship_groups(array(
            'name' => $groupname,
            'relationshipid' => $relationshipid,
        ));

        return (int) $group->id;
    }

    /**
     * Ensures a tag instance exists for a relationship.
     *
     * @param string $tag
     * @param string $relationshipname
     */
    private function ensure_relationship_tag_exists($tag, $relationshipname) {
        global $DB;

        $relationshipid = $DB->get_field('relationship', 'id', array('name' => $relationshipname), MUST_EXIST);
        $tagid = $DB->get_field('tag', 'id', array('name' => $tag));

        if ($tagid) {
            $taginstance = $DB->get_record('tag_instance', array(
                'tagid' => $tagid,
                'itemtype' => 'relationship',
                'itemid' => $relationshipid,
            ), 'id');
            if ($taginstance) {
                return;
            }
        }

        $this->instance_the_tag_at_relationship($tag, $relationshipname);
    }

    /**
     * Legacy step kept for backward compatibility.
     * Prefer using "the following users are added to cohorts".
     *
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

        if ($cohort == 'advisor') {
            $record_advisor_log = new stdClass();
            $record_advisor_log->userid = $userId;
            $record_advisor_log->timemodified = time();
            $record_advisor_log->plugin = null;
            $record_advisor_log->name = 'local_tutores_orientador_roles';
            $record_advisor_log->value = 'advisor';

            $DB->insert_record('config_log', $record_advisor_log);
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

    // -------------------------------------------------------------------------
    // TCC mock helpers
    // -------------------------------------------------------------------------

    /**
     * Ensures report_unasus_SistemaTccClient is available.
     * Must be called at step runtime (not at class-load time) because
     * MOODLE_INTERNAL is only defined after Moodle's bootstrap runs,
     * which happens after Behat loads the context file.
     */
    private function require_sistematcc() {
        global $CFG;
        if (!class_exists('report_unasus_SistemaTccClient')) {
            require_once($CFG->dirroot . '/report/unasus/sistematcc.php');
        }
    }

    /**
     * Configures an LTI activity as a TCC tool by creating the required
     * lti_types and lti_types_config records and linking them to the activity.
     *
     * @Given /^the LTI activity "([^"]*)" is configured as TCC with tcc_definition_id "([^"]*)"$/
     * @param string $lti_idnumber  idnumber of the course_module
     * @param string $definition_id tcc_definition_id sent in customparameters
     */
    public function lti_configured_as_tcc($lti_idnumber, $definition_id) {
        global $DB;

        $cm = $DB->get_record_sql(
            "SELECT instance, course FROM {course_modules} WHERE idnumber = :idnumber",
            array('idnumber' => $lti_idnumber),
            MUST_EXIST
        );
        $lti_id = $cm->instance;

        // Create lti_types with a fake baseurl (mock intercepts before any HTTP).
        $type = new stdClass();
        $type->name          = 'TCC Mock Type';
        $type->baseurl       = 'http://mock-tcc-system';
        $type->tooldomain    = 'mock-tcc-system';
        $type->state         = 1;
        $type->course        = $cm->course;
        $type->coursevisible = 0;
        $type->timecreated   = time();
        $type->timemodified  = time();
        $type->createdby     = 2; // admin
        $typeid = $DB->insert_record('lti_types', $type);

        // resourcekey (consumer_key used by SistemaTccClient).
        $cfg_key = new stdClass();
        $cfg_key->typeid = $typeid;
        $cfg_key->name   = 'resourcekey';
        $cfg_key->value  = 'mock-consumer-key';
        $DB->insert_record('lti_types_config', $cfg_key);

        // customparameters containing tcc_definition_id.
        $cfg_params = new stdClass();
        $cfg_params->typeid = $typeid;
        $cfg_params->name   = 'customparameters';
        $cfg_params->value  = 'tcc_definition_id=' . $definition_id;
        $DB->insert_record('lti_types_config', $cfg_params);

        // Link the LTI activity instance to the newly created type.
        $DB->set_field('lti', 'typeid', $typeid, array('id' => $lti_id));
    }

    /**
     * Injects a mock response for the TCC definition webservice endpoint.
     * Stored in the Moodle config table so it is visible to the web server
     * PHP process when the report page renders (static variables do not cross
     * PHP process boundaries in Behat tests).
     *
     * Table columns: id, title, position
     *
     * @Given /^the TCC webservice returns definition with chapters:$/
     */
    public function tcc_webservice_returns_definition(\Behat\Gherkin\Node\TableNode $data) {
        $chapters = array();
        foreach ($data->getHash() as $row) {
            $ch             = new stdClass();
            $ch->id         = (int) $row['id'];
            $ch->title      = $row['title'];
            $ch->position   = (int) $row['position'];
            $ch->created_at = null;
            $ch->updated_at = null;

            $wrapper                    = new stdClass();
            $wrapper->chapter_definition = $ch;
            $chapters[]                 = $wrapper;
        }

        $tcc_def                    = new stdClass();
        $tcc_def->chapter_definitions = $chapters;

        $response              = new stdClass();
        $response->tcc_definition = $tcc_def;

        set_config('behat_tcc_mock_tcc_definition_service', json_encode($response), 'report_unasus');
    }

    /**
     * Injects a mock response for the TCC reporting webservice endpoint.
     * Resolves Moodle user IDs from usernames so the data matches what
     * LtiPortfolioQuery::get_report_data() expects.
     * Stored in the Moodle config table so it is visible to the web server
     * PHP process when the report page renders.
     *
     * Table columns: username, chapter_position, state, state_date
     *   - chapter_position = 0 means the abstract/resumo
     *   - state values: done | review | draft | null | empty
     *   - state_date: date string (Y-m-d) or empty for null
     *
     * @Given /^the TCC webservice returns student data:$/
     */
    public function tcc_webservice_returns_student_data(\Behat\Gherkin\Node\TableNode $data) {
        global $DB;

        $by_user = array();
        foreach ($data->getHash() as $row) {
            $uid  = $DB->get_field('user', 'id', array('username' => $row['username']), MUST_EXIST);
            $pos  = (int) $row['chapter_position'];
            $state = $row['state'];
            $date  = (!empty($row['state_date'])) ? $row['state_date'] : null;

            if (!isset($by_user[$uid])) {
                $by_user[$uid] = array('abstract' => null, 'chapters' => array());
            }

            if ($pos === 0) {
                $abstract             = new stdClass();
                $abstract->state      = $state;
                $abstract->state_date = $date;
                $by_user[$uid]['abstract'] = $abstract;
            } else {
                $ch             = new stdClass();
                $ch->position   = $pos;
                $ch->state      = $state;
                $ch->state_date = $date;

                $wrapper          = new stdClass();
                $wrapper->chapter = $ch;
                $by_user[$uid]['chapters'][] = $wrapper;
            }
        }

        $result = array();
        foreach ($by_user as $uid => $user_data) {
            $tcc           = new stdClass();
            $tcc->user_id  = $uid;
            $tcc->grade    = null;
            $tcc->abstract = $user_data['abstract'];
            $tcc->chapters = $user_data['chapters'];

            $entry      = new stdClass();
            $entry->tcc = $tcc;
            $result[]   = $entry;
        }

        set_config('behat_tcc_mock_reportingservice_tcc', json_encode($result), 'report_unasus');
    }

    /**
     * Inserts deterministic rows into logstore_standard_log for tutor report tests.
     *
     * Table columns: username, course, datetime, action
     *   - username: Moodle username
     *   - course: course shortname/fullname/idnumber or numeric id
     *   - datetime: any strtotime()-compatible value (e.g. 2026-03-10 10:00:00)
     *   - action: log action (e.g. viewed, updated). For "uso_sistema_tutor",
     *             use non-login/non-logout actions.
     *
     * @Given /^the following tutor report logs exist:$/
     */
    public function the_following_tutor_report_logs_exist(\Behat\Gherkin\Node\TableNode $data) {
        global $DB;

        $required = array('username', 'course', 'datetime', 'action');
        foreach ($data->getHash() as $row) {
            foreach ($required as $field) {
                if (!array_key_exists($field, $row)) {
                    throw new Exception('tutor report logs step requires column: ' . $field);
                }
            }

            $userid = $DB->get_field('user', 'id', array('username' => trim($row['username'])), MUST_EXIST);
            $courseid = $this->resolve_course_id(trim($row['course']));
            $context = context_course::instance($courseid);

            $timestamp = strtotime(trim($row['datetime']));
            if ($timestamp === false) {
                throw new Exception('Invalid datetime value for tutor report logs step: ' . $row['datetime']);
            }

            $record = new stdClass();
            $record->eventname = '\\core\\event\\course_viewed';
            $record->component = 'core';
            $record->action = trim($row['action']);
            $record->target = 'course';
            $record->objecttable = 'course';
            $record->objectid = $courseid;
            $record->crud = 'r';
            $record->edulevel = 2;
            $record->contextid = $context->id;
            $record->contextlevel = $context->contextlevel;
            $record->contextinstanceid = $context->instanceid;
            $record->userid = $userid;
            $record->courseid = $courseid;
            $record->relateduserid = null;
            $record->anonymous = 0;
            $record->other = null;
            $record->timecreated = $timestamp;
            $record->origin = 'web';
            $record->ip = '127.0.0.1';
            $record->realuserid = null;

            $DB->insert_record('logstore_standard_log', $record);
        }
    }

    /**
     * Opens a UNA-SUS report URL directly (without navigating from course menu).
     *
     * @When /^I open the unasus report "([^"]*)" directly for course "([^"]*)"$/
     * @param string $reportname
     * @param string $courseidentifier
     */
    public function i_open_the_unasus_report_directly_for_course($reportname, $courseidentifier) {
        $courseid = $this->resolve_course_id(trim($courseidentifier));
        $url = new moodle_url('/report/unasus/index.php', array(
            'relatorio' => trim($reportname),
            'course' => $courseid,
        ));

        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }

    /**
     * Attempts to open a UNA-SUS report URL directly and captures permission errors.
     *
     * @When /^I try to open the unasus report "([^"]*)" directly for course "([^"]*)"$/
     * @param string $reportname
     * @param string $courseidentifier
     */
    public function i_try_to_open_the_unasus_report_directly_for_course($reportname, $courseidentifier) {
        $this->last_unasus_error_message = '';

        try {
            $this->i_open_the_unasus_report_directly_for_course($reportname, $courseidentifier);
        } catch (\Throwable $e) {
            $this->last_unasus_error_message = $e->getMessage();
        }
    }

    /**
     * Asserts that a permission error was captured while opening UNA-SUS report directly.
     *
     * @Then /^I should have a unasus permission error$/
     */
    public function i_should_have_a_unasus_permission_error() {
        if (empty($this->last_unasus_error_message)) {
            throw new \Exception('Expected a UNA-SUS permission error, but no exception was captured.');
        }

        $msg = mb_strtolower($this->last_unasus_error_message);
        if (strpos($msg, 'permission') === false && strpos($msg, 'permiss') === false) {
            throw new \Exception('Expected a permission-related error. Captured: ' . $this->last_unasus_error_message);
        }
    }

    /**
     * Exports any UNA-SUS report as CSV by direct URL and stores parsed CSV content.
     * Table columns: name, value
     *
     * @When /^I export the unasus report "([^"]*)" as csv for course "([^"]*)" with params:$/
     */
    public function i_export_the_unasus_report_as_csv_for_course_with_params($reportname, $courseidentifier, \Behat\Gherkin\Node\TableNode $data) {
        $courseid = $this->resolve_course_id(trim($courseidentifier));
        $params = array(
            'relatorio' => trim($reportname),
            'course' => $courseid,
            'modo_exibicao' => 'export_csv',
        );

        foreach ($data->getHash() as $row) {
            if (!isset($row['name']) || !array_key_exists('value', $row)) {
                throw new \Exception('Step requires columns: name, value');
            }
            $name = trim($row['name']);
            $value = trim($row['value']);

            // Allow stable feature params that depend on runtime ids.
            // value "courseid:<courseidentifier>" resolves to course id.
            if (strpos($value, 'courseid:') === 0) {
                $courseidentifier = substr($value, 9);
                $value = (string) $this->resolve_course_id($courseidentifier);
            }

            // value "cmid:<activityidnumber>" resolves to course module id.
            if (strpos($value, 'cmid:') === 0) {
                $activityidnumber = substr($value, 5);
                $value = (string) $this->resolve_course_module_id_by_activity_idnumber($courseid, $activityidnumber);
            }

            $params[$name] = $value;
        }

        $url = new moodle_url('/report/unasus/index.php', $params);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));

        $content = $this->getSession()->getPage()->getContent();
        $this->last_unasus_csv_content = (string) $content;
        $this->last_unasus_csv_rows = $this->parse_unasus_csv_rows($this->last_unasus_csv_content);
    }

    /**
     * Resolves course_modules.id from an activity idnumber within a course.
     *
     * @param int $courseid
     * @param string $activityidnumber
     * @return int
     * @throws Exception
     */
    private function resolve_course_module_id_by_activity_idnumber($courseid, $activityidnumber) {
        global $DB;

        // Most Moodle generators store activity idnumber on course_modules.
        $cmid = $DB->get_field('course_modules', 'id', array(
            'course' => $courseid,
            'idnumber' => $activityidnumber,
        ));
        if ($cmid) {
            return (int) $cmid;
        }

        // Fallback: try to resolve by module instance table idnumber.
        $modules = $DB->get_records('modules', null, '', 'id,name');
        foreach ($modules as $module) {
            $table = $module->name;
            if (!$DB->get_manager()->table_exists($table)) {
                continue;
            }

            if (!$DB->get_manager()->field_exists($table, 'idnumber')) {
                continue;
            }

            $instanceid = $DB->get_field($table, 'id', array('idnumber' => $activityidnumber));
            if (!$instanceid) {
                continue;
            }

            $cmid = $DB->get_field('course_modules', 'id', array(
                'course' => $courseid,
                'module' => $module->id,
                'instance' => $instanceid,
            ));
            if ($cmid) {
                return (int) $cmid;
            }
        }

        throw new Exception('Course module not found for activity idnumber: ' . $activityidnumber . ' (course ' . $courseid . ')');
    }

    /**
     * Asserts raw exported CSV contains a given text.
     *
     * @Then /^the exported unasus csv should contain "([^"]*)"$/
     */
    public function the_exported_unasus_csv_should_contain($needle) {
        if (strpos($this->last_unasus_csv_content, $needle) === false) {
            throw new \Exception('CSV content does not contain expected text: ' . $needle);
        }
    }

    /**
     * Asserts at least one parsed CSV row contains all provided values.
     * Single-column table header: value
     *
     * @Then /^the exported unasus csv should have a row containing:$/
     */
    public function the_exported_unasus_csv_should_have_a_row_containing(\Behat\Gherkin\Node\TableNode $data) {
        $values = array();
        foreach ($data->getHash() as $row) {
            if (!isset($row['value'])) {
                throw new \Exception('Step requires single column: value');
            }
            $values[] = trim($row['value']);
        }

        foreach ($this->last_unasus_csv_rows as $csvrow) {
            $line = implode(' | ', $csvrow);
            $ok = true;
            foreach ($values as $value) {
                if (strpos($line, $value) === false) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return;
            }
        }

        throw new \Exception('No CSV row found containing all expected values: ' . implode(', ', $values));
    }

    /**
     * Parses CSV rows from a raw CSV string.
     *
     * @param string $content
     * @return array
     */
    private function parse_unasus_csv_rows($content) {
        $rows = array();
        $lines = preg_split('/\r\n|\r|\n/', (string) $content);
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $parsed = str_getcsv($line);
            if ($parsed === null) {
                continue;
            }
            $rows[] = $parsed;
        }
        return $rows;
    }

    /**
     * Resolves a course id from numeric id, shortname, fullname or idnumber.
     *
     * @param string $courseidentifier
     * @return int
     * @throws Exception
     */
    private function resolve_course_id($courseidentifier) {
        global $DB;

        if (is_numeric($courseidentifier)) {
            $course = $DB->get_record('course', array('id' => (int) $courseidentifier), 'id');
            if ($course) {
                return (int) $course->id;
            }
        }

        $fields = array('shortname', 'fullname', 'idnumber');
        foreach ($fields as $field) {
            $courseid = $DB->get_field('course', 'id', array($field => $courseidentifier));
            if ($courseid) {
                return (int) $courseid;
            }
        }

        throw new Exception('Course not found for identifier: ' . $courseidentifier);
    }

    /**
     * Resets TCC mock responses after each scenario tagged @tcc so that
     * mock state does not leak into subsequent scenarios.
     *
     * @AfterScenario @tcc
     */
    public function reset_tcc_mock() {
        unset_config('behat_tcc_mock_tcc_definition_service', 'report_unasus');
        unset_config('behat_tcc_mock_reportingservice_tcc', 'report_unasus');
        // Also clear static mock in case it was set directly (e.g. in same-process unit tests)
        if (class_exists('report_unasus_SistemaTccClient')) {
            report_unasus_SistemaTccClient::$mock_responses = null;
        }
    }
}
