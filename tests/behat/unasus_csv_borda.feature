 @unasus @report_unasus
Feature: Relatórios UNA-SUS: exportação CSV com caracteres especiais
  Verificar que nomes de estudantes e atividades contendo vírgulas são
  corretamente escapados no CSV (RFC 4180 — campo entre aspas duplas).

Background:
  Given the following "users" exist:
    | username | firstname | lastname       | email                |
    | teacher1 | Teacher   | t1             | teacher1@example.com |
    | student1 | Silva     | da Silva, Jr.  | student1@example.com |

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

  # Atividade cujo nome contém vírgula — deve aparecer como campo único no CSV
  And the following "activities" exist:
    | activity | course | idnumber | name                    | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
    | assign   | C1     | a1       | Atividade, com vírgula  | Submit something! | 100   | 1                                   | 978307200          | 1          |

  And the following "course enrolments" exist:
    | user     | course | role           |
    | teacher1 | c1     | editingteacher |
    | student1 | c1     | student        |

  And the following "permission overrides" exist:
    | capability                    | permission | role           | contextlevel | reference |
    | local/relationship:view       | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage     | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign     | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view            | Allow      | editingteacher | Category     | CAT1      |
    | report/unasus:view_all        | Allow      | editingteacher | Course       | c1        |

  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | editingteacher | Category     | CAT1      |

  And the following users are added to cohorts:
    | user     | cohort  |
    | teacher1 | teacher |
    | student1 | student |

  And a basic unasus tutoria environment exists:

  And the following tutoria memberships exist:
    | user     | group               |
    | teacher1 | relationship_group1 |
    | student1 | relationship_group1 |

  @csv @csv_borda
  Scenario: csv_borda - nome de estudante com virgula e exportado como campo entre aspas
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    # O parser str_getcsv deve reconhecer "Silva da Silva, Jr." como um campo único
    Then the exported unasus csv should contain "Silva da Silva, Jr."
    # O step de lookup de linha deve encontrar o estudante pelo nome com vírgula
    And the exported unasus csv should have an empty value at row "Silva da Silva, Jr." and column "Atividade, com vírgula"

  @csv @csv_borda
  Scenario: csv_borda - nome de atividade com virgula no cabecalho do CSV
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    # O cabeçalho deve conter o nome da atividade como campo único (com aspas no CSV bruto)
    Then the exported unasus csv should contain "Atividade, com vírgula"
    # O lookup por coluna deve encontrar o header com vírgula via str_getcsv
    And the exported unasus csv should have an empty value at row "Silva da Silva, Jr." and column "Atividade, com vírgula"
