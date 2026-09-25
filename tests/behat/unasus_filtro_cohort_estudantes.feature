@unasus @report_unasus @javascript @filtro_cohort
Feature: Filtro de cohort e grupo nos relatórios UNA-SUS de tutoria que listam estudantes
  # O Background e' o mesmo de unasus_filtro_cohort_sintese.feature: a feature foi dividida em duas para o
  # Behat paralelo distribuir o custo. Mudar o Background exige mudar os dois arquivos.

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
