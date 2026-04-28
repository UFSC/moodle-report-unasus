@unasus @report_unasus @atividades_vs_notas
Feature: Relatório UNA-SUS de atribuição de notas
  Para acompanhar entregas, notas e prazos de avaliação dos estudantes
  Como coordenador ou tutor
  Preciso gerar e exportar o relatório de atribuição de notas

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

  @javascript @atividades_vs_notas
Scenario: atividades_vs_notas - estados de entrega e nota
    # Cria atividade com prazo definido para validar legenda de nota no prazo (ate 24hs).
    And the following "activities" exist:
      | activity | course | idnumber | name                  | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
      | assign   | C1     | a7       | Test assignment seven | Submit something! | 100   | 1                                   | 946684800          | 1          |

    # Distribui estados entre estudantes para cobrir variacoes sem concentrar em um unico aluno.
    # student2 entrega a1 sem nota (deadline futuro -> sem nota).
    And I log in as "student2"
    And I follow "Course1"
    And I follow "Test assignment one"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student2 submission on assignment one |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    # Entregas no prazo para a7 em estudantes diferentes (base da legenda nota no prazo).
    And I log in as "student2"
    And I follow "Course1"
    And I follow "Test assignment seven"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student2 submission on assignment seven |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    And I log in as "student3"
    And I follow "Course1"
    And I follow "Test assignment seven"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student3 submission on assignment seven |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    # Estado de correcao atrasada: forca envio de a2 para 2 dias no passado (student2 sem nota em a2).
    And I set the submission date of activity "a2" to "-2" days after

    # pouco_atraso: student1 submeteu a6 no Background; forcamos a 4 dias antes de agora (2-5 dias > prazo_avaliacao=1, <= prazo_maximo=5).
    And I set the submission date of activity "a6" to "4" days before now

    # Estados com nota: no prazo em a4 e com atraso em a5 para student1.
    And I set the grade of activity "a4" for user "student1" to "90"
    And I set the grade date of activity "a4" for user "student1" to "0" days after submission
    And I set the grade of activity "a5" for user "student1" to "70"
    And I set the grade date of activity "a5" for user "student1" to "2" days after submission

    # Notas no prazo em a7 para estudantes diferentes (<= 24h apos entrega).
    And I set the submission date of activity "a7" to "0" days after
    And I set the grade of activity "a7" for user "student2" to "85"
    And I set the grade date of activity "a7" for user "student2" to "0" days after submission
    And I set the grade of activity "a7" for user "student3" to "95"
    And I set the grade date of activity "a7" for user "student3" to "0" days after submission

    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: atribuição de notas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
#    And I take a screenshot

    # Legenda especifica solicitada: nota atribuida no prazo (ate 24hs).
    Then I should see "Nota atribuída no prazo (até 24hs)"

    # Cobre variacoes textuais: sem prazo, no prazo, nao entregue, sem nota e dias em atraso.
    And the unasus report table should have "sem prazo" at row "Student s4" and column "Test assignment three"
    And the unasus report table should have "no prazo" at row "Student s5" and column "Test assignment one"
    And the unasus report table should have "não entregue" at row "Student s6" and column "Test assignment two"
    And the unasus report table should have "sem nota" at row "Student s2" and column "Test assignment one"

    # Garante que estados/notas estao distribuidos entre alunos diferentes em colunas distintas.
    And the unasus report table should have "90.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "70.0" at row "Student s1" and column "Test assignment five"
    And the unasus report table should have "85.0" at row "Student s2" and column "Test assignment seven"
    And the unasus report table should have "95.0" at row "Student s3" and column "Test assignment seven"

    # CSS class de cada estado da legenda confirmado por celula.
    And the unasus report table cell at row "Student s4" and column "Test assignment three" should have css class "sem_prazo"
    And the unasus report table cell at row "Student s5" and column "Test assignment one" should have css class "nao_realizada"
    And the unasus report table cell at row "Student s6" and column "Test assignment two" should have css class "nao_entregue"
    # a2 correcao atrasada: student2 submeteu em Dez/2000, sem nota -> "X dias" (variavel), CSS muito_atraso.
    And the unasus report table cell at row "Student s2" and column "Test assignment two" should have css class "muito_atraso"
    And the unasus report table cell at row "Student s2" and column "Test assignment one" should have css class "avaliado_sem_nota"
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "nota_atribuida"
    And the unasus report table cell at row "Student s1" and column "Test assignment five" should have css class "nota_atribuida_atraso"
    And the unasus report table cell at row "Student s2" and column "Test assignment seven" should have css class "nota_atribuida"
    And the unasus report table cell at row "Student s3" and column "Test assignment seven" should have css class "nota_atribuida"
    # pouco_atraso: student1/a6 submetido 4 dias antes de agora, sem nota -> dentro do prazo maximo (1-5 dias).
    And the unasus report table cell at row "Student s1" and column "Test assignment six" should have css class "pouco_atraso"

  @atividades_vs_notas @csv
  Scenario: atividades_vs_notas exporta CSV com dados esperados
    # Reaplica setup deterministico para o cenario de exportacao CSV.
    And the following "activities" exist:
      | activity | course | idnumber | name                  | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
      | assign   | C1     | a7       | Test assignment seven | Submit something! | 100   | 1                                   | 946684800          | 1          |

    And I log in as "student2"
    And I follow "Course1"
    And I follow "Test assignment one"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student2 submission on assignment one |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    And I log in as "student2"
    And I follow "Course1"
    And I follow "Test assignment seven"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student2 submission on assignment seven |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    And I log in as "student3"
    And I follow "Course1"
    And I follow "Test assignment seven"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student3 submission on assignment seven |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    And I set the submission date of activity "a2" to "-2" days after
    # pouco_atraso: student1/a6 submetido no Background; forcamos 4 dias antes de agora (> prazo_avaliacao=1, <= prazo_maximo=5).
    And I set the submission date of activity "a6" to "4" days before now
    And I set the grade of activity "a4" for user "student1" to "90"
    And I set the grade date of activity "a4" for user "student1" to "0" days after submission
    And I set the grade of activity "a5" for user "student1" to "70"
    And I set the grade date of activity "a5" for user "student1" to "2" days after submission
    And I set the submission date of activity "a7" to "0" days after
    And I set the grade of activity "a7" for user "student2" to "85"
    And I set the grade date of activity "a7" for user "student2" to "0" days after submission
    And I set the grade of activity "a7" for user "student3" to "95"
    And I set the grade date of activity "a7" for user "student3" to "0" days after submission

    And I log in as "admin"
    And I export the unasus report "atividades_vs_notas" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Estudante"
    And the exported unasus csv should contain "Test assignment two"
    And the exported unasus csv should have "sem prazo" at row "Student s4" and column "Test assignment three"
    And the exported unasus csv should have "no prazo" at row "Student s5" and column "Test assignment one"
    And the exported unasus csv should have "não entregue" at row "Student s6" and column "Test assignment two"
    And the exported unasus csv should have "sem nota" at row "Student s2" and column "Test assignment one"
    # Correcao atrasada: student2/a2 submetido em Dez/2000, sem nota -> formato "X dias" (variavel).
    And the exported unasus csv should contain "dias"
    And the exported unasus csv should have "90.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "70.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "85.0" at row "Student s2" and column "Test assignment seven"
    And the exported unasus csv should have "95.0" at row "Student s3" and column "Test assignment seven"
    # pouco_atraso: student1/a6 com 4 dias sem nota -> "4 dias" no CSV.
    And the exported unasus csv should have "4 dias" at row "Student s1" and column "Test assignment six"

  @javascript @tutor_scope @atividades_vs_notas
  Scenario: tutor teacher1 vê apenas estudantes da própria tutoria no relatório de atribuição de notas
    And I log in as "teacher1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: atribuição de notas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "Student s1"
    And I should see "Student s3"
    And I should not see "Student s5"
    And I should not see "Student s12"
    And I log out
