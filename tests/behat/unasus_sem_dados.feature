 @unasus @report_unasus @javascript
Feature: Relatórios sem dados de entrega
  Verificar o comportamento dos relatórios quando nenhum estudante iniciou qualquer atividade.

Background:
  Given the following "users" exist:
    | username  | firstname | lastname | email                 |
    | student1  | Student   | s1       | student1@example.com  |
    | student2  | Student   | s2       | student2@example.com  |
    | student3  | Student   | s3       | student3@example.com  |
    | student4  | Student   | s4       | student4@example.com  |
    | student5  | Student   | s5       | student5@example.com  |
    | student6  | Student   | s6       | student6@example.com  |
    | student7  | Student   | s7       | student7@example.com  |
    | student8  | Student   | s8       | student8@example.com  |
    | student9  | Student   | s9       | student9@example.com  |
    | student10 | Student   | s10      | student10@example.com |
    | student11 | Student   | s11      | student11@example.com |
    | student12 | Student   | s12      | student12@example.com |
    | teacher1  | Teacher   | t1       | teacher1@example.com  |
    | teacher2  | Teacher   | t2       | teacher2@example.com  |
    | teacher3  | Teacher   | t3       | teacher3@example.com  |

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
    | report_unasus_prazo_maximo_entrega        | 10 |
    | report_unasus_prazo_maximo_avaliacao      | 5  |
    | report_unasus_prazo_avaliacao             | 1  |
    | report_unasus_tolerancia_potencial_evasao | 1  |

  And the following "cohorts" exist:
    | name           | idnumber | contextlevel | reference |
    | Cohort teacher | CHt      | Category     | CAT1      |
    | Cohort student | CHs      | Category     | CAT1      |

  # Unix timestamp: 946684800 = 1 jan 2000, 978307200 = 1 jan 2001, 2147483647 = futuro distante
  And the following "activities" exist:
    | activity | course | idnumber | name                  | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
    | assign   | C1     | a1       | Test assignment one   | Submit something! | 100   | 1                                   | 2147483647         | 1          |
    | assign   | C1     | a2       | Test assignment two   | Submit something! | 100   | 1                                   | 978307200          | 1          |
    | assign   | C1     | a3       | Test assignment three | Submit something! | 100   | 1                                   | 0                  | 1          |
    | assign   | C1     | a4       | Test assignment four  | Submit something! | 100   | 1                                   | 946684800          | 1          |

  And the following "activities" exist:
    | activity | course | idnumber | name            | intro       | scale | completion | completionexpected |
    | forum    | C1     | f1       | Test forum one  | Forum intro | 100   | 1          | 2147483647         |
    | forum    | C1     | f2       | Test forum two  | Forum intro | 100   | 1          | 978307200          |

  And the following "activities" exist:
    | activity | course | idnumber | name          | intro      | grade | completion | completionexpected |
    | quiz     | C1     | q1       | Test quiz one | Quiz intro | 100   | 1          | 978307200          |

  And the following "activities" exist:
    | activity | course | idnumber | name              | intro    | scale | completion | completionexpected |
    | data     | C1     | d1       | Test database one | DB intro | 100   | 1          | 978307200          |

  And the following "activities" exist:
    | activity | course | idnumber | name         | intro     | completion | completionexpected |
    | lti      | C1     | l1       | Test lti one | LTI intro | 1          | 978307200          |

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
    | teacher3 | editingteacher | Category     | CAT1      |

  And the following users are added to cohorts:
    | user      | cohort  |
    | teacher1  | teacher |
    | teacher2  | teacher |
    | teacher3  | teacher |
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

  And a basic unasus tutoria environment exists:

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

  # Nenhuma submissao realizada. Todos os estudantes estao sem atividade iniciada.

  @javascript
Scenario: sem_dados - entrega_de_atividades mostra atividades sem submissao
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: entrega de atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Atividade a3 sem deadline -> sem prazo
    Then I should see "sem prazo"
    # Atividades com prazo passado e sem submissao -> aparecem no relatorio
    And I should see "Test assignment two"
    # Atividade a1 com prazo futuro -> aparece no relatorio
    And I should see "Test assignment one"

  @javascript
Scenario: sem_dados - estudante_sem_atividade_postada lista todos os estudantes
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: atividades não postadas e sem nota" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Todos os estudantes sem submissao devem aparecer na lista
    Then I should see "Student"
    # Atividades com prazo passado e sem entrega devem aparecer
    And I should see "Test assignment two"

  @javascript
Scenario: sem_dados - estudante_sem_atividade_avaliada nao lista ninguem
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: atividades não avaliadas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Nenhum estudante entregou -> nao ha nada para avaliar -> nenhum aluno listado
    Then I should not see "Student"

  @javascript
Scenario: sem_dados - avaliacoes_em_atraso sem pendencias de avaliacao
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Síntese: avaliações em atraso" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Nenhuma submissao -> nenhuma avaliacao pendente -> tutores aparecem com zero pendencias
    Then I should see "Teacher"
    And I should not see "Student"

  @javascript
Scenario: sem_dados - atividades_vs_notas mostra estados sem entrega
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: atribuição de notas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Atividades com prazo passado e sem entrega -> nao entregue
    Then I should see "não entregue"
    # Atividade sem deadline -> sem prazo
    And I should see "sem prazo"
    # Atividade com prazo futuro -> no prazo
    And I should see "no prazo"

  @javascript
Scenario: sem_dados - boletim exibe atividades sem notas
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Atividades devem aparecer no cabecalho mesmo sem notas atribuidas
    Then I should see "Test assignment one"
    And I should see "Test assignment two"

  @javascript
Scenario: sem_dados - modulos_concluidos sem nenhuma conclusao
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: Conclusão" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Atividades aparecem no cabecalho mesmo sem conclusoes
    Then I should see "Test assignment one"
    # Todos os estudantes aparecem sem marca de conclusao
    And I should see "Student"
