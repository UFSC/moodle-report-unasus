@unasus @report_unasus @synthesis_queries
Feature: Pin do contrato LEFT JOIN das synthesis queries em queries.php
  Para evitar regressão silenciosa ao consolidar as 5 queries duplicadas em queries.php:720-810
  Como engenheiro de testes
  Preciso garantir que estudantes sem registro na atividade ainda apareçam no denominador
  → query_database_synthesis_from_users e query_lti_synthesis_from_users devem usar LEFT JOIN
    para que o total reflita TODOS os estudantes da tutoria, não apenas os que têm registro

Background:
  Given a standard report_unasus tutoria fixture exists

  @javascript @synthesis_queries
  Scenario: database-synthesis-includes-students-without-data-record
    # Apenas student1 marca a atividade de database como completa.
    # Os outros 11 estudantes NÃO interagem com d1.
    # Se a query usar INNER JOIN, o denominador reduziria para 1; com LEFT JOIN
    # devemos ver "1/12" — provando que estudantes sem registro permanecem no total.
    And I mark activity "d1" as complete for user "student1"

    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Síntese: atividades concluídas" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"

    # Contrato: total inclui todos os 12 estudantes, não apenas os com registro de data
    Then the unasus report table should have "1/12 8.3%" at row "Total alunos com atividade concluida / Total alunos" and column "Test database one"
    # Sanidade: outras colunas — 0/12 confirma que ninguém foi excluído
    And the unasus report table should have "0/12 0.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test lti one"
    And the unasus report table should have "0/12 0.0%" at row "Total alunos com atividade concluida / Total alunos" and column "Test assignment one"
