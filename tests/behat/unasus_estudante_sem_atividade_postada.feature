 @unasus @report_unasus
Feature: Relatório UNA-SUS: estudante sem atividade postada
  Para acompanhar estudantes sem entregas no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso visualizar a lista de estudantes com atividades não postadas

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

  @javascript @scope @estudante_sem_atividade_postada
Scenario: estudante_sem_atividade_postada - cobertura com limites e variacoes de borda
    # Relatório lista estudantes que têm atividades sem entrega e sem nota.
    # Casos de borda:
    # - student9  (grupo3): nenhuma entrega -> aparece com todas as atividades pendentes.
    # - student10 (grupo3): entregou apenas a4 -> aparece, a4 ausente na linha.
    # - student11 (grupo3): entregou a4 e a5 -> aparece, a4 e a5 ausentes na linha.
    # - student12 (grupo3): todas as atividades cobertas -> NAO deve aparecer.
    # - student1  (grupo1): background - entregou a4/a5/a6, a2 pendente -> aparece.
    # - student2  (grupo1): background - entregou a2, postou em f2; a4/a5/a6 pendentes -> aparece.

    # --- setup student10: entrega apenas a4 ---
    And I submit assignment "a4" for user "student10"

    # --- setup student11: entrega a4 e a5 ---
    And I submit assignment "a4" for user "student11"
    And I submit assignment "a5" for user "student11"

    # --- setup student12: todas as atividades cobertas (nao deve aparecer) ---
    # Atribui nota a todos os assignments sem exigir submissão (!has_grade() -> false).
    And I set the grade of activity "a1" for user "student12" to "0"
    And I set the grade of activity "a2" for user "student12" to "0"
    And I set the grade of activity "a3" for user "student12" to "0"
    And I set the grade of activity "a4" for user "student12" to "0"
    And I set the grade of activity "a5" for user "student12" to "0"
    And I set the grade of activity "a6" for user "student12" to "0"
    # Posta nos fóruns para que has_submitted() = true neles.
    And I post to forum "f1" as user "student12"
    And I post to forum "f2" as user "student12"
    # Cobre quiz (quiz_attempts + quiz_grades) e database (grade_grades).
    And I mark quiz "q1" as completed for user "student12"

    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Lista: atividades não postadas e sem nota" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"

    # Verificações de presença no relatório.
    Then the unasus report should have row "Student s1"
    And the unasus report should have row "Student s2"
    And the unasus report should have row "Student s9"
    And the unasus report should have row "Student s10"
    And the unasus report should have row "Student s11"
    # student12: todas as atividades cobertas -> nao deve aparecer.
    And the unasus report should not have row "Student s12"

    # Verificação por linha: student1 (background - entregou a4, a5, a6).
    And the unasus report row "Student s1" should contain activity "Test assignment two"
    And the unasus report row "Student s1" should not contain activity "Test assignment four"
    And the unasus report row "Student s1" should not contain activity "Test assignment five"
    And the unasus report row "Student s1" should not contain activity "Test assignment six"

    # Verificação por linha: student2 (background - entregou a2, postou em f2).
    And the unasus report row "Student s2" should not contain activity "Test assignment two"
    And the unasus report row "Student s2" should not contain activity "Test forum two"
    And the unasus report row "Student s2" should contain activity "Test assignment four"
    And the unasus report row "Student s2" should contain activity "Test assignment five"
    And the unasus report row "Student s2" should contain activity "Test assignment six"

    # Verificação por linha: student9 (nenhuma entrega - caso zero envios).
    And the unasus report row "Student s9" should contain activity "Test assignment two"
    And the unasus report row "Student s9" should contain activity "Test assignment four"
    And the unasus report row "Student s9" should contain activity "Test assignment five"
    And the unasus report row "Student s9" should contain activity "Test assignment six"

    # Verificação por linha: student10 (entregou apenas a4).
    And the unasus report row "Student s10" should not contain activity "Test assignment four"
    And the unasus report row "Student s10" should contain activity "Test assignment two"
    And the unasus report row "Student s10" should contain activity "Test assignment five"
    And the unasus report row "Student s10" should contain activity "Test assignment six"

    # Verificação por linha: student11 (entregou a4 e a5).
    And the unasus report row "Student s11" should not contain activity "Test assignment four"
    And the unasus report row "Student s11" should not contain activity "Test assignment five"
    And the unasus report row "Student s11" should contain activity "Test assignment two"
    And the unasus report row "Student s11" should contain activity "Test assignment six"
