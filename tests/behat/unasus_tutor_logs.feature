@unasus @report_unasus @javascript @tutor_logs
Feature: Relatórios UNA-SUS restritos de logs de tutor com período fixo
  Para validar os relatórios baseados em logs de tutores
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

  And I add the user "tutor1" with cohort "teacher" to cohort members
  And I add the user "tutor2" with cohort "teacher" to cohort members
  And I add the user "student1" with cohort "student" to cohort members
  And I add the user "student2" with cohort "student" to cohort members

  And the following relationship "relationships" exist:
    | name          | category |
    | relationship1 | CAT1     |

  And the following relationship group "relationship_groups" exist:
    | name                | relationship  |
    | relationship_group1 | relationship1 |
    | relationship_group2 | relationship1 |

  And instance the tag "grupo_tutoria" at relationship "relationship1"
  And add created cohorts at relationship "relationship1"

  And the following users belongs to the relationship group as "relationship_members":
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

  @javascript
  Scenario: acesso_tutor com período fixo mostra tutores e estados de acesso no mês
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (acessos)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "01/03/2026"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    Then I should see "Tutor One"
    And I should see "Tutor Two"
    And I should see "10/03/26"
    And I should see "15/03/26"
    And I should see "Sim"
    And I should see "Não"

  @javascript
  Scenario: uso_sistema_tutor com período fixo mostra colunas de média e total
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (horas)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "01/03/2026"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    Then I should see "Tutor One"
    And I should see "Tutor Two"
    And I should see "Media"
    And I should see "Total"

  @javascript
  Scenario: logs fora de março não aparecem no cabeçalho quando o período é março de 2026
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Uso do sistema pelo tutor (horas)" node in "Reports > UNA-SUS"
    And I set the field "data_inicio" to "01/03/2026"
    And I set the field "data_fim" to "31/03/2026"
    And I press "Gerar relatório"
    Then I should see "10/03/26"
    And I should see "15/03/26"
    And I should not see "05/04/26"

  @javascript
  Scenario: usuário sem view_all não visualiza relatórios restritos de logs de tutor
    And I log in as "tutor1"
    And I follow "Course1"
    Then I should not see "Uso do sistema pelo tutor (acessos)"
    And I should not see "Uso do sistema pelo tutor (horas)"
