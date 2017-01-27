 @unasus @report_unasus @javascript
Feature: Edit completion settings of an activity
  In order to edit completion settings without accidentally breaking user data
  As a teacher
  I need to edit the activity and use the unlock button if required

Background:
  Given the following "users" exist:
    | username | firstname | lastname | email                 |
    | student1 | Student   |     1    | stundent1@example.com |
    | teacher1 | Teacher   |     1    | teacher1@example.com  |

  And the following "categories" exist:
    | name       | category | idnumber |
    | Category 1 | 0        | CAT1     |

  And the following "courses" exist:
    | fullname | shortname | id | category | groupmode |
    | Course1  | c1        | 1  | CAT1     | 1         |

# -----------------------------QUIZ SETUP-----------------------------------------------
  And the following "question categories" exist:
    | contextlevel | reference | name           |
    | Course       | C1        | Test questions |

  And the following "activities" exist:
    | activity   | name   | intro              | course | idnumber |
    | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    |

  And the following "questions" exist:
    | questioncategory | qtype       | name  | questiontext    |
    | Test questions   | truefalse   | TF1   | First question  |
    | Test questions   | truefalse   | TF2   | Second question |

  And quiz "Quiz 1" contains the following questions:
    | question | page | maxmark |
    | TF1      | 1    |         |
    | TF2      | 1    | 3.0     |
# --------------------------------------------------------------------------------------

  And the following "course enrolments" exist:
    | user     | course | role    |
    | student1 | c1     | student |
    | teacher1 | c1     | teacher |

  # Por causa da exigência do relatório, é necessário que o role seja editingteacher
  And the following "permission overrides" exist:
    | capability                | permission | role           | contextlevel | reference |
    | local/relationship:view   | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view        | Allow      | editingteacher | Category     | CAT1      |

  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | editingteacher | Category     | CAT1      |

  And the following "cohorts" exist:
    | name             | idnumber | contextid |
    | Cohort teacher   | 500      | 154002    |
    | Cohort student   | 501      | 154002    |

  # O cohort precisa ser student ou teacher para que seja incluído nos grupos de tutoria
  And I add the user "teacher1" with cohort "teacher" to cohort members
  And I add the user "student1" with cohort "student" to cohort members

  And the following relationship "relationships" exist:
    | name          | contextid |
    | relationship1 | 154002    |

  And the following relationship group "relationship_groups" exist:
    | name                | relationshipid |
    | relationship_group1 | 344000         |

  And instance the tag "grupo_tutoria" at relationship "relationship1"
  And add created cohorts at relationship "relationship1" on relationship_groups "relationship_group1"
  And I log in as "admin"

  @javascript
Scenario: Teachers can navigate relationship page
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Síntese: avaliações em atraso" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "relationship_group1 - Teacher 1"




