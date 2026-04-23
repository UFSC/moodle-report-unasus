 @unasus @report_unasus
Feature: Relatórios UNA-SUS: geração e visualização
  Para acompanhar o progresso dos estudantes no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso gerar e visualizar os relatórios de entrega e avaliação de atividades

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

  @javascript @entrega_de_atividades
Scenario: Correct report generation
    And I log in as "admin"

    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: entrega de atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "Test assignment one"

  @javascript @entrega_de_atividades
Scenario: entrega_de_atividades - todas as legendas de entrega
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: entrega de atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # a3 tem deadline=0 -> sem prazo
    Then I should see "sem prazo"
    # a5 foi entregue com 10 dias de atraso -> pouco atraso (dentro de prazo_maximo=10)
    And I should see "10 dias"
    # a6 foi entregue com 11 dias de atraso -> muito atraso (acima de prazo_maximo=10)
    And I should see "11 dias"
    # a2 sem envio de student1 com prazo passado -> nao entregue fora do prazo
    # a1 prazo futuro sem envio de student3 -> nao entregue mas no prazo
    # Verifica que há atividades com prazo futuro no relatório
    And I should see "Test assignment one"
    And I should see "Test assignment two"

  @javascript @scope @estudante_sem_atividade_postada
Scenario: estudante_sem_atividade_postada - lista de atividades nao postadas
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: atividades não postadas e sem nota" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # student1 nao entregou a2 (deadline passado 978307200) e nao tem nota
    Then I should see "Student s1"
    # a2 deve aparecer na lista para quem nao entregou
    And I should see "Test assignment two"

  @javascript @estudante_sem_atividade_avaliada
Scenario: estudante_sem_atividade_avaliada - cobertura com limites e variacoes de borda
    # student2: duas entregas, uma corrigida e outra sem nota (deve aparecer).
    And I log in as "student2"
    And I follow "Course1"
    And I follow "Test assignment four"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student2 submission on assignment four |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    # student4: duas entregas, uma corrigida e outra sem nota (deve aparecer).
    And I log in as "student4"
    And I follow "Course1"
    And I follow "Test assignment one"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student4 submission on assignment one |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    And I log in as "student4"
    And I follow "Course1"
    And I follow "Test assignment two"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student4 submission on assignment two |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    # student5: uma entrega corrigida (nao deve aparecer).
    And I log in as "student5"
    And I follow "Course1"
    And I follow "Test assignment five"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student5 submission on assignment five |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    # student6: uma entrega sem nota (deve aparecer).
    And I log in as "student6"
    And I follow "Course1"
    And I follow "Test assignment six"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student6 submission on assignment six |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    # student8: duas entregas, ambas corrigidas (nao deve aparecer).
    And I log in as "student8"
    And I follow "Course1"
    And I follow "Test assignment four"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student8 submission on assignment four |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    And I log in as "student8"
    And I follow "Course1"
    And I follow "Test assignment five"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student8 submission on assignment five |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    # Ajusta notas para compor os cenarios de borda.
    # student1 entregou a4, a5 e a6 no background; todas corrigidas (nao deve aparecer).
    And I set the grade of activity "a4" for user "student1" to "90"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I set the grade of activity "a6" for user "student1" to "70"

    # student2: corrige a4, mantendo a2 sem nota.
    And I set the grade of activity "a4" for user "student2" to "85"

    # student4: corrige a1, mantendo a2 sem nota.
    And I set the grade of activity "a1" for user "student4" to "88"

    # student5: entrega corrigida.
    And I set the grade of activity "a5" for user "student5" to "92"

    # student8: todas as entregas corrigidas.
    And I set the grade of activity "a4" for user "student8" to "77"
    And I set the grade of activity "a5" for user "student8" to "79"

    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: atividades não avaliadas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"

    And I take a screenshot


    # Deve listar estudantes com pelo menos uma entrega/postagem sem nota.
    Then the unasus report should have row "Student s1"
    And the unasus report should have row "Student s2"
    And the unasus report should have row "Student s3"
    And the unasus report should have row "Student s4"
    And the unasus report should have row "Student s5"
    And the unasus report should have row "Student s6"
    And the unasus report should have row "Student s8"

    # Nao deve listar quem nao tem entrega pendente de avaliacao.
    And the unasus report should not have row "Student s7"

    # Verifica por linha se cada estudante possui exatamente as atividades pendentes esperadas.
    And the unasus report row "Student s1" should contain activity "Test forum one"
    And the unasus report row "Student s1" should not contain activity "Test assignment one"
    And the unasus report row "Student s1" should not contain activity "Test assignment two"
    And the unasus report row "Student s1" should not contain activity "Test assignment three"
    And the unasus report row "Student s1" should not contain activity "Test assignment six"

    And the unasus report row "Student s2" should contain activity "Test assignment two"
    And the unasus report row "Student s2" should contain activity "Test forum two"
    And the unasus report row "Student s2" should not contain activity "Test assignment one"
    And the unasus report row "Student s2" should not contain activity "Test assignment three"
    And the unasus report row "Student s2" should not contain activity "Test assignment six"

    And the unasus report row "Student s3" should contain activity "Test assignment three"
    And the unasus report row "Student s3" should not contain activity "Test assignment two"
    And the unasus report row "Student s3" should not contain activity "Test assignment one"
    And the unasus report row "Student s3" should not contain activity "Test assignment six"

    And the unasus report row "Student s4" should contain activity "Test assignment two"
    And the unasus report row "Student s4" should not contain activity "Test assignment one"
    And the unasus report row "Student s4" should not contain activity "Test assignment three"
    And the unasus report row "Student s4" should not contain activity "Test assignment six"

    And the unasus report row "Student s5" should contain activity "Test lti one"
    And the unasus report row "Student s5" should not contain activity "Test assignment five"
    And the unasus report row "Student s5" should not contain activity "Test assignment two"
    And the unasus report row "Student s5" should not contain activity "Test assignment six"

    And the unasus report row "Student s6" should contain activity "Test assignment six"
    And the unasus report row "Student s6" should not contain activity "Test assignment one"
    And the unasus report row "Student s6" should not contain activity "Test assignment two"
    And the unasus report row "Student s6" should not contain activity "Test assignment three"

    And the unasus report row "Student s8" should contain activity "Test lti one"
    And the unasus report row "Student s8" should not contain activity "Test assignment four"
    And the unasus report row "Student s8" should not contain activity "Test assignment five"
    And the unasus report row "Student s8" should not contain activity "Test assignment two"

  @javascript @avaliacoes_em_atraso
Scenario: avaliacoes_em_atraso - sintese de avaliacoes pendentes
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Síntese: avaliações em atraso" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Deve exibir tabela com tutores e atividades com avaliacoes pendentes
    Then I should see "Test assignment one"

  @avaliacoes_em_atraso @csv
  Scenario: avaliacoes_em_atraso exporta CSV com dados esperados
    And I log in as "admin"
    And I export the unasus report "avaliacoes_em_atraso" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Tutores"
    And the exported unasus csv should contain "Média"
    And the exported unasus csv should contain "Test assignment one"
    And the exported unasus csv should have a row containing:
      | value      |
      | Teacher t1 |

  @atividades_nota_atribuida @csv
  Scenario: atividades_nota_atribuida exporta CSV com dados esperados
    And I log in as "admin"
    And I export the unasus report "atividades_nota_atribuida" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Tutores"
    And the exported unasus csv should contain "N° Alunos com atividades concluídas"
    And the exported unasus csv should have a row containing:
      | value      |
      | Teacher t1 |

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
    # And I take a screenshot

    # Legenda especifica solicitada: nota atribuida no prazo (ate 24hs).
    Then I should see "Nota atribuída no prazo (até 24hs)"

    # Cobre variacoes textuais: sem prazo, no prazo, nao entregue, sem nota e dias em atraso.
    And the unasus report table should have "sem prazo" at row "Student s4" and column "Test assignment three"
    And the unasus report table should have "no prazo" at row "Student s5" and column "Test assignment one"
    And the unasus report table should have "não entregue" at row "Student s6" and column "Test assignment two"
    And the unasus report table should have "sem nota" at row "Student s2" and column "Test assignment one"
    And I should see "dias"

    # Garante que estados/notas estao distribuidos entre alunos diferentes em colunas distintas.
    And the unasus report table should have "90.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "70.0" at row "Student s1" and column "Test assignment five"
    And the unasus report table should have "85.0" at row "Student s2" and column "Test assignment seven"
    And the unasus report table should have "95.0" at row "Student s3" and column "Test assignment seven"

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
    And the exported unasus csv should contain "dias"
    And the exported unasus csv should have "90.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "70.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "85.0" at row "Student s2" and column "Test assignment seven"
    And the exported unasus csv should have "95.0" at row "Student s3" and column "Test assignment seven"

  @javascript @boletim
Scenario: boletim - verificacao de notas
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # student1 tem notas 100 (a4) e 80 (a5) -> media final deve ser 90.0
    Then I should see "Test assignment four"
    And the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage
    And I should see "Atividade avaliada com nota acima de"
    And I should see "Atividade avaliada com nota abaixo de"
    And I should see "Média final abaixo de"
    And I should see "Atividade não avaliada ou não entregue"
    And I should see "Atividade não aplicada"

  @boletim @csv
  Scenario: boletim exporta CSV com dados esperados
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Estudante"
    And the exported unasus csv should contain "Média Final"
    And the exported unasus csv should contain "Test assignment four"
    And the exported unasus csv should have "100.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "90.0" at row "Student s1" and column "Média Final"
    And the exported unasus csv should have an empty value at row "Student s1" and column "Test assignment one"

  @javascript @boletim
Scenario: boletim - média ponderada no gradebook
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set gradebook weight "75" for activity "a4" in course "c1"
    And I set gradebook weight "25" for activity "a5" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "95.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - média ponderada no gradebook exporta CSV
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set gradebook weight "75" for activity "a4" in course "c1"
    And I set gradebook weight "25" for activity "a5" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "95.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Média Final"
    And the exported unasus csv should have "100.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "95.0" at row "Student s1" and column "Média Final"

  @javascript @boletim
Scenario: boletim - média simples com vazias=zero
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "90.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "Test assignment four"
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - média simples com vazias=zero exporta CSV
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "90.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @javascript @boletim
Scenario: boletim - média ponderada com vazias=zero
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I reset all gradebook weights for course "c1"
    And I set gradebook weight "60" for activity "a4" in course "c1"
    And I set gradebook weight "20" for activity "a5" in course "c1"
    And I set gradebook weight "20" for activity "a1" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "95.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - média ponderada com vazias=zero exporta CSV
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I reset all gradebook weights for course "c1"
    And I set gradebook weight "60" for activity "a4" in course "c1"
    And I set gradebook weight "20" for activity "a5" in course "c1"
    And I set gradebook weight "20" for activity "a1" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "95.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @javascript @boletim
Scenario: boletim - atividades base 100 com nota final base 10
    # Atividades têm grademax=100; course total configurado para base 10 (grademax=10).
    # finalgrade armazenado em escala 0-10 → boletim exibe valor bruto: 7.0 (não 70.0).
    # student1: a4=80/100 (80%), a5=60/100 (60%) → média=70% → finalgrade=7.0
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "10"
    And I set the grade of activity "a4" for user "student1" to "80"
    And I set the grade of activity "a5" for user "student1" to "60"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "70.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then the unasus report table should have "7.0" at row "Student s1" and column "M.Final"

  @boletim @csv
  Scenario: boletim - atividades base 100 com nota final base 10 exporta CSV
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "10"
    And I set the grade of activity "a4" for user "student1" to "80"
    And I set the grade of activity "a5" for user "student1" to "60"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "70.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should have "7.0" at row "Student s1" and column "Média Final"

  @javascript @boletim
Scenario: boletim - todas as atividades avaliadas verifica media final
    # student1: a1=60, a2=70, a3=80, a4=90, a5=100, a6=80
    # media simples (6 notas) = 480/6 = 80.0%
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a1" for user "student1" to "60"
    And I set the grade of activity "a2" for user "student1" to "70"
    And I set the grade of activity "a3" for user "student1" to "80"
    And I set the grade of activity "a4" for user "student1" to "90"
    And I set the grade of activity "a5" for user "student1" to "100"
    And I set the grade of activity "a6" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "80.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # And I take a screenshot
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - todas as atividades avaliadas verifica media final exporta CSV
    # student1: a1=60, a2=70, a3=80, a4=90, a5=100, a6=80
    # media simples (6 notas) = 480/6 = 80.0%
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a1" for user "student1" to "60"
    And I set the grade of activity "a2" for user "student1" to "70"
    And I set the grade of activity "a3" for user "student1" to "80"
    And I set the grade of activity "a4" for user "student1" to "90"
    And I set the grade of activity "a5" for user "student1" to "100"
    And I set the grade of activity "a6" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "80.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should have "60.0" at row "Student s1" and column "Test assignment one"
    And the exported unasus csv should have "70.0" at row "Student s1" and column "Test assignment two"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment three"
    And the exported unasus csv should have "90.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "100.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment six"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Média Final"

  @javascript @modulos_concluidos
Scenario: modulos_concluidos - verificacao de completion de atividades
    And I mark activity "a4" as complete for user "student1"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: Conclusão" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Deve exibir atividades com estado de conclusao
    Then I should see "Test assignment four"

  @javascript @atividades_concluidas_agrupadas
Scenario: atividades_concluidas_agrupadas - geracao do relatorio por modulo
    And I mark activity "a4" as complete for user "student1"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Sintese: atividades concluidas agrupadas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "N° Alunos com atividades concluídas"
    And I should see "Course1"
    And I should see "Total alunos com atividade concluida / Total alunos"

  @javascript @tutor_scope @entrega_de_atividades @atividades_vs_notas
  Scenario Outline: tutor teacher1 vê apenas estudantes da própria tutoria nos relatórios de acompanhamento
    And I log in as "teacher1"
    And I follow "Course1"
    And I navigate to "<reportnode>" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then I should see "Student s1"
    And I should see "Student s3"
    And I should not see "Student s5"
    And I should not see "Student s12"
    And I log out

    Examples:
      | reportnode                              |
      | Acompanhamento: entrega de atividades   |
      | Acompanhamento: atribuição de notas     |
