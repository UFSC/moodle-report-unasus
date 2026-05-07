@unasus @report_unasus @branches_loops
Feature: Robustez de relatorios/loops.php quando aluno está em tutoria sem matrícula
  Para evitar regressões silenciosas durante a refatoração estrutural de loops.php
  Como engenheiro de testes
  Preciso validar que o relatório segue funcionando (sem PHP error / sem dados corrompidos)
  quando um membro de tutoria está sem matrícula no curso

Background:
  Given a standard report_unasus tutoria fixture exists

  @javascript @branches_loops
  Scenario: relatorio-renderiza-corretamente-quando-membro-tutoria-perde-matricula
    # Standard fixture matricula student12 e o inclui em uma tutoria. Removemos a matrícula
    # mantendo a participação na tutoria — essa é a combinação que exercita o filtro
    # de matrícula em relatorios/loops.php sem que dados de outros alunos sejam afetados.
    And I submit assignment "a1" for user "student2"
    And I submit assignment "a2" for user "student3"
    And I unenrol user "student12" from course "c1"

    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: atribuição de notas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"

    # Sanidade: o relatório renderiza e os outros estudantes seguem sendo processados
    # corretamente — a refatoração não pode quebrar essa garantia.
    Then I should see "Student s2"
    And I should see "Student s3"
    And I should see "Student s1"
    # Cabeçalhos de atividade ainda são renderizados sem erro
    And I should see "Test assignment one"
    And I should see "Test forum one"
