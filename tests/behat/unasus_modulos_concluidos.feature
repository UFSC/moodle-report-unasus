 @unasus @report_unasus
Feature: Relatório UNA-SUS: módulos concluídos
  Para acompanhar conclusão de atividades no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso visualizar a lista de módulos pendentes por estudante

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

  @javascript @modulos_concluidos
Scenario: modulos_concluidos - verificacao de completion de atividades
    # student10: todas as atividades concluidas, com notas, deve mostrar a media final do curso.
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a1" for user "student10" to "80"
    And I set the grade of activity "a2" for user "student10" to "80"
    And I set the grade of activity "a3" for user "student10" to "80"
    And I set the grade of activity "a4" for user "student10" to "80"
    And I set the grade of activity "a5" for user "student10" to "80"
    And I set the grade of activity "a6" for user "student10" to "80"
    And I mark activity "a1" as complete for user "student10"
    And I mark activity "a2" as complete for user "student10"
    And I mark activity "a3" as complete for user "student10"
    And I mark activity "a4" as complete for user "student10"
    And I mark activity "a5" as complete for user "student10"
    And I mark activity "a6" as complete for user "student10"
    And I mark activity "f1" as complete for user "student10"
    And I mark activity "f2" as complete for user "student10"
    And I mark activity "q1" as complete for user "student10"
    And I mark activity "d1" as complete for user "student10"
    And I mark activity "l1" as complete for user "student10"
    # student11: uma atividade concluida; todas as demais devem aparecer como pendentes.
    And I mark activity "a4" as complete for user "student11"
    # student12: somente a6 pendente; as demais nao devem aparecer na lista de pendencias.
    And I mark activity "a1" as complete for user "student12"
    And I mark activity "a2" as complete for user "student12"
    And I mark activity "a3" as complete for user "student12"
    And I mark activity "a4" as complete for user "student12"
    And I mark activity "a5" as complete for user "student12"
    And I mark activity "f1" as complete for user "student12"
    And I mark activity "f2" as complete for user "student12"
    And I mark activity "q1" as complete for user "student12"
    And I mark activity "d1" as complete for user "student12"
    And I mark activity "l1" as complete for user "student12"
    And I recalculate gradebook final grades for course "c1"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: Conclusão" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # Linha student10: curso concluido, deve mostrar a media final e nenhuma atividade pendente.
    Then the unasus module completion final grade for user "student10" in course "c1" should match Moodle gradebook
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test assignment one"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test assignment two"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test assignment three"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test assignment four"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test assignment five"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test assignment six"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test forum one"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test forum two"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test quiz one"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test database one"
    And the unasus report table cell at row "Student s10" and column "Course1" should not contain "Test lti one"
    # Linha student11: apenas a4 concluida, todas as outras devem aparecer como pendentes.
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test assignment one"
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test assignment two"
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test assignment three"
    And the unasus report table cell at row "Student s11" and column "Course1" should not contain "Test assignment four"
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test assignment five"
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test assignment six"
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test forum one"
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test forum two"
    And the unasus report table cell at row "Student s11" and column "Course1" should contain "Test quiz one"
    # Linha student12: somente a6 pendente, nenhuma outra atividade deve aparecer.
    And the unasus report table cell at row "Student s12" and column "Course1" should contain "Test assignment six"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test assignment one"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test assignment two"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test assignment three"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test assignment four"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test assignment five"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test forum one"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test forum two"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test quiz one"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test database one"
    And the unasus report table cell at row "Student s12" and column "Course1" should not contain "Test lti one"
    # CSS class confirma estado concluido/nao_concluido de cada estudante.
    And the unasus report table cell at row "Student s10" and column "Course1" should have css class "concluido"
    And the unasus report table cell at row "Student s11" and column "Course1" should have css class "nao_concluido"
    And the unasus report table cell at row "Student s12" and column "Course1" should have css class "nao_concluido"
