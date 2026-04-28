@unasus @report_unasus @acesso_tutor
Feature: Relatório UNA-SUS de acesso do tutor com período fixo
  Para validar o relatório de acesso diário do tutor
  Como usuário com capability report/unasus:view_all
  Preciso visualizar resultados determinísticos no período fixo de março de 2026

Background:
  Given the following "users" exist:
    | username | firstname | lastname | email                |
    | student1 | Student   | One      | student1@example.com |
    | student2 | Student   | Two      | student2@example.com |
    | tutor1   | Tutor     | One      | tutor1@example.com   |
    | tutor2   | Tutor     | Two      | tutor2@example.com   |
    | manager1 | Manager   | One      | manager1@example.com |

  And the following "categories" exist:
    | name       | category | idnumber |
    | Category 1 | 0        | CAT1     |

  And the following "courses" exist:
    | fullname | shortname | category | enablecompletion |
    | Course1  | c1        | CAT1     | 1                |

  And the following config values are set as admin:
    | enablecompletion               | 1              |
    | enablestats                    | 1              |
    | local_tutores_student_roles    | student        |
    | local_tutores_tutor_roles      | editingteacher |
    | local_tutores_orientador_roles | editingteacher |

  And the following "activities" exist:
    | activity | course | idnumber | name                  | intro                | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
    | assign   | C1     | a_logs   | Tutor logs assignment | Assignment for setup | 100   | 1                                   | 1772323200         | 1          |

  And the following "cohorts" exist:
    | name           | idnumber | contextlevel | reference |
    | Cohort teacher | CHt      | Category     | CAT1      |
    | Cohort student | CHs      | Category     | CAT1      |

  And the following "course enrolments" exist:
    | user     | course | role           |
    | student1 | c1     | student        |
    | student2 | c1     | student        |
    | tutor1   | c1     | editingteacher |
    | tutor2   | c1     | editingteacher |
    | manager1 | c1     | manager        |

  And the following "permission overrides" exist:
    | capability                    | permission | role           | contextlevel | reference |
    | local/relationship:view       | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage     | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign     | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view            | Allow      | editingteacher | Category     | CAT1      |
    | report/unasus:view_tutoria    | Allow      | editingteacher | Course       | c1        |
    | report/unasus:view_all        | Allow      | manager        | Course       | c1        |

  And the following "role assigns" exist:
    | user   | role           | contextlevel | reference |
    | tutor1 | editingteacher | Category     | CAT1      |
    | tutor2 | editingteacher | Category     | CAT1      |

  And the following users are added to cohorts:
    | user     | cohort  |
    | tutor1   | teacher |
    | tutor2   | teacher |
    | student1 | student |
    | student2 | student |

  And a basic unasus tutoria environment exists:

  And the following tutoria memberships exist:
    | user     | group               |
    | tutor1   | relationship_group1 |
    | student1 | relationship_group1 |
    | tutor2   | relationship_group2 |
    | student2 | relationship_group2 |

  And the following tutor report logs exist:
    | username | course | datetime   | action  |
    | tutor1   | c1     | 1773136800 | viewed  |
    | tutor1   | c1     | 1773138660 | updated |
    | tutor2   | c1     | 1773586800 | viewed  |
    | tutor2   | c1     | 1773588660 | updated |
    | tutor1   | c1     | 1775379600 | viewed  |
    | tutor1   | c1     | 1775381460 | updated |

  @javascript @acesso_tutor
  Scenario: acesso_tutor com período fixo mostra tutores e estados de acesso no mês
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (acessos)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "01/03/2026"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    # Per-cell: confirma que cada tutor/dia mostra o texto correto.
    Then the unasus report table should have "Sim" at row "Tutor One" and column "10/03/26"
    And the unasus report table should have "Não" at row "Tutor One" and column "15/03/26"
    And the unasus report table should have "Não" at row "Tutor Two" and column "10/03/26"
    And the unasus report table should have "Sim" at row "Tutor Two" and column "15/03/26"
    # CSS class confirma acessou/nao_acessou por tutor e dia.
    And the unasus report table cell at row "Tutor One" and column "10/03/26" should have css class "acessou"
    And the unasus report table cell at row "Tutor One" and column "15/03/26" should have css class "nao_acessou"
    And the unasus report table cell at row "Tutor Two" and column "10/03/26" should have css class "nao_acessou"
    And the unasus report table cell at row "Tutor Two" and column "15/03/26" should have css class "acessou"

  @javascript @acesso_tutor
  Scenario: acesso_tutor não mostra dia de abril quando o período é março de 2026
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (acessos)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "01/03/2026"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    Then I should see "10/03/26"
    And I should see "15/03/26"
    And I should not see "05/04/26"

  @javascript @acesso_tutor
  Scenario: usuário sem view_all não visualiza relatório restrito acesso_tutor
    And I log in as "tutor1"
    And I follow "Course1"
    Then I should not see "Uso do sistema pelo tutor (acessos)"

  @javascript @acesso_tutor
  Scenario: acesso_tutor exibe aviso para intervalo de datas inválido
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (acessos)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "31/03/2026"
    And I set the field "data_fim" to "01/03/2026"
    And I press "Gerar relatório"
    Then I should not see "10/03/26"

  @javascript @acesso_tutor
  Scenario: acesso_tutor exibe opção de exportar CSV para usuário com view_all
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (acessos)" node in "Reports > UNA-SUS"
    Then I should see "Exportar para CSV"

  @acesso_tutor @csv
  Scenario: acesso_tutor exporta CSV com conteúdo esperado
    And I log in as "manager1"
    And I export the unasus report "acesso_tutor" as csv for course "c1" with params:
      | name       | value      |
      | data_inicio| 01/03/2026 |
      | data_fim   | 31/03/2026 |
    Then the exported unasus csv should contain "Tutores"
    And the exported unasus csv should contain "10/03/26"
    And the exported unasus csv should contain "15/03/26"
    And the exported unasus csv should have a row containing:
      | value     |
      | Tutor One |
