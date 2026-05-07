@unasus @report_unasus @javascript @filtro_cohort
Feature: Filtro de cohort e grupo nos relatórios UNA-SUS de tutoria
  Para garantir que coordenadores possam refinar a visão por cohort e por grupo de tutoria
  Como usuário manager com report/unasus:view_all
  Preciso que ao selecionar cohort1 apenas o estudante desse cohort apareça
  e ao selecionar cohort2 apenas o estudante desse cohort apareça

Background:
  Given a standard report_unasus tutoria fixture exists

  And the following "users" exist:
    | username | firstname | lastname | email                |
    | manager1 | Manager   | m1       | manager1@example.com |

  And the following "course enrolments" exist:
    | user     | course | role    |
    | manager1 | c1     | manager |

  And the following "permission overrides" exist:
    | capability             | permission | role    | contextlevel | reference |
    | report/unasus:view_all | Allow      | manager | Course       | c1        |

  # Dois cohorts de estudante individuais para testar granularidade do filtro.
  # CHs1 tem apenas student1 (grupo de teacher1); CHs6 tem apenas student6 (grupo de teacher2).
  # Os demais estudantes permanecem somente no cohort padrão CHs.
  And the following "cohorts" exist:
    | name      | idnumber | contextlevel | reference |
    | Cohort s1 | CHs1     | Category     | CAT1      |
    | Cohort s6 | CHs6     | Category     | CAT1      |

  And the following users are added to cohorts:
    | user     | cohort |
    | student1 | s1     |
    | student6 | s6     |

  # prazo_avaliacao=0: qualquer submissão não-avaliada é imediatamente atrasada,
  # garantindo que avaliacoes_em_atraso e estudante_sem_atividade_avaliada tenham dados.
  And the following config values are set as admin:
    | report_unasus_prazo_avaliacao | 0 |

  # Submissões de a2 (deadline passado) para 2 estudantes de cada grupo.
  # Resultado: avaliacoes_em_atraso mostra 2/4 por grupo (student3, student4, student7, student8 não submeteram).
  # Mantém estudante_sem_atividade_avaliada e estudante_sem_atividade_postada com dados.
  And I submit assignment "a2" for user "student1"
  And I submit assignment "a2" for user "student2"
  And I submit assignment "a2" for user "student5"
  And I submit assignment "a2" for user "student6"

  # Marca todas as atividades concluídas para 2 estudantes de cada grupo.
  # Resultado em atividades_concluidas_agrupadas: 2/4 por grupo.
  # student3 e student7 recebem apenas a3 completo → contam em atividades_nota_atribuida mas não
  # em atividades_concluidas_agrupadas (que exige conclusão de todas as atividades do curso).
  # Resultado final: avaliacoes_em_atraso=2/4, atividades_nota_atribuida=3/4, atividades_concluidas_agrupadas=2/4.
  And I mark all completion-enabled activities in course "c1" as complete for user "student1"
  And I mark all completion-enabled activities in course "c1" as complete for user "student2"
  And I mark all completion-enabled activities in course "c1" as complete for user "student5"
  And I mark all completion-enabled activities in course "c1" as complete for user "student6"
  And I mark activity "a3" as complete for user "student3"
  And I mark activity "a3" as complete for user "student7"

# Relatórios que listam estudantes individualmente como linhas da tabela.
# Filtro de cohort: CHs1 tem só student1 → apenas s1 aparece (s2–s4 do mesmo grupo, não).
# Filtro de grupo: group1 tem students 1–4 → s1 e s2 aparecem; group2 tem students 5–8.
@filtro_cohort
Scenario Outline: filtro de cohort e grupo restringe estudantes exibidos em relatórios de tutoria
  Given I log in as "manager1"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value       |
    | modo_exibicao | tabela      |
    | cohorts[0]    | cohort:CHs1 |
  Then I should see "Student s1"
  And I should not see "Student s2"
  And I should not see "Student s6"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value       |
    | modo_exibicao | tabela      |
    | cohorts[0]    | cohort:CHs6 |
  Then I should see "Student s6"
  And I should not see "Student s5"
  And I should not see "Student s1"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value                                 |
    | modo_exibicao | tabela                                |
    | tutores[0]    | relationshipgroup:relationship_group1 |
  Then I should see "Student s1"
  And I should see "Student s2"
  And I should not see "Student s6"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value                                 |
    | modo_exibicao | tabela                                |
    | tutores[0]    | relationshipgroup:relationship_group2 |
  Then I should see "Student s6"
  And I should see "Student s5"
  And I should not see "Student s1"

  Examples:
    | report                          |
    | entrega_de_atividades           |
    | atividades_vs_notas             |
    | boletim                         |
    | estudante_sem_atividade_postada |
    | estudante_sem_atividade_avaliada |
    | modulos_concluidos              |

# Relatórios de síntese agrupam dados por tutor como linha da tabela.
# O filtro de grupo remove a linha do tutor que não pertence ao grupo selecionado e
# exibe apenas os dados dos estudantes daquele grupo na linha resultante.
# Dados esperados por relatório com filtro de grupo:
#   avaliacoes_em_atraso: students 1-2 (group1) e 5-6 (group2) submeteram a2 → 2/4
#   atividades_nota_atribuida: students 1-2 + student3 com a3 completo → 3/4 (usa course_modules_completion)
#   atividades_concluidas_agrupadas: coluna é o curso ("Course1"); students 1-2 com tudo completo → 2/4
@filtro_cohort
Scenario Outline: filtro de grupo restringe tutores e dados em relatórios de síntese
  Given I log in as "manager1"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value                                 |
    | modo_exibicao | tabela                                |
    | tutores[0]    | relationshipgroup:relationship_group1 |
  Then I should see "Teacher t1"
  And I should not see "Teacher t2"
  And the unasus report table cell at row "Teacher t1" and column "<coluna>" should contain "<contagem>"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value                                 |
    | modo_exibicao | tabela                                |
    | tutores[0]    | relationshipgroup:relationship_group2 |
  Then I should see "Teacher t2"
  And I should not see "Teacher t1"
  And the unasus report table cell at row "Teacher t2" and column "<coluna>" should contain "<contagem>"

  Examples:
    | report                          | coluna                | contagem |
    | avaliacoes_em_atraso            | Test assignment two   | 2/4      |
    | atividades_nota_atribuida       | Test assignment three | 3/4      |
    | atividades_concluidas_agrupadas | Course1               | 2/4      |

# Para relatórios de síntese o filtro de cohort afeta apenas os contadores dentro
# de cada linha (todos os tutores continuam aparecendo).
# Com CHs1 (só student1, grupo de teacher1): Teacher t1 mostra 1 dado, Teacher t2 mostra 0.
# Com CHs6 (só student6, grupo de teacher2): Teacher t2 mostra 1 dado, Teacher t1 mostra 0.
@filtro_cohort
Scenario Outline: filtro de cohort altera contadores em relatórios de síntese
  Given I log in as "manager1"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value       |
    | modo_exibicao | tabela      |
    | cohorts[0]    | cohort:CHs1 |
  Then the unasus report table cell at row "Teacher t1" and column "<coluna>" should contain "1/"
  And the unasus report table cell at row "Teacher t2" and column "<coluna>" should contain "0/"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name          | value       |
    | modo_exibicao | tabela      |
    | cohorts[0]    | cohort:CHs6 |
  Then the unasus report table cell at row "Teacher t2" and column "<coluna>" should contain "1/"
  And the unasus report table cell at row "Teacher t1" and column "<coluna>" should contain "0/"

  Examples:
    | report                          | coluna                |
    | avaliacoes_em_atraso            | Test assignment two   |
    | atividades_nota_atribuida       | Test assignment three |
    | atividades_concluidas_agrupadas | Course1               |
