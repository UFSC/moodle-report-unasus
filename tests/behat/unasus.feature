 @unasus @report_unasus @javascript
Feature: Edit completion settings of an activity
  In order to edit completion settings without accidentally breaking user data
  As a teacher
  I need to edit the activity and use the unlock button if required

Background:
  Given the following "users" exist:
    | username | firstname | lastname | email                 |
    | student1 | Student   |     s1   | stundent1@example.com |
    | student2 | Student   |     s2   | stundent2@example.com |
    | student3 | Student   |     s3   | stundent3@example.com |
    | teacher1 | Teacher   |     t1   | teacher1@example.com  |
    | teacher2 | Teacher   |     t2   | teacher2@example.com  |

  And the following "categories" exist:
    | name       | category | idnumber |
    | Category 1 | 0        | CAT1     |

  And the following "courses" exist:
    | fullname | shortname | category | groupmode | enablecompletion |
    | Course1  | c1        | CAT1     | 1         | 1                |
#    | Course2  | c2        | CAT2     | 1         | 1                |
  And the following config values are set as admin:
    | enablecompletion | 1 |

  And the following "cohorts" exist:
    | name             | idnumber | contextlevel | reference |
    | Cohort teacher   | CHt      | Category     | CAT1      |
    | Cohort student   | CHs      | Category     | CAT1      |

# -----------------------------ASSIGN SETUP-----------------------------------------------
# Unix timestamp 946684800 = 1 jan 2000 at 00h00m00s
  And the following "activities" exist:
    | activity | course | idnumber | name                  | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected |
    | assign   | C1     | a1       | Test assignment one   | Submit something! | 100   | 1                                   | 2147483647         |
    | assign   | C1     | a2       | Test assignment two   | Submit something! | 100   | 1                                   | 978307200          |
    | assign   | C1     | a3       | Test assignment three | Submit something! | 100   | 1                                   | 0                  |
    | assign   | C1     | a4       | Test assignment four  | Submit something! | 100   | 1                                   | 946684800          |
    | assign   | C1     | a5       | Test assignment five  | Submit something! | 100   | 1                                   | 946684800          |
    | assign   | C1     | a6       | Test assignment six   | Submit something! | 100   | 1                                   | 946684800          |
    | assign   | C1     | a7       | Test assignment seven | Submit something! | 100   | 1                                   | 946684800          |


#  And the following activity "assigns" exist:
#    | activity | course | idnumber | name                  | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected |
#    | assign   | C1     | a1       | Test assignment one   | Submit something! | 100   | 1                                   | time() + 31557600  |
#    | assign   | C1     | a2       | Test assignment two   | Submit something! | 100   | 1                                   | 1524585600         |
#    | assign   | C1     | a3       | Test assignment three | Submit something! | 100   | 1                                   | 1524585600         |
#    | assign   | C1     | a4       | Test assignment four  | Submit something! | 100   | 1                                   | 1524585600         |
#    | assign   | C1     | a5       | Test assignment five  | Submit something! | 100   | 1                                   | 1524585600         |
#    | assign   | C1     | a6       | Test assignment six   | Submit something! | 100   | 1                                   | 1524585600         |
#    | assign   | C1     | a7       | Test assignment seven | Submit something! | 100   | 1                                   | 1524585600         |

# --------------------------------------------------------------------------------------
# -----------------------------QUIZ SETUP-----------------------------------------------
#  And the following "question categories" exist:
#    | contextlevel | reference | name           |
#    | Course       | C1        | Test questions |
#
#  And the following "activities" exist:
#    | activity   | name   | intro              | course | idnumber |
#    | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    |
#
#
#  And the following "questions" exist:
#    | questioncategory | qtype       | name  | questiontext    |
#    | Test questions   | truefalse   | TF1   | First question  |
#    | Test questions   | truefalse   | TF2   | Second question |
#
#  And quiz "Quiz 1" contains the following questions:
#    | question | page | maxmark |
#    | TF1      | 1    |         |
#    | TF2      | 1    | 3.0     |

# --------------------------------------------------------------------------------------
# -----------------------------FORUM SETUP-----------------------------------------------
#  And I log in as "admin"
#  And I follow "Courses"
#  And I follow "Category 1"
#  And I follow "Course1"
#  And I turn editing mode on
#  And I click on "Edit settings" "link" in the "Administration" "block"
#  And I set the following fields to these values:
#    | Enable completion tracking | Yes |
#  And I press "Save and display"
#
##  And the following "activities" exist:
##    | activity   | name                   | intro       | course | idnumber     | groupmode |
##    | forum      | forum                  | Test forum  | C1     | forum        | 0         |
#
#  When I add a "forum" to section "1" and I fill the form with:
#    | Forum name  | Test forum name1       |
#    | Description | Test forum description |
#    | course      | C1                     |
#    | groupmode   | 0                      |
#    | Completion tracking | Show activity as complete when conditions are met |
#    | completionview      | 1                                                 |
#
#  When I add a "forum" to section "1" and I fill the form with:
#    | Forum name  | Test forum name2       |
#    | Description | Test forum description |
#    | course      | C1                     |
#    | groupmode   | 0                      |
#    | Completion tracking | Show activity as complete when conditions are met |
#    | completionview      | 1                                                 |
#
#  When I add a "forum" to section "1" and I fill the form with:
#    | Forum name  | Test forum name3       |
#    | Description | Test forum description |
#    | course      | C1                     |
#    | groupmode   | 0                      |
#    | Completion tracking | Show activity as complete when conditions are met |
#    | completionview      | 1                                                 |
#  And I log out

# --------------------------------------------------------------------------------------

  And the following "course enrolments" exist:
    | user     | course | role    |
    | student1 | c1     | student |
    | student2 | c1     | student |
    | student3 | c1     | student |
    | teacher1 | c1     | editingteacher |
    | teacher2 | c1     | editingteacher |

  # Por causa da exigência do relatório, é necessário que a coluna `role` tenha como valor editingteacher
  And the following "permission overrides" exist:
    | capability                | permission | role           | contextlevel | reference |
    | local/relationship:view   | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view        | Allow      | editingteacher | Category     | CAT1      |

  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | editingteacher | Category     | CAT1      |
    | teacher2 | editingteacher | Category     | CAT1      |

  # O cohort precisa ser student ou teacher para que seja incluído nos grupos de tutoria
  # É necessário usar a regra abaixo para a inclusão dos membros, visto a integração do plugin relationship
  And I add the user "teacher1" with cohort "teacher" to cohort members
  And I add the user "teacher2" with cohort "teacher" to cohort members
  And I add the user "student1" with cohort "student" to cohort members
  And I add the user "student2" with cohort "student" to cohort members
  And I add the user "student3" with cohort "student" to cohort members

  And the following relationship "relationships" exist:
    | name          | contextid |
    | relationship1 | 154005    |

  And the following relationship group "relationship_groups" exist:
    | name                | relationshipid |
    | relationship_group1 | 344000         |
    | relationship_group2 | 344000         |

  And instance the tag "grupo_tutoria" at relationship "relationship1"
  And add created cohorts at relationship "relationship1" on relationship_groups "relationship_group1"
  And add created cohorts at relationship "relationship1" on relationship_groups "relationship_group2"

  And the following users belongs to the relationship group as "relationship_members":
    | user                | group               |
    | student1            | relationship_group1 |
    | student2            | relationship_group1 |
    | student3            | relationship_group2 |
    | teacher1            | relationship_group1 |
    | teacher2            | relationship_group2 |




  # Resposta do quiz (Entrega da atividade por parte do estudante)
#  And I log in as "student1"
#  And I follow "Course1"
#  And I follow "Quiz 1"
#  And I press "Attempt quiz now"
#  And I click on "True" "radio" in the "First question" "question"
#  And I click on "False" "radio" in the "Second question" "question"
#  And I follow "Finish attempt ..."
#  And I press "Submit all and finish"
#  And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
#  And I log out
#
#  And I log in as "student2"
#  And I follow "Course1"
#  And I follow "Quiz 1"
#  And I press "Attempt quiz now"
#  And I click on "True" "radio" in the "First question" "question"
#  And I click on "True" "radio" in the "Second question" "question"
#  And I follow "Finish attempt ..."
#  And I press "Submit all and finish"
#  And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
#  And I log out
#
#  And I log in as "student3"
#  And I follow "Course1"
#  And I follow "Quiz 1"
#  And I press "Attempt quiz now"
#  And I click on "False" "radio" in the "First question" "question"
#  And I click on "False" "radio" in the "Second question" "question"
#  And I follow "Finish attempt ..."
#  And I press "Submit all and finish"
#  And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
#  And I log out

  # Resposta do forum (Entrega da atividade por parte do estudante)
#  And I log in as "student1"
#  And I follow "Course1"
#  And I add a new discussion to "Test forum name1" forum with:
#    | Subject | Forum discussion 1 |
#    | Message | How awesome is this forum discussion? |
#  And I reply "Forum discussion 1" post from "Test forum name1" forum with:
#    | Message | Actually, I've seen better. |
#  And I log out
#
#  And I log in as "student2"
#  And I follow "Course1"
#  And I add a new discussion to "Test forum name2" forum with:
#    | Subject | Forum discussion 1 |
#    | Message | How awesome is this forum discussion? |
#  And I reply "Forum discussion 1" post from "Test forum name2" forum with:
#    | Message | Actually, I've seen better. |
#  And I log out
#
#  And I log in as "student3"
#  And I follow "Course1"
#  And I add a new discussion to "Test forum name3" forum with:
#    | Subject | Forum discussion 1 |
#    | Message | How awesome is this forum discussion? |
#  And I reply "Forum discussion 1" post from "Test forum name3" forum with:
#    | Message | Actually, I've seen better. |
#  And I log out

  # Resposta do assignment (Entrega da atividade por parte do estudante)
#  And I log in as "student1"
#  And I follow "Course1"
#  And I follow "Test assignment one"
#  And I press "Add submission"
#  And I set the following fields to these values:
#    | Online text | I'm the student1 submission |
#  And I press "Save changes"
#  And I press "Submit assignment"
#  And I press "Continue"
#  And I log out

  And I log in as "student2"
  And I follow "Course1"
  And I follow "Test assignment two"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student2 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I log out

  And I log in as "student3"
  And I follow "Course1"
  And I follow "Test assignment three"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student3 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I log out

  And I log in as "student1"
  And I follow "Course1"
  And I follow "Test assignment four"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student1 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I set the submission date of activity "a4" to "0" days after
  And I log out

  And I log in as "student1"
  And I follow "Course1"
  And I follow "Test assignment five"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student3 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I set the submission date of activity "a5" to "10" days after
  And I log out

  And I log in as "student1"
  And I follow "Course1"
  And I follow "Test assignment six"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student3 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I set the submission date of activity "a6" to "11" days after
  And I log out

  And I log in as "student1"
  And I follow "Course1"
  And I follow "Test assignment seven"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student3 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I set the submission date of activity "a7" to "11" days after
  And I log out

  # Atribuição de nota em assignment (Avaliação da atividade por parte do professor)
#  And I follow "Courses"
#  And I follow "Category 1"
#  And I follow "Course1"
#  And I navigate to "Grades" node in "Course administration"
#  And I turn editing mode on
#  And I give the grade "100.00" to the user "Student 1" for the grade item "Test assignment one"
#  And I give the grade "75.00" to the user "Student 2" for the grade item "Test assignment one"
#  And I give the grade "50.00" to the user "Student 3" for the grade item "Test assignment one"
##  And I give the grade "67.00" to the user "Student 1" for the grade item "Test assignment two"
#  And I press "Save changes"


  @javascript
Scenario: Correct report generation
    And I log in as "admin"

    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
#    And I navigate to "Síntese: avaliações em atraso" node in "Reports > UNA-SUS"
#    And I navigate to "Boletim" node in "Reports > UNA-SUS"
#    And I pause
    And I navigate to "Acompanhamento: entrega de atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    And I pause
    Then I should see "Test assignment one"




