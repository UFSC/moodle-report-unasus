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
    | fullname | shortname | id | category |
    | Course1  | c1        | 1  | CAT1     |

  And the following "course enrolments" exist:
    | user     | course | role    |
    | student1 | c1     | student |
    | teacher1 | c1     | teacher |

  And the following "permission overrides" exist:
    | capability                | permission | role    | contextlevel | reference |
    | local/relationship:view   | Allow      | teacher | Category     | CAT1      |
    | local/relationship:manage | Allow      | teacher | Category     | CAT1      |
    | local/relationship:assign | Allow      | teacher | Category     | CAT1      |
    | moodle/cohort:view        | Allow      | teacher | Category     | CAT1      |

  And the following "role assigns" exist:
    | user     | role    | contextlevel | reference |
    | teacher1 | teacher | Category     | CAT1      |

  And the following "cohorts" exist:
    | name             | idnumber | contextid |
    | Cohort professor | 500      | 154002    |
    | Cohort student   | 500      | 154002    |

  And I add the user "teacher1" with cohort "Cohort professor" to cohort members
  And I add the user "student1" with cohort "Cohort student" to cohort members

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
    And I navigate to "Lista: atividades não avaliadas" node in "Reports > UNA-SUS"
    And I pause
    And I follow "Lista: atividades não avaliadas"
    And I pause



