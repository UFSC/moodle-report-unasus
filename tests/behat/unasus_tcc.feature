 @unasus @report_unasus @tcc
Feature: Relatórios TCC UNA-SUS
  Para acompanhar o progresso dos estudantes nos trabalhos de conclusão de curso
  Como orientador ou coordenador
  Preciso gerar e visualizar os relatórios de TCC com dados provenientes do webservice

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

  And the following "categories" exist:
    | name       | category | idnumber |
    | Category 1 | 0        | CAT1     |

  And the following "courses" exist:
    | fullname | shortname | category | groupmode | enablecompletion |
    | Course1  | c1        | CAT1     | 1         | 1                |

  # Dedicated advisor role (shortname = advisor) — sole role for TCC orientation
  And the following "roles" exist:
    | shortname | name    | archetype |
    | advisor   | Advisor | teacher   |

  # Only two cohorts: advisor (teachers) and student — no tutoria cohort needed for TCC
  And the following "cohorts" exist:
    | name           | idnumber | contextlevel | reference |
    | Cohort advisor | CHa      | Category     | CAT1      |
    | Cohort student | CHs      | Category     | CAT1      |

  And the following config values are set as admin:
    | enablecompletion                          | 1       |
    | local_tutores_student_roles               | student |
    | local_tutores_orientador_roles            | advisor |
    | report_unasus_prazo_maximo_entrega        | 10      |
    | report_unasus_prazo_maximo_avaliacao      | 5       |
    | report_unasus_prazo_avaliacao             | 1       |
    | report_unasus_tolerancia_potencial_evasao | 1       |

  # LTI activity that will be linked to the TCC mock webservice
  And the following "activities" exist:
    | activity | course | idnumber | name       | intro     | completion | completionexpected |
    | lti      | C1     | ltcc1    | TCC Eixo 1 | TCC intro | 1          | 978307200          |

  And the LTI activity "ltcc1" is configured as TCC with tcc_definition_id "1"

  # Chapter definitions returned by mock webservice (position 0 = abstract/resumo)
  And the TCC webservice returns definition with chapters:
    | id | title      | position |
    | 1  | Resumo     | 0        |
    | 2  | Capítulo 1 | 1        |
    | 3  | Capítulo 2 | 2        |

  # Only students enrolled in the course (teachers have only category-level advisor role)
  And the following "course enrolments" exist:
    | user      | course | role    |
    | student1  | c1     | student |
    | student2  | c1     | student |
    | student3  | c1     | student |
    | student4  | c1     | student |
    | student5  | c1     | student |
    | student6  | c1     | student |
    | student7  | c1     | student |
    | student8  | c1     | student |
    | student9  | c1     | student |
    | student10 | c1     | student |
    | student11 | c1     | student |
    | student12 | c1     | student |
    | teacher1  | c1     | advisor |
    | teacher2  | c1     | advisor |
    | teacher3  | c1     | advisor |

  # Advisor permissions for TCC orientation reports
  And the following "permission overrides" exist:
    | capability                    | permission | role    | contextlevel | reference |
    | local/relationship:view       | Allow      | advisor | Category     | CAT1      |
    | local/relationship:manage     | Allow      | advisor | Category     | CAT1      |
    | local/relationship:assign     | Allow      | advisor | Category     | CAT1      |
    | moodle/cohort:view            | Allow      | advisor | Category     | CAT1      |
    | report/unasus:view_orientacao | Allow      | advisor | Category     | CAT1      |
    | report/unasus:view_orientacao | Allow      | advisor | Course       | c1        |

  # Teachers hold only the advisor role at category level (single role → single cohort mapping)
  And the following "role assigns" exist:
    | user     | role    | contextlevel | reference |
    | teacher1 | advisor | Category     | CAT1      |
    | teacher2 | advisor | Category     | CAT1      |
    | teacher3 | advisor | Category     | CAT1      |

  # Each user type in exactly one cohort (prevents duplicate relationship_cohort entries)
  And the following users are added to cohorts:
    | user      | cohort  |
    | teacher1  | advisor |
    | teacher2  | advisor |
    | teacher3  | advisor |
    | student1  | student |
    | student2  | student |
    | student3  | student |
    | student4  | student |
    | student5  | student |
    | student6  | student |
    | student7  | student |
    | student8  | student |
    | student9  | student |
    | student10 | student |
    | student11 | student |
    | student12 | student |

  # Relationship tagged with both tags: grupo_tutoria (required by loops.php to find student cohort)
  # and grupo_orientacao (required by TCC reports to find orientation groups)
  And a basic unasus tutoria environment exists:
  And instance the tag "grupo_orientacao" at relationship "relationship1"

  And the following tutoria memberships exist:
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

  @javascript @tcc_entrega_atividades
  Scenario: tcc_entrega_atividades - exibe capítulos por estudante com estados corretos
    Given the TCC webservice returns student data:
      | username | chapter_position | state  | state_date |
      | student1 | 0                | done   | 2024-01-01 |
      | student1 | 1                | review | 2024-01-10 |
      | student1 | 2                | draft  | 2024-01-15 |
      | student2 | 0                | null   |            |
      | student2 | 1                | null   |            |
      | student2 | 2                | null   |            |
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "TCC: Entrega de Atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # CSS class cobre todos os estados: avaliado, revisao, rascunho, nao_aplicado.
    Then the unasus report table cell at row "Student s1" and column "Resumo" should have css class "avaliado"
    And the unasus report table cell at row "Student s1" and column "Capítulo 1" should have css class "revisao"
    And the unasus report table cell at row "Student s1" and column "Capítulo 2" should have css class "rascunho"
    And the unasus report table cell at row "Student s2" and column "Resumo" should have css class "nao_aplicado"

  @javascript @tcc_concluido
  Scenario: tcc_concluido - capítulo avaliado vs não avaliado por estudante
    Given the TCC webservice returns student data:
      | username | chapter_position | state | state_date |
      | student1 | 0                | done  | 2024-01-01 |
      | student1 | 1                | done  | 2024-01-10 |
      | student1 | 2                | draft | 2024-01-15 |
      | student2 | 0                | null  |            |
      | student2 | 1                | null  |            |
      | student2 | 2                | null  |            |
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "TCC: Atividades concluídas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # CSS class: dois capítulos concluidos e um nao_concluido para student1; student2 sem dados.
    Then the unasus report table cell at row "Student s1" and column "Resumo" should have css class "concluido"
    And the unasus report table cell at row "Student s1" and column "Capítulo 1" should have css class "concluido"
    And the unasus report table cell at row "Student s1" and column "Capítulo 2" should have css class "nao_concluido"
    And the unasus report table cell at row "Student s2" and column "Resumo" should have css class "nao_concluido"

  @javascript @tcc_consolidado
  Scenario: tcc_consolidado - síntese de progresso por grupo de orientação
    Given the TCC webservice returns student data:
      | username | chapter_position | state | state_date |
      | student1 | 0                | done  | 2024-01-01 |
      | student1 | 1                | done  | 2024-01-10 |
      | student1 | 2                | done  | 2024-01-20 |
      | student2 | 0                | done  | 2024-01-01 |
      | student2 | 1                | draft | 2024-01-05 |
      | student2 | 2                | null  |            |
      | student3 | 0                | null  |            |
      | student3 | 1                | null  |            |
      | student3 | 2                | null  |            |
      | student4 | 0                | null  |            |
      | student4 | 1                | null  |            |
      | student4 | 2                | null  |            |
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "TCC: TCCs Consolidados" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "Resumo"
    And I should see "Capítulo 1"
    And I should see "Capítulo 2"
    And I should see "Total por curso"

  @tcc_consolidado @csv
  Scenario: tcc_consolidado exporta CSV com dados esperados
    Given the TCC webservice returns student data:
      | username | chapter_position | state | state_date |
      | student1 | 0                | done  | 2024-01-01 |
      | student1 | 1                | done  | 2024-01-10 |
      | student2 | 0                | done  | 2024-01-01 |
      | student2 | 1                | draft | 2024-01-05 |
      | student5 | 0                | null  |            |
      | student5 | 1                | null  |            |
    And I log in as "admin"
    And I export the unasus report "tcc_consolidado" as csv for course "c1" with params:
      | name       | value       |
      | modulos[0] | courseid:c1 |
    Then the exported unasus csv should contain "Resumo"
    And the exported unasus csv should contain "Capítulo 1"
    And the exported unasus csv should have a row containing:
      | value      |
      | Teacher t1 |

  @javascript @advisor_scope @tcc_entrega_atividades @tcc_concluido
  Scenario Outline: orientador teacher1 vê apenas estudantes do próprio grupo de orientação nos relatórios TCC
    Given the TCC webservice returns student data:
      | username | chapter_position | state | state_date |
      | student1 | 0                | done  | 2024-01-01 |
      | student2 | 0                | done  | 2024-01-01 |
      | student5 | 0                | done  | 2024-01-01 |
      | student9 | 0                | done  | 2024-01-01 |
    And I log in as "teacher1"
    And I follow "Course1"
    And I navigate to "<reportnode>" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "Student s1"
    And I should see "Student s2"
    And I should not see "Student s5"
    And I should not see "Student s9"
    And I log out

    Examples:
      | reportnode                   |
      | TCC: Entrega de Atividades   |
      | TCC: Atividades concluídas   |
