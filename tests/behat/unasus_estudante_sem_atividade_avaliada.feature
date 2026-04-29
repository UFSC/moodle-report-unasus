 @unasus @report_unasus
Feature: Relatório UNA-SUS: estudante sem atividade avaliada
  Para acompanhar estudantes com entregas pendentes de avaliação no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso visualizar a lista de estudantes com atividades entregues e sem nota

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

  @javascript @estudante_sem_atividade_avaliada
Scenario: estudante_sem_atividade_avaliada - cobertura com limites e variacoes de borda
    # student2: duas entregas, uma corrigida e outra sem nota (deve aparecer).
    And I submit assignment "a4" for user "student2"

    # student4: duas entregas, uma corrigida e outra sem nota (deve aparecer).
    And I submit assignment "a1" for user "student4"
    And I submit assignment "a2" for user "student4"

    # student5: uma entrega corrigida (nao deve aparecer).
    And I submit assignment "a5" for user "student5"

    # student6: uma entrega sem nota (deve aparecer).
    And I submit assignment "a6" for user "student6"

    # student8: duas entregas, ambas corrigidas (nao deve aparecer).
    And I submit assignment "a4" for user "student8"
    And I submit assignment "a5" for user "student8"

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

    # Deve listar estudantes com pelo menos uma entrega/postagem sem nota.
    Then the unasus report should have row "Student s1"
    And the unasus report should have row "Student s2"
    And the unasus report should have row "Student s3"
    And the unasus report should have row "Student s4"
    And the unasus report should have row "Student s6"

    # Nao deve listar quem nao tem entrega pendente de avaliacao.
    And the unasus report should not have row "Student s5"
    And the unasus report should not have row "Student s7"
    And the unasus report should not have row "Student s8"

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

    And the unasus report row "Student s6" should contain activity "Test assignment six"
    And the unasus report row "Student s6" should not contain activity "Test assignment one"
    And the unasus report row "Student s6" should not contain activity "Test assignment two"
    And the unasus report row "Student s6" should not contain activity "Test assignment three"

