@unasus @report_unasus @uso_sistema_tutor
Feature: Relatório UNA-SUS de uso do sistema pelo tutor com período fixo
  Para validar o relatório de horas de uso do tutor
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
    | username | course | datetime            | action  |
    | tutor1   | c1     | 2026-03-10 10:00:00 | viewed  |
    | tutor1   | c1     | 2026-03-10 10:31:00 | updated |
    | tutor2   | c1     | 2026-03-15 15:00:00 | viewed  |
    | tutor2   | c1     | 2026-03-15 15:31:00 | updated |
    | tutor1   | c1     | 2026-04-05 09:00:00 | viewed  |
    | tutor1   | c1     | 2026-04-05 09:31:00 | updated |

  @javascript @uso_sistema_tutor
  Scenario: uso_sistema_tutor com período fixo mostra tutores e colunas de média e total
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (horas)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "01/03/2026"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    Then I should see "Tutor One"
    And I should see "Tutor Two"
    And I should see "10/03/26"
    And I should see "15/03/26"
    And I should see "Media"
    And I should see "Total"

  @javascript @uso_sistema_tutor
  Scenario: uso_sistema_tutor não mostra dia de abril quando o período é março de 2026
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (horas)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "01/03/2026"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    Then I should see "10/03/26"
    And I should see "15/03/26"
    And I should not see "05/04/26"

  @javascript @uso_sistema_tutor
  Scenario: usuário sem view_all não visualiza relatório restrito uso_sistema_tutor
    And I log in as "tutor1"
    And I follow "Course1"
    Then I should not see "Uso do sistema pelo tutor (horas)"

  @javascript @uso_sistema_tutor
  Scenario: uso_sistema_tutor exibe aviso para formato de data inválido
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (horas)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "2026-03-01"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    Then I should not see "10/03/26"

  @javascript @uso_sistema_tutor
  Scenario: uso_sistema_tutor exibe opção de exportar CSV para usuário com view_all
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (horas)" node in "Reports > UNA-SUS"
    Then I should see "Exportar para CSV"

  @uso_sistema_tutor @csv
  Scenario: uso_sistema_tutor exporta CSV com conteúdo esperado
    And I log in as "manager1"
    And I export the unasus report "uso_sistema_tutor" as csv for course "c1" with params:
      | name       | value      |
      | data_inicio| 01/03/2026 |
      | data_fim   | 31/03/2026 |
    Then the exported unasus csv should contain "Tutores"
    And the exported unasus csv should contain "Media"
    And the exported unasus csv should contain "Total"
    And the exported unasus csv should have a row containing:
      | value     |
      | Tutor One |
