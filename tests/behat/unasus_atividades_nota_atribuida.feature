 @unasus @report_unasus
Feature: Relatório UNA-SUS: atividades nota atribuída
  Para acompanhar a conclusão de atividades no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso visualizar a síntese de atividades concluídas por tutor

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
    And the unasus report table should have "1/4 25.0%" at row "Teacher t1" and column "Test lti one"
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
    And the unasus report table should have "2/12 16.7%" at row "Total alunos com atividade concluida / Total alunos" and column "Test lti one"
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
    And the exported unasus csv should have "1/4 25.0%" at row "Teacher t1" and column "Test lti one"
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
    And the exported unasus csv should have "2/12 16.7%" at row "Total alunos com atividade concluida / Total alunos" and column "Test lti one"
    And the exported unasus csv should have "3/12 25.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Total"
