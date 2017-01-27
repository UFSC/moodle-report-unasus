<?php

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Behat\Exception\PendingException as PendingException;

class behat_unasus extends behat_base
{
    /**
     * @var testing_data_generator
     */
    protected $datagenerator;
    protected $relationshipcount = 0;
    protected $relationship_groupscount = 0;
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
            'required' => array('name')
        ),

        'relationship_groups' => array(
            'datagenerator' => 'relationship_groups',
            'required' => array('name')
        ),
        
    );

    /**
     * Creates the specified element. More info about available elements in http://docs.moodle.org/dev/Acceptance_testing#Fixtures.
     *
     * @Given /^the following relationship "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     *
     * @throws Exception
     * @throws PendingException
     * @param string    $elementname The name of the entity to add
     * @param TableNode $data
     */

    public function the_Following_Relationship_Exist($elementname, TableNode $data)
    {

        $this->datagenerator = new behat_unasus;

        $elementdatagenerator = self::$elements[$elementname]['datagenerator'];
        $requiredfields = self::$elements[$elementname]['required'];
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
            // if (method_exists($this->datagenerator, $methodname)) {
            if(true){
                // Using data generators directly.
                $this->datagenerator->{$methodname}($elementdata);

            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);
            } else {
                throw new PendingException($elementname . ' data generator is not implemented');
            }
        }

    }

    /**
     * Creates the specified element. More info about available elements in http://docs.moodle.org/dev/Acceptance_testing#Fixtures.
     *
     * @Given /^the following relationship group "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     *
     * @throws Exception
     * @throws PendingException
     * @param string    $elementname The name of the entity to add
     * @param TableNode $data
     */
    public function the_Following_Relationship_Groups_Exist($elementname, TableNode $data)
    {

        $this->datagenerator = new behat_unasus;

        $elementdatagenerator = self::$elements[$elementname]['datagenerator'];
        $requiredfields = self::$elements[$elementname]['required'];
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
            // if (method_exists($this->datagenerator, $methodname)) {
            if(true){
                // Using data generators directly.
                $this->datagenerator->{$methodname}($elementdata);

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
     * @param array $options with keys:
     *      'createsections'=>bool precreate all sections
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

        return $DB->get_record('relationship', array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * Create a test relationship_groups
     * @param array|stdClass $record
     * @param array $options with keys:
     *      'createsections'=>bool precreate all sections
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
     *
     * Adiciona uma tag no relationship.
     *
     * @Given /^instance the tag "([^"]*)" at relationship "([^"]*)"$/
     */
    public function instance_the_tag_at_relationship($tag, $relationship) {
        global $DB;

        $record = new stdClass();
        $record->id = 1;
        $record->userid = 2;
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
        $record2->itemid = $id->relationshipid; // itemid = relationshipid! Why??
        $record2->contextid = null;
        $record2->tiuserid = 0;
        $record2->ordering = 0;
        $record2->timecreated = time();
        $record2->timemodified = time();

        $DB->insert_record('tag_instance', $record2);
    }

    /**
     *
     * Adiciona os cohorts criados no relationship. Para isso é feita a inserção na tabela relationship_cohorts
     * e relationship_members. Como há mais de um tipo de cohort é utilizado um foreach e assim executada a inclusão
     * de um cohort em cada iteração.
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

        $sql = "SELECT role_assignments.roleid, cohort.id, role_assignments.userid
                     FROM {cohort} cohort, {role_assignments} role_assignments
                     LEFT JOIN {cohort_members} cohort_members 
                     ON role_assignments.userid = cohort_members.userid
                     WHERE cohort_members.cohortid = cohort.id";

        $sql_result = $DB->get_records_sql($sql);

        foreach ($sql_result as $id) {
            $record = new stdClass();
            $record->relationshipid = $relationshipId;
            $record->roleid = $id->roleid;
            $record->cohortid = $id->id;
            $record->allowdupsingroups = 0;
            $record->uniformdistribution = 0;
            $record->timecreated = time();
            $record->timemodified = time();

            $DB->insert_record('relationship_cohorts', $record);

            $sql_rc = "SELECT id
                       FROM {relationship_cohorts}
                       WHERE roleid = :roleid";

            $relationshipCohortId = $DB->get_field_sql($sql_rc, array('roleid' => $id->roleid));

            $sql_rg = "SELECT id
                    FROM {relationship_groups}
                    WHERE name = :relationship_group";

            $relationshipGroupId = $DB->get_field_sql($sql_rg, array('relationship_group' => $relationship_group));

            $record = new stdClass();
            $record->relationshipgroupid = $relationshipGroupId;
            $record->relationshipcohortid = $relationshipCohortId;
            $record->userid = $id->userid;
            $record->timeadded = time();

            $DB->insert_record('relationship_members', $record);
        }
    }

    /**
     * Adds the user to the specified cohort. The user should be specified like "username".
     * Obs: Ao adicionar o usuário ao grupo de cohort, automaticamente é adicionado o usuário
     * aos respectivos grupos de tutoria.
     *
     * @Given /^I add the user "([^"]*)" with cohort "([^"]*)" to cohort members$/
     * @param string $user, $cohort
     */
    public function i_add_user_to_cohort_members($user, $cohort) {
        global $DB;

        $sql_user = "SELECT id
                     FROM {user}
                     WHERE username = :user";

        $userId = $DB->get_field_sql($sql_user, array('user' => $user));

        $sql_cohort = "SELECT id
                     FROM {cohort}
                     WHERE name = :cohort";

        $cohortId = $DB->get_field_sql($sql_cohort, array('cohort' => 'Cohort '.$cohort));

        $record = new stdClass();
        $record->cohortid = $cohortId;
        $record->userid = $userId;
        $record->timeadded = time();

        $DB->insert_record('cohort_members', $record);

        if($cohort == 'student'){
            $record_student = new stdClass();
            $record_student->name = 'local_tutores_student_roles';
            $record_student->value = $cohort;

            $DB->insert_record('config', $record_student);            

            $record_student_log = new stdClass();
            $record_student_log->userid = $userId;
            $record_student_log->timemodified = time();
            $record_student_log->plugin = null;
            $record_student_log->name = 'local_tutores_student_roles';
            $record_student_log->value = $cohort;

            $DB->insert_record('config_log', $record_student_log);

        }

        if($cohort == 'teacher'){
            $record_teacher = new stdClass();
            $record_teacher->name = 'local_tutores_tutor_roles';
            $record_teacher->value = 'editing'.$cohort;

            $DB->insert_record('config', $record_teacher);

            $record_teacher_log = new stdClass();
            $record_teacher_log->userid = $userId;
            $record_teacher_log->timemodified = time();
            $record_teacher_log->plugin = null;
            $record_teacher_log->name = 'local_tutores_tutor_roles';
            $record_teacher_log->value = 'editing'.$cohort;

            $DB->insert_record('config_log', $record_teacher_log);

            $record_editingteacher = new stdClass();
            $record_editingteacher->name = 'local_tutores_orientador_roles';
            $record_editingteacher->value = 'editing'.$cohort;

            $DB->insert_record('config', $record_editingteacher);

            $record_editingteacher_log = new stdClass();
            $record_editingteacher_log->userid = $userId;
            $record_editingteacher_log->timemodified = time();
            $record_editingteacher_log->plugin = null;
            $record_editingteacher_log->name = 'local_tutores_orientador_roles';
            $record_editingteacher_log->value = 'editing'.$cohort;

            $DB->insert_record('config_log', $record_editingteacher_log);
        }
    }

//    protected function preprocess_relationship($data)
//    {
//        if (isset($data['contextlevel'])) {
//            if (!isset($data['reference'])) {
//                throw new Exception('If field contextlevel is specified, field reference must also be present');
//            }
//            $context = $this->get_context($data['contextlevel'], $data['reference']);
//            unset($data['contextlevel']);
//            unset($data['reference']);
//            $data['contextid'] = $context->id;
//        }
//        return $data;
//    }

}
