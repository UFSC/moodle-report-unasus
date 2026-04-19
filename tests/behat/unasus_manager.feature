@unasus @report_unasus @javascript @manager @tcc
Feature: Relatórios UNA-SUS: acesso global com manager (view_all)
  Para acompanhar tutoria e orientação de forma consolidada
  Como usuário manager com report/unasus:view_all
  Preciso visualizar todos os grupos e todos os estudantes

Background:
  Given the following "users" exist:
    | username  | firstname | lastname | email                  |
    | student1  | Student   | s1       | student1@example.com   |
    | student2  | Student   | s2       | student2@example.com   |
    | student3  | Student   | s3       | student3@example.com   |
    | student4  | Student   | s4       | student4@example.com   |
    | student5  | Student   | s5       | student5@example.com   |
    | student6  | Student   | s6       | student6@example.com   |
    | student7  | Student   | s7       | student7@example.com   |
    | student8  | Student   | s8       | student8@example.com   |
    | student9  | Student   | s9       | student9@example.com   |
    | student10 | Student   | s10      | student10@example.com  |
    | student11 | Student   | s11      | student11@example.com  |
    | student12 | Student   | s12      | student12@example.com  |
    | teacher1  | Teacher   | t1       | teacher1@example.com   |
    | teacher2  | Teacher   | t2       | teacher2@example.com   |
    | teacher3  | Teacher   | t3       | teacher3@example.com   |
    | manager1  | Manager   | m1       | manager1@example.com   |

  And the following "categories" exist:
    | name       | category | idnumber |
    | Category 1 | 0        | CAT1     |

  And the following "courses" exist:
    | fullname | shortname | category | groupmode | enablecompletion |
    | Course1  | c1        | CAT1     | 1         | 1                |

  And the following config values are set as admin:
    | enablecompletion               | 1              |
    | local_tutores_student_roles    | student        |
    | local_tutores_tutor_roles      | editingteacher |
    | local_tutores_orientador_roles | editingteacher |
    | report_unasus_prazo_maximo_entrega   | 10 |
    | report_unasus_prazo_maximo_avaliacao | 5  |
    | report_unasus_prazo_avaliacao        | 1  |
    | report_unasus_tolerancia_potencial_evasao | 1 |

  And the following "cohorts" exist:
    | name             | idnumber | contextlevel | reference |
    | Cohort teacher   | CHt      | Category     | CAT1      |
    | Cohort student   | CHs      | Category     | CAT1      |

  And the following "activities" exist:
    | activity | course | idnumber | name              | intro      | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
    | assign   | C1     | a1       | Manager assignment | Submit now | 100   | 1                                   | 978307200          | 1          |

  And the following "activities" exist:
    | activity | course | idnumber | name       | intro     | completion | completionexpected |
    | lti      | C1     | ltcc1    | TCC Eixo 1 | TCC intro | 1          | 978307200          |

  And the LTI activity "ltcc1" is configured as TCC with tcc_definition_id "1"
  And the TCC webservice returns definition with chapters:
    | id | title      | position |
    | 1  | Resumo     | 0        |
    | 2  | Capítulo 1 | 1        |

  And the TCC webservice returns student data:
    | username  | chapter_position | state | state_date |
    | student1  | 0                | done  | 2024-01-01 |
    | student1  | 1                | done  | 2024-01-02 |
    | student2  | 0                | done  | 2024-01-01 |
    | student2  | 1                | done  | 2024-01-02 |
    | student3  | 0                | done  | 2024-01-01 |
    | student3  | 1                | done  | 2024-01-02 |
    | student4  | 0                | done  | 2024-01-01 |
    | student4  | 1                | done  | 2024-01-02 |
    | student5  | 0                | done  | 2024-01-01 |
    | student5  | 1                | done  | 2024-01-02 |
    | student6  | 0                | done  | 2024-01-01 |
    | student6  | 1                | done  | 2024-01-02 |
    | student7  | 0                | done  | 2024-01-01 |
    | student7  | 1                | done  | 2024-01-02 |
    | student8  | 0                | done  | 2024-01-01 |
    | student8  | 1                | done  | 2024-01-02 |
    | student9  | 0                | done  | 2024-01-01 |
    | student9  | 1                | done  | 2024-01-02 |
    | student10 | 0                | done  | 2024-01-01 |
    | student10 | 1                | done  | 2024-01-02 |
    | student11 | 0                | done  | 2024-01-01 |
    | student11 | 1                | done  | 2024-01-02 |
    | student12 | 0                | done  | 2024-01-01 |
    | student12 | 1                | done  | 2024-01-02 |

  And the following "course enrolments" exist:
    | user      | course | role           |
    | student1  | c1     | student        |
    | student2  | c1     | student        |
    | student3  | c1     | student        |
    | student4  | c1     | student        |
    | student5  | c1     | student        |
    | student6  | c1     | student        |
    | student7  | c1     | student        |
    | student8  | c1     | student        |
    | student9  | c1     | student        |
    | student10 | c1     | student        |
    | student11 | c1     | student        |
    | student12 | c1     | student        |
    | teacher1  | c1     | editingteacher |
    | teacher2  | c1     | editingteacher |
    | teacher3  | c1     | editingteacher |
    | manager1  | c1     | manager        |

  And the following "permission overrides" exist:
    | capability                    | permission | role           | contextlevel | reference |
    | local/relationship:view       | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage     | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign     | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view            | Allow      | editingteacher | Category     | CAT1      |
    | report/unasus:view_tutoria    | Allow      | editingteacher | Course       | c1        |
    | report/unasus:view_orientacao | Allow      | editingteacher | Course       | c1        |
    | report/unasus:view_all        | Allow      | manager        | Course       | c1        |

  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | editingteacher | Category     | CAT1      |
    | teacher2 | editingteacher | Category     | CAT1      |
    | teacher3 | editingteacher | Category     | CAT1      |

  And I add the user "teacher1" with cohort "teacher" to cohort members
  And I add the user "teacher2" with cohort "teacher" to cohort members
  And I add the user "teacher3" with cohort "teacher" to cohort members
  And I add the user "student1" with cohort "student" to cohort members
  And I add the user "student2" with cohort "student" to cohort members
  And I add the user "student3" with cohort "student" to cohort members
  And I add the user "student4" with cohort "student" to cohort members
  And I add the user "student5" with cohort "student" to cohort members
  And I add the user "student6" with cohort "student" to cohort members
  And I add the user "student7" with cohort "student" to cohort members
  And I add the user "student8" with cohort "student" to cohort members
  And I add the user "student9" with cohort "student" to cohort members
  And I add the user "student10" with cohort "student" to cohort members
  And I add the user "student11" with cohort "student" to cohort members
  And I add the user "student12" with cohort "student" to cohort members

  And the following relationship "relationships" exist:
    | name          | category |
    | relationship1 | CAT1     |

  And the following relationship group "relationship_groups" exist:
    | name                | relationship  |
    | relationship_group1 | relationship1 |
    | relationship_group2 | relationship1 |
    | relationship_group3 | relationship1 |

  And instance the tag "grupo_tutoria" at relationship "relationship1"
  And instance the tag "grupo_orientacao" at relationship "relationship1"
  And add created cohorts at relationship "relationship1"

  And the following users belongs to the relationship group as "relationship_members":
    | user      | group               |
    | teacher1  | relationship_group1 |
    | student1  | relationship_group1 |
    | student2  | relationship_group1 |
    | student3  | relationship_group1 |
    | student4  | relationship_group1 |
    | teacher2  | relationship_group2 |
    | student5  | relationship_group2 |
    | student6  | relationship_group2 |
    | student7  | relationship_group2 |
    | student8  | relationship_group2 |
    | teacher3  | relationship_group3 |
    | student9  | relationship_group3 |
    | student10 | relationship_group3 |
    | student11 | relationship_group3 |
    | student12 | relationship_group3 |

  @javascript
  Scenario: manager com view_all visualiza todos os estudantes na tutoria
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: entrega de atividades" node in "Reports > UNA-SUS"
    Then I should see "Filtrar Cohorts:"
    And I should see "Filtrar Grupos de Tutoria:"
    And I press "Gerar relatório"
    And I should see "Student s1"
    And I should see "Student s2"
    And I should see "Student s3"
    And I should see "Student s4"
    And I should see "Student s5"
    And I should see "Student s6"
    And I should see "Student s7"
    And I should see "Student s8"
    And I should see "Student s9"
    And I should see "Student s10"
    And I should see "Student s11"
    And I should see "Student s12"

  @javascript
  Scenario: manager com view_all visualiza todos os estudantes na orientação TCC
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "TCC: Entrega de Atividades" node in "Reports > UNA-SUS"
    Then I should see "Filtrar Grupos de Orientação:"
    And I press "Gerar relatório"
    And I should see "Resumo"
    And I should see "Capítulo 1"
    And I should see "Student s1"
    And I should see "Student s2"
    And I should see "Student s3"
    And I should see "Student s4"
    And I should see "Student s5"
    And I should see "Student s6"
    And I should see "Student s7"
    And I should see "Student s8"
    And I should see "Student s9"
    And I should see "Student s10"
    And I should see "Student s11"
    And I should see "Student s12"

  @javascript
  Scenario: tutor sem view_all nao visualiza estudantes de outros grupos na tutoria
    And I log in as "teacher1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: entrega de atividades" node in "Reports > UNA-SUS"
    Then I should not see "Filtrar Cohorts:"
    And I press "Gerar relatório"
    And I should see "Student s1"
    And I should see "Student s2"
    And I should see "Student s3"
    And I should see "Student s4"
    And I should not see "Student s5"
    And I should not see "Student s12"

  @javascript
  Scenario: orientador sem view_all nao visualiza estudantes de outros grupos no TCC
    And I log in as "teacher1"
    And I follow "Course1"
    And I navigate to "TCC: Entrega de Atividades" node in "Reports > UNA-SUS"
    Then I should not see "Filtrar Cohorts:"
    And I press "Gerar relatório"
    And I should see "Student s1"
    And I should see "Student s2"
    And I should see "Student s3"
    And I should see "Student s4"
    And I should not see "Student s5"
    And I should not see "Student s12"
