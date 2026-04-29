 @unasus @report_unasus @javascript
Feature: Relatórios UNA-SUS: escopo de visualização do tutor
  Verificar que o tutor visualiza apenas os estudantes do seu grupo de tutoria
  e que um grupo sem estudantes não provoca erro.

Background:
  Given the following "users" exist:
    | username | firstname | lastname | email                |
    | student1 | Student   | s1       | student1@example.com |
    | student2 | Student   | s2       | student2@example.com |
    | student3 | Student   | s3       | student3@example.com |
    | student4 | Student   | s4       | student4@example.com |
    | student5 | Student   | s5       | student5@example.com |
    | student6 | Student   | s6       | student6@example.com |
    | student7 | Student   | s7       | student7@example.com |
    | student8 | Student   | s8       | student8@example.com |
    | teacher1 | Teacher   | t1       | teacher1@example.com |
    | teacher2 | Teacher   | t2       | teacher2@example.com |
    | teacher3 | Teacher   | t3       | teacher3@example.com |

  And the following "categories" exist:
    | name       | category | idnumber |
    | Category 1 | 0        | CAT1     |

  And the following "courses" exist:
    | fullname | shortname | category | groupmode | enablecompletion |
    | Course1  | c1        | CAT1     | 1         | 1                |

  And the following config values are set as admin:
    | enablecompletion                          | 1              |
    | local_tutores_student_roles               | student        |
    | local_tutores_tutor_roles                 | editingteacher |
    | local_tutores_orientador_roles            | editingteacher |
    | report_unasus_prazo_maximo_entrega        | 10             |
    | report_unasus_prazo_maximo_avaliacao      | 5              |
    | report_unasus_prazo_avaliacao             | 1              |
    | report_unasus_tolerancia_potencial_evasao | 1              |

  And the following "cohorts" exist:
    | name           | idnumber | contextlevel | reference |
    | Cohort teacher | CHt      | Category     | CAT1      |
    | Cohort student | CHs      | Category     | CAT1      |

  And the following "activities" exist:
    | activity | course | idnumber | name                | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
    | assign   | C1     | a1       | Test assignment one | Submit something! | 100   | 1                                   | 978307200          | 1          |

  And the following "course enrolments" exist:
    | user     | course | role           |
    | student1 | c1     | student        |
    | student2 | c1     | student        |
    | student3 | c1     | student        |
    | student4 | c1     | student        |
    | student5 | c1     | student        |
    | student6 | c1     | student        |
    | student7 | c1     | student        |
    | student8 | c1     | student        |
    | teacher1 | c1     | editingteacher |
    | teacher2 | c1     | editingteacher |
    | teacher3 | c1     | editingteacher |

  And the following "permission overrides" exist:
    | capability                    | permission | role           | contextlevel | reference |
    | local/relationship:view       | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage     | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign     | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view            | Allow      | editingteacher | Category     | CAT1      |
    | report/unasus:view_tutoria    | Allow      | editingteacher | Course       | c1        |

  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | editingteacher | Category     | CAT1      |
    | teacher2 | editingteacher | Category     | CAT1      |
    | teacher3 | editingteacher | Category     | CAT1      |

  And the following users are added to cohorts:
    | user     | cohort  |
    | teacher1 | teacher |
    | teacher2 | teacher |
    | teacher3 | teacher |
    | student1 | student |
    | student2 | student |
    | student3 | student |
    | student4 | student |
    | student5 | student |
    | student6 | student |
    | student7 | student |
    | student8 | student |

  And a basic unasus tutoria environment exists:

  And the following tutoria memberships exist:
    | user     | group               |
    | teacher1 | relationship_group1 |
    | student1 | relationship_group1 |
    | student2 | relationship_group1 |
    | student3 | relationship_group1 |
    | student4 | relationship_group1 |
    | teacher2 | relationship_group2 |
    | student5 | relationship_group2 |
    | student6 | relationship_group2 |
    | student7 | relationship_group2 |
    | student8 | relationship_group2 |
    | teacher3 | relationship_group3 |
    # relationship_group3 sem estudantes — intencional para o cenário de grupo vazio

  @javascript @escopo_tutor
  Scenario: tutor_scope - tutor ve apenas estudantes do seu grupo
    And I log in as "teacher1"
    And I follow "Course1"
    And I navigate to "Lista: atividades não postadas e sem nota" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # teacher1 pertence ao group1: deve ver seus estudantes (s1–s4)
    Then I should see "Student s1"
    # teacher1 NAO deve ver estudantes do group2 (teacher2)
    And I should not see "Student s5"
    And I should not see "Student s6"

  @javascript @escopo_tutor
  Scenario: tutor_scope - tutor com grupo sem estudantes nao causa erro
    And I log in as "teacher3"
    And I follow "Course1"
    And I navigate to "Lista: atividades não postadas e sem nota" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # relationship_group3 nao tem estudantes — relatório deve carregar sem erro
    Then I should not see "Student"
    And I should not see "Notice"
    And I should not see "Warning"
    And I should not see "Fatal error"
