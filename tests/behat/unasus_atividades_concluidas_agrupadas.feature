@unasus @report_unasus @javascript @atividades_concluidas_agrupadas
Feature: Relatório UNA-SUS de atividades concluídas agrupadas com validação de dados
  Para validar dados de linhas e colunas do relatório de síntese agrupada
  Como usuário com capability de visualização
  Preciso ver valores determinísticos por grupo e total

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
    | local_tutores_student_roles    | student        |
    | local_tutores_tutor_roles      | editingteacher |
    | local_tutores_orientador_roles | editingteacher |

  And the following "activities" exist:
    | activity | course | idnumber | name            | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
    | assign   | C1     | a_single | Single activity | Deterministic data | 100   | 1                                   | 1772323200         | 1          |

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

  # Deterministic completion: only student1 concludes the only activity.
  And I mark activity "a_single" as complete for user "student1"

  @javascript @atividades_concluidas_agrupadas
  Scenario: síntese agrupada exibe valores por grupo e total
    And I log in as "manager1"
    And I follow "Course1"
    And I navigate to "Sintese: atividades concluidas agrupadas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "N° Alunos com atividades concluídas"
    And I should see "Total alunos com atividade concluida / Total alunos"
    And I should see "1/1"
    And I should see "0/1"
    And I should see "1/2"

  @javascript @atividades_concluidas_agrupadas
  Scenario: tutor sem view_all não visualiza dados de outro grupo na síntese agrupada
    And I log in as "tutor1"
    And I follow "Course1"
    And I navigate to "Sintese: atividades concluidas agrupadas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "Tutor One"
    And I should not see "Tutor Two"
