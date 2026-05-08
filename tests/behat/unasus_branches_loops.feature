@unasus @report_unasus @branches_loops
Feature: Pin do filtro de matrícula nas queries de relatorios/loops.php
  Para evitar regressões silenciosas durante a refatoração estrutural de loops.php / queries.php
  Como engenheiro de testes
  Preciso pinar dois contratos relacionados ao branch `!$r->enrol` (loops.php:64):
    1. Aluno em tutoria mas sem matrícula no curso é filtrado pelo INNER JOIN em queries.php
       — não aparece como linha no relatório.
    2. Outros membros do mesmo grupo de tutoria que continuam matriculados não são afetados.

Background:
  Given a standard report_unasus tutoria fixture exists

  @javascript @branches_loops
  Scenario: aluno-em-tutoria-sem-matricula-e-filtrado-do-relatorio
    # Standard fixture matricula student12 (em relationship_group3 com teacher3,
    # student9, student10, student11) e o inclui em uma tutoria. Removemos a matrícula
    # mantendo a participação na tutoria — essa é a combinação que exercita o filtro
    # INNER JOIN com user_enrolments em queries.php.
    And I submit assignment "a1" for user "student2"
    And I submit assignment "a2" for user "student3"
    And I unenrol user "student12" from course "c1"

    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Acompanhamento: atribuição de notas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"

    # Contrato 1: student12 desmatriculado é filtrado e NÃO aparece no relatório.
    # Se a refatoração trocar INNER JOIN por LEFT JOIN, o branch `!$r->enrol` em
    # loops.php:64 passaria a disparar e o aluno apareceria com data_empty / nao_aplicado;
    # nesse caso esta asserção falha e a mudança de contrato precisa ser explícita.
    Then I should not see "Student s12"

    # Contrato 2: student9 (mesmo relationship_group3 que student12, mas ainda
    # matriculado) segue aparecendo — o filtro é por matrícula, não por grupo de tutoria.
    And I should see "Student s9"

    # Sanidade: outros estudantes em outras tutorias e cabeçalhos de atividade
    # continuam renderizando sem erro.
    And I should see "Student s1"
    And I should see "Student s2"
    And I should see "Student s3"
    And I should see "Test assignment one"
    And I should see "Test forum one"
