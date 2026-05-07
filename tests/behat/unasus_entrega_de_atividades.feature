 @unasus @report_unasus
Feature: Relatório UNA-SUS: entrega de atividades
  Para acompanhar as entregas de atividades no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso visualizar as legendas de entrega e o escopo de tutoria

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

  @javascript @entrega_de_atividades
Scenario: entrega_de_atividades - todas as legendas de entrega
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: entrega de atividades" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # a3 deadline=0: estudantes que nao entregaram aparecem como "sem prazo" na coluna.
    Then the unasus report table should have "sem prazo" at row "Student s1" and column "Test assignment three"
    And the unasus report table should have "sem prazo" at row "Student s2" and column "Test assignment three"
    # a5 entregue com 10 dias de atraso -> pouco atraso (dentro de prazo_maximo=10).
    And the unasus report table should have "10 dias" at row "Student s1" and column "Test assignment five"
    # a6 entregue com 11 dias de atraso -> muito atraso (acima de prazo_maximo=10).
    And the unasus report table should have "11 dias" at row "Student s1" and column "Test assignment six"
    # Estados sem texto visivel verificados por CSS class:
    # a1 prazo futuro, student1 nao entregou -> nao_entregue_mas_no_prazo.
    And the unasus report table cell at row "Student s1" and column "Test assignment one" should have css class "nao_entregue_mas_no_prazo"
    # a2 prazo passado, student1 nao entregou -> nao_entregue_fora_do_prazo.
    And the unasus report table cell at row "Student s1" and column "Test assignment two" should have css class "nao_entregue_fora_do_prazo"
    # a4 entregue no prazo por student1 (0 dias de atraso) -> no_prazo.
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "no_prazo"
    # a3 entregue por student3 (sem deadline) -> no_prazo.
    And the unasus report table cell at row "Student s3" and column "Test assignment three" should have css class "no_prazo"
    # a5 pouco atraso (<=10 dias) e a6 muito atraso (>10 dias) confirmados por CSS.
    And the unasus report table cell at row "Student s1" and column "Test assignment five" should have css class "pouco_atraso"
    And the unasus report table cell at row "Student s1" and column "Test assignment six" should have css class "muito_atraso"
