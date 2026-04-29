@unasus @report_unasus @atividades_vs_notas
Feature: Relatório UNA-SUS de atribuição de notas
  Para acompanhar entregas, notas e prazos de avaliação dos estudantes
  Como coordenador ou tutor
  Preciso gerar e exportar o relatório de atribuição de notas

Background:
  Given a standard report_unasus tutoria fixture exists

  And I submit assignment "a2" for user "student2"
  And I submit assignment "a3" for user "student3"
  And I submit assignment "a4" for user "student1"
  And I set the submission date of activity "a4" to "0" days after
  And I submit assignment "a5" for user "student1"
  And I set the submission date of activity "a5" to "10" days after
  And I submit assignment "a6" for user "student1"
  And I set the submission date of activity "a6" to "11" days after
  And I post to forum "f1" as user "student1"
  And I post to forum "f2" as user "student2"

  @javascript @atividades_vs_notas
Scenario: atividades_vs_notas - estados de entrega e nota
    # Cria atividade com prazo definido para validar legenda de nota no prazo (ate 24hs).
    And the following "activities" exist:
      | activity | course | idnumber | name                  | intro             | grade | assignsubmission_onlinetext_enabled | completionexpected | completion |
      | assign   | C1     | a7       | Test assignment seven | Submit something! | 100   | 1                                   | 946684800          | 1          |

    # Distribui estados entre estudantes para cobrir variacoes sem concentrar em um unico aluno.
    # student2 entrega a1 sem nota (deadline futuro -> sem nota).
    And I submit assignment "a1" for user "student2"

    # Entregas no prazo para a7 em estudantes diferentes (base da legenda nota no prazo).
    And I submit assignment "a7" for user "student2"
    And I submit assignment "a7" for user "student3"

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

    And I submit assignment "a1" for user "student2"
    And I submit assignment "a7" for user "student2"
    And I submit assignment "a7" for user "student3"

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
