 @unasus @report_unasus
Feature: Relatório UNA-SUS: atividades nota atribuída
  Para acompanhar a conclusão de atividades no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso visualizar a síntese de atividades concluídas por tutor

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

  And the following config values are set as admin:
    | enablecompletion               | 1              |
    | grade_aggregateonlygraded      | 1              |
    | local_tutores_student_roles    | student        |
    | local_tutores_tutor_roles      | editingteacher |
    | local_tutores_orientador_roles | editingteacher |
    | report_unasus_prazo_maximo_entrega   | 10 |
    | report_unasus_prazo_maximo_avaliacao | 5  |
    | report_unasus_prazo_avaliacao        | 1  |
    | report_unasus_tolerancia_potencial_evasao | 1 |
    | report_unasus_passing_grade_percentage   | 60 |

  And the following "cohorts" exist:
    | name             | idnumber | contextlevel | reference |
    | Cohort teacher   | CHt      | Category     | CAT1      |
    | Cohort student   | CHs      | Category     | CAT1      |

# -----------------------------ASSIGN SETUP-----------------------------------------------
# Unix timestamp 946684800 = 1 jan 2000 at 00h00m00s
  And the following "activities" exist:
    | activity | course | idnumber | name                  | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
    | assign   | C1     | a1       | Test assignment one   | Submit something! | 100   | 1                                   | 2147483647         | 1          |
    | assign   | C1     | a2       | Test assignment two   | Submit something! | 100   | 1                                   | 978307200          | 1          |
    | assign   | C1     | a3       | Test assignment three | Submit something! | 100   | 1                                   | 0                  | 1          |
    | assign   | C1     | a4       | Test assignment four  | Submit something! | 100   | 1                                   | 946684800          | 1          |
    | assign   | C1     | a5       | Test assignment five  | Submit something! | 100   | 1                                   | 946684800          | 1          |
    | assign   | C1     | a6       | Test assignment six   | Submit something! | 100   | 1                                   | 946684800          | 1          |
#    | assign   | C1     | a7       | Test assignment seven | Submit something! | 100   | 1                                   | 946684800          | 1          |

# -----------------------------FORUM SETUP-----------------------------------------------
  And the following "activities" exist:
    | activity | course | idnumber | name            | intro        | scale | completion | completionexpected |
    | forum    | C1     | f1       | Test forum one  | Forum intro  | 100   | 1          | 2147483647         |
    | forum    | C1     | f2       | Test forum two  | Forum intro  | 100   | 1          | 978307200          |

# -----------------------------QUIZ SETUP-----------------------------------------------
  And the following "activities" exist:
    | activity | course | idnumber | name           | intro      | grade | completion | completionexpected |
    | quiz     | C1     | q1       | Test quiz one  | Quiz intro | 100   | 1          | 978307200          |

# -----------------------------DATABASE SETUP-----------------------------------------------
  And the following "activities" exist:
    | activity | course | idnumber | name              | intro    | scale | completion | completionexpected |
    | data     | C1     | d1       | Test database one | DB intro | 100   | 1          | 978307200          |

# -----------------------------LTI SETUP-----------------------------------------------
  And the following "activities" exist:
    | activity | course | idnumber | name          | intro     | completion | completionexpected |
    | lti      | C1     | l1       | Test lti one  | LTI intro | 1          | 978307200          |

# --------------------------------------------------------------------------------------

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

  # Por causa da exigência do relatório, é necessário que a coluna `role` tenha como valor editingteacher
  And the following "permission overrides" exist:
    | capability                | permission | role           | contextlevel | reference |
    | local/relationship:view   | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view        | Allow      | editingteacher | Category     | CAT1      |
    | report/unasus:view_tutoria | Allow      | editingteacher | Course       | c1        |

  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | editingteacher | Category     | CAT1      |
    | teacher2 | editingteacher | Category     | CAT1      |
    | teacher3 | editingteacher | Category     | CAT1      |

  # O cohort precisa ser student ou teacher para que seja incluído nos grupos de tutoria
  # É necessário usar a regra abaixo para a inclusão dos membros, visto a integração do plugin relationship
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

  And I log in as "student2"
  And I follow "Course1"
  And I follow "Test assignment two"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student2 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I log out

  And I log in as "student3"
  And I follow "Course1"
  And I follow "Test assignment three"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student3 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I log out

  And I log in as "student1"
  And I follow "Course1"
  And I follow "Test assignment four"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student1 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I set the submission date of activity "a4" to "0" days after
  And I log out

  And I log in as "student1"
  And I follow "Course1"
  And I follow "Test assignment five"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student3 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I set the submission date of activity "a5" to "10" days after
  And I log out

  And I log in as "student1"
  And I follow "Course1"
  And I follow "Test assignment six"
  And I press "Add submission"
  And I set the following fields to these values:
    | Online text | I'm the student3 submission |
  And I press "Save changes"
  And I press "Submit assignment"
  And I press "Continue"
  And I set the submission date of activity "a6" to "11" days after
  And I log out

#  And I log in as "student3"
#  And I follow "Course1"
#  And I follow "Test assignment seven"
#  And I press "Add submission"
#  And I set the following fields to these values:
#    | Online text | I'm the student3 submission |
#  And I press "Save changes"
#  And I press "Submit assignment"
#  And I press "Continue"
#  And I set the submission date of activity "a7" to "11" days after
#  And I log out

  # Postagem no fórum (student1 no forum com prazo futuro, student2 no forum com prazo passado)
  And I log in as "student1"
  And I follow "Course1"
  And I add a new discussion to "Test forum one" forum with:
    | Subject | Forum discussion s1 |
    | Message | I'm the student1 forum post |
  And I log out

  And I log in as "student2"
  And I follow "Course1"
  And I add a new discussion to "Test forum two" forum with:
    | Subject | Forum discussion s2 |
    | Message | I'm the student2 forum post |
  And I log out

  @javascript @atividades_nota_atribuida
  Scenario: atividades_nota_atribuida - sintese de completude por tutor
    # Teacher t1: dois estudantes concluiram todas as atividades com completude ativa.
    And I mark all completion-enabled activities in course "c1" as complete for user "student1"
    And I mark all completion-enabled activities in course "c1" as complete for user "student2"

    # Teacher t2: um estudante concluiu todas; outro concluiu apenas uma atividade.
    And I mark all completion-enabled activities in course "c1" as complete for user "student5"
    And I mark activity "a1" as complete for user "student6"

    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Síntese: atividades concluídas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"

    Then the unasus report table should have "2/4 50.0%" at row "Teacher t1" and column "Test assignment one"
    And the unasus report table should have "2/4 50.0%" at row "Teacher t1" and column "Test assignment two"
    And the unasus report table should have "2/4 50.0%" at row "Teacher t1" and column "Test forum one"
    And the unasus report table should have "2/4 50.0%" at row "Teacher t1" and column "Test database one"
    And the unasus report table should have "2/4 50.0%" at row "Teacher t1" and column "Test lti one"
    And the unasus report table should have "2/4 50.0%" at row "Teacher t1" and column "Total"

    And the unasus report table should have "2/4 50.0%" at row "Teacher t2" and column "Test assignment one"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t2" and column "Test assignment two"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t2" and column "Test database one"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t2" and column "Test lti one"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t2" and column "Total"

    And the unasus report table should have "0/4 0.0%" at row "Teacher t3" and column "Test assignment one"
    And the unasus report table should have "0/4 0.0%" at row "Teacher t3" and column "Test database one"
    And the unasus report table should have "0/4 0.0%" at row "Teacher t3" and column "Test lti one"
    And the unasus report table should have "0/4 0.0%" at row "Teacher t3" and column "Total"

    And the unasus report table should have "4/12 33.3%" at row "Total alunos com atividade concluida / Total alunos" and column "Test assignment one"
    And the unasus report table should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test assignment two"
    And the unasus report table should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test database one"
    And the unasus report table should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test lti one"
    And the unasus report table should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Total"

  @atividades_nota_atribuida @csv
  Scenario: atividades_nota_atribuida exporta CSV com dados esperados
    # Teacher t1: dois estudantes concluiram todas as atividades com completude ativa.
    And I mark all completion-enabled activities in course "c1" as complete for user "student1"
    And I mark all completion-enabled activities in course "c1" as complete for user "student2"

    # Teacher t2: um estudante concluiu todas; outro concluiu apenas uma atividade.
    And I mark all completion-enabled activities in course "c1" as complete for user "student5"
    And I mark activity "a1" as complete for user "student6"

    And I log in as "admin"
    And I export the unasus report "atividades_nota_atribuida" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Tutores"
    And the exported unasus csv should contain "N° Alunos com atividades concluídas"
    And the exported unasus csv should have "2/4 50.0%" at row "Teacher t1" and column "Test assignment one"
    And the exported unasus csv should have "2/4 50.0%" at row "Teacher t1" and column "Test assignment two"
    And the exported unasus csv should have "2/4 50.0%" at row "Teacher t1" and column "Test forum one"
    And the exported unasus csv should have "2/4 50.0%" at row "Teacher t1" and column "Test database one"
    And the exported unasus csv should have "2/4 50.0%" at row "Teacher t1" and column "Test lti one"
    And the exported unasus csv should have "2/4 50.0%" at row "Teacher t1" and column "Total"

    And the exported unasus csv should have "2/4 50.0%" at row "Teacher t2" and column "Test assignment one"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t2" and column "Test assignment two"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t2" and column "Test database one"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t2" and column "Test lti one"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t2" and column "Total"

    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t3" and column "Test assignment one"
    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t3" and column "Test database one"
    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t3" and column "Test lti one"
    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t3" and column "Total"

    And the exported unasus csv should have "4/12 33.3%" at row "Total alunos com atividade concluida / Total alunos" and column "Test assignment one"
    And the exported unasus csv should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test assignment two"
    And the exported unasus csv should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test database one"
    And the exported unasus csv should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test lti one"
    And the exported unasus csv should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Total"
