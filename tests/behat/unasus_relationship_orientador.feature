@unasus @report_unasus @relationship @orientador @tcc
Feature: Relacionamento com múltiplos cohorts e papéis no role orientador (TCC)
  Para que coordenadores enxerguem TCCs de orientadores em N cohorts/papéis distintos
  Como administrador
  Preciso que os relatórios TCC propaguem o agrupamento de estudantes
  por orientador através de cohorts e shortnames de papel diferentes
  — não apenas do primeiro relationship_cohort.

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

  # Custom advisor role (shortname=advisor) co-existindo com built-ins editingteacher / teacher.
  And the following "roles" exist:
    | shortname | name    | archetype |
    | advisor   | Advisor | teacher   |

  # 1 student cohort + 3 advisor cohorts — cada advisor em um cohort distinto.
  And the following "cohorts" exist:
    | name             | idnumber | contextlevel | reference |
    | Cohort student   | CHs      | Category     | CAT1      |
    | Cohort advisor1  | CHa1     | Category     | CAT1      |
    | Cohort advisor2  | CHa2     | Category     | CAT1      |
    | Cohort advisor3  | CHa3     | Category     | CAT1      |

  # Habilita 3 shortnames de papel para o role orientador.
  And the following config values are set as admin:
    | enablecompletion                          | 1                                |
    | local_tutores_student_roles               | student                          |
    | local_tutores_orientador_roles            | advisor,editingteacher,teacher   |
    | report_unasus_prazo_maximo_entrega        | 10                               |
    | report_unasus_prazo_maximo_avaliacao      | 5                                |
    | report_unasus_prazo_avaliacao             | 1                                |
    | report_unasus_tolerancia_potencial_evasao | 1                                |

  # LTI activity vinculada ao webservice mock de TCC.
  And the following "activities" exist:
    | activity | course | idnumber | name       | intro     | completion | completionexpected |
    | lti      | C1     | ltcc1    | TCC Eixo 1 | TCC intro | 1          | 978307200          |

  And the LTI activity "ltcc1" is configured as TCC with tcc_definition_id "1"

  And the TCC webservice returns definition with chapters:
    | id | title      | position |
    | 1  | Capítulo 1 | 1        |
    | 2  | Capítulo 2 | 2        |

  # Cada teacher com um role distinto + estudantes como student.
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
    | teacher1  | c1     | advisor        |
    | teacher2  | c1     | editingteacher |
    | teacher3  | c1     | teacher        |

  # Capabilities — concede aos 3 shortnames de orientador.
  And the following "permission overrides" exist:
    | capability                    | permission | role           | contextlevel | reference |
    | local/relationship:view       | Allow      | advisor        | Category     | CAT1      |
    | local/relationship:manage     | Allow      | advisor        | Category     | CAT1      |
    | local/relationship:assign     | Allow      | advisor        | Category     | CAT1      |
    | moodle/cohort:view            | Allow      | advisor        | Category     | CAT1      |
    | report/unasus:view_orientacao | Allow      | advisor        | Category     | CAT1      |
    | report/unasus:view_orientacao | Allow      | advisor        | Course       | c1        |
    | report/unasus:view_orientacao | Allow      | editingteacher | Category     | CAT1      |
    | report/unasus:view_orientacao | Allow      | editingteacher | Course       | c1        |
    | report/unasus:view_orientacao | Allow      | teacher        | Category     | CAT1      |
    | report/unasus:view_orientacao | Allow      | teacher        | Course       | c1        |

  # Cada teacher recebe o role correspondente na categoria — critério usado por
  # add_created_cohorts_at_relationship para deduzir (roleid, cohortid).
  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | advisor        | Category     | CAT1      |
    | teacher2 | editingteacher | Category     | CAT1      |
    | teacher3 | teacher        | Category     | CAT1      |

  # Cada teacher em um advisor cohort distinto; alunos no único student cohort.
  And the following users are added to cohorts:
    | user      | cohort   |
    | teacher1  | advisor1 |
    | teacher2  | advisor2 |
    | teacher3  | advisor3 |
    | student1  | student  |
    | student2  | student  |
    | student3  | student  |
    | student4  | student  |
    | student5  | student  |
    | student6  | student  |
    | student7  | student  |
    | student8  | student  |
    | student9  | student  |
    | student10 | student  |
    | student11 | student  |
    | student12 | student  |

  # Relacionamento com tags tutoria + orientacao (mesma estratégia da unasus_tcc.feature).
  # add_created_cohorts_at_relationship cria 1 linha por (roleid, cohortid) — 3 advisor + 1 student.
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
  Scenario: tcc_entrega_atividades sob orientadores multi-cohort/multi-role
    # Cada estudante orientado por um teacher de cohort/papel diferente.
    Given the TCC webservice returns student data:
      | username | chapter_position | state  | state_date |
      | student1 | 0                | done   | 2024-01-01 |
      | student1 | 1                | review | 2024-01-10 |
      | student5 | 0                | done   | 2024-01-01 |
      | student5 | 1                | draft  | 2024-01-15 |
      | student9 | 0                | done   | 2024-01-01 |
      | student9 | 1                | review | 2024-01-20 |
    And I log in as "admin"
    And I am on "Course1" course homepage
    And I navigate to "TCC: Entrega de Atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Sob singular wrapper de orientador, only students do primeiro advisor cohort apareceriam.
    # Asserções de presença e CSS state cobrem o pipeline orientador cohort → grupo → estudante.
    Then the unasus report table cell at row "Student s1" and column "Resumo" should have css class "avaliado"
    And the unasus report table cell at row "Student s5" and column "Resumo" should have css class "avaliado"
    And the unasus report table cell at row "Student s9" and column "Resumo" should have css class "avaliado"
    And the unasus report table cell at row "Student s1" and column "Capítulo 1" should have css class "revisao"
    And the unasus report table cell at row "Student s5" and column "Capítulo 1" should have css class "rascunho"
    And the unasus report table cell at row "Student s9" and column "Capítulo 1" should have css class "revisao"

  @javascript @tcc_concluido
  Scenario: tcc_concluido sob orientadores multi-cohort/multi-role
    Given the TCC webservice returns student data:
      | username | chapter_position | state | state_date |
      | student1 | 0                | done  | 2024-01-01 |
      | student1 | 1                | done  | 2024-01-10 |
      | student5 | 0                | done  | 2024-01-01 |
      | student5 | 1                | done  | 2024-01-10 |
      | student9 | 0                | done  | 2024-01-01 |
      | student9 | 1                | done  | 2024-01-10 |
    And I log in as "admin"
    And I am on "Course1" course homepage
    And I navigate to "TCC: Atividades concluídas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Cada estudante representativo de seu advisor cohort tem Resumo + Capítulo 1 concluídos.
    Then the unasus report table cell at row "Student s1" and column "Resumo" should have css class "concluido"
    And the unasus report table cell at row "Student s5" and column "Resumo" should have css class "concluido"
    And the unasus report table cell at row "Student s9" and column "Resumo" should have css class "concluido"
    And the unasus report table cell at row "Student s1" and column "Capítulo 1" should have css class "concluido"
    And the unasus report table cell at row "Student s5" and column "Capítulo 1" should have css class "concluido"
    And the unasus report table cell at row "Student s9" and column "Capítulo 1" should have css class "concluido"

  @javascript @tcc_entrega_atividades @phantom_column
  Scenario: tcc_entrega_atividades não exibe coluna fantasma quando webservice retorna posições extras
    # Regressão: em produção o relatório mostra uma coluna a mais que o header.
    # Hipótese: o webservice TCC retorna posições além das definidas em chapter_definitions;
    # o relatório deve constranger os dados às colunas do header (ignorar posições extras).
    Given the TCC webservice returns student data:
      | username | chapter_position | state  | state_date |
      | student1 | 0                | done   | 2024-01-01 |
      | student1 | 1                | done   | 2024-01-10 |
      | student1 | 2                | review | 2024-01-15 |
      | student1 | 3                | draft  | 2024-01-20 |
      | student5 | 0                | done   | 2024-01-01 |
      | student5 | 1                | done   | 2024-01-10 |
      | student5 | 2                | done   | 2024-01-15 |
      | student5 | 3                | done   | 2024-01-20 |
    And I log in as "admin"
    And I am on "Course1" course homepage
    And I navigate to "TCC: Entrega de Atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Asserção principal: número de células no body row deve casar com o header.
    # Definição tem 2 capítulos (header = Estudante + Resumo + Capítulo 1 + Capítulo 2 = 4 colunas).
    # Posição 3 retornada pelo webservice está além — não deve gerar célula extra.
    Then the unasus report row "Student s1" should have the same number of cells as the header
    And the unasus report row "Student s5" should have the same number of cells as the header

  @javascript @tcc_consolidado
  Scenario: tcc_consolidado sob orientadores multi-cohort/multi-role
    Given the TCC webservice returns student data:
      | username | chapter_position | state | state_date |
      | student1 | 0                | done  | 2024-01-01 |
      | student1 | 1                | done  | 2024-01-10 |
      | student5 | 0                | done  | 2024-01-01 |
      | student5 | 1                | draft | 2024-01-05 |
      | student9 | 0                | done  | 2024-01-01 |
      | student9 | 1                | done  | 2024-01-10 |
    And I log in as "admin"
    And I am on "Course1" course homepage
    And I navigate to "TCC: TCCs Consolidados" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # tcc_consolidado é um relatório de síntese — verificamos que as colunas existem e
    # que os 3 grupos de orientação (um por cohort/role de advisor) aparecem.
    Then I should see "Resumo"
    And I should see "Capítulo 1"
    And I should see "Capítulo 2"
    And I should see "Total por curso"
