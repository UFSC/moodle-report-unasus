 @unasus @report_unasus
Feature: Relatório UNA-SUS: avaliações em atraso
  Para acompanhar avaliações pendentes no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso visualizar a síntese de avaliações em atraso por tutor

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

  @javascript @avaliacoes_em_atraso
Scenario: avaliacoes_em_atraso - sintese de avaliacoes pendentes
    And I set the grade of activity "a4" for user "student1" to "90"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Síntese: avaliações em atraso" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"

    # Teacher t1 tem atividades enviadas por seus estudantes e ainda sem avaliacao.
    Then the unasus report table should have "0/4 0.0%" at row "Teacher t1" and column "Test assignment one"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment two"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment three"
    And the unasus report table should have "0/4 0.0%" at row "Teacher t1" and column "Test assignment four"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment five"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment six"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t1" and column "Test forum one"
    And the unasus report table should have "1/4 25.0%" at row "Teacher t1" and column "Test forum two"
    And the unasus report table should have "6/44 13.6%" at row "Teacher t1" and column "Média"

    # Limites do agregado: sem envio, ja avaliada, tutor sem pendencias e media zerada.
    And the unasus report table should have "0/4 0.0%" at row "Teacher t2" and column "Test assignment two"
    And the unasus report table should have "0/44 0.0%" at row "Teacher t2" and column "Média"
    And the unasus report table should have "0/4 0.0%" at row "Teacher t3" and column "Test assignment six"
    And the unasus report table should have "0/44 0.0%" at row "Teacher t3" and column "Média"

  @avaliacoes_em_atraso @csv
  Scenario: avaliacoes_em_atraso exporta CSV com dados esperados
    And I set the grade of activity "a4" for user "student1" to "90"
    And I log in as "admin"
    And I export the unasus report "avaliacoes_em_atraso" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Tutores"
    And the exported unasus csv should contain "Média"
    And the exported unasus csv should contain "Test assignment one"

    # Teacher t1 tem atividades enviadas por seus estudantes e ainda sem avaliacao.
    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t1" and column "Test assignment one"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment two"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment three"
    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t1" and column "Test assignment four"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment five"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t1" and column "Test assignment six"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t1" and column "Test forum one"
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t1" and column "Test forum two"
    And the exported unasus csv should have "6/44 13.6%" at row "Teacher t1" and column "Média"

    # Limites do agregado: sem envio, ja avaliada, tutor sem pendencias e media zerada.
    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t2" and column "Test assignment two"
    And the exported unasus csv should have "0/44 0.0%" at row "Teacher t2" and column "Média"
    And the exported unasus csv should have "0/4 0.0%" at row "Teacher t3" and column "Test assignment six"
    And the exported unasus csv should have "0/44 0.0%" at row "Teacher t3" and column "Média"
