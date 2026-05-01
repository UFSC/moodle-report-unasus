@unasus @report_unasus @javascript @filtro_cohort @tcc
Feature: Filtro de cohort e grupo nos relatórios UNA-SUS de orientação TCC
  Para garantir que coordenadores possam refinar a visão por cohort e por grupo de orientação
  Como usuário manager com report/unasus:view_all
  Preciso que ao selecionar cohort1 apenas o estudante desse cohort apareça
  e ao selecionar cohort2 apenas o estudante desse cohort apareça

Background:
  Given the following "users" exist:
    | username  | firstname | lastname | email                  |
    | student1  | Student   | s1       | student1@example.com   |
    | student2  | Student   | s2       | student2@example.com   |
    | student3  | Student   | s3       | student3@example.com   |
    | student4  | Student   | s4       | student4@example.com   |
    | student5  | Student   | s5       | student5@example.com   |
    | student6  | Student   | s6       | student6@example.com   |
    | student7  | Student   | s7       | student7@example.com   |
    | student8  | Student   | s8       | student8@example.com   |
    | student9  | Student   | s9       | student9@example.com   |
    | student10 | Student   | s10      | student10@example.com  |
    | student11 | Student   | s11      | student11@example.com  |
    | student12 | Student   | s12      | student12@example.com  |
    | teacher1  | Teacher   | t1       | teacher1@example.com   |
    | teacher2  | Teacher   | t2       | teacher2@example.com   |
    | teacher3  | Teacher   | t3       | teacher3@example.com   |
    | manager1  | Manager   | m1       | manager1@example.com   |

  And the following "categories" exist:
    | name       | category | idnumber |
    | Category 1 | 0        | CAT1     |

  And the following "courses" exist:
    | fullname | shortname | category | groupmode | enablecompletion |
    | Course1  | c1        | CAT1     | 1         | 1                |

  And the following config values are set as admin:
    | enablecompletion               | 1              |
    | local_tutores_student_roles    | student        |
    | local_tutores_tutor_roles      | editingteacher |
    | local_tutores_orientador_roles | editingteacher |
    | report_unasus_prazo_maximo_entrega        | 10 |
    | report_unasus_prazo_maximo_avaliacao      | 5  |
    | report_unasus_prazo_avaliacao             | 1  |
    | report_unasus_tolerancia_potencial_evasao | 1  |

  And the following "cohorts" exist:
    | name           | idnumber | contextlevel | reference |
    | Cohort teacher | CHt      | Category     | CAT1      |
    | Cohort student | CHs      | Category     | CAT1      |
    | Cohort s1      | CHs1     | Category     | CAT1      |
    | Cohort s6      | CHs6     | Category     | CAT1      |

  And the following "activities" exist:
    | activity | course | idnumber | name              | intro      | completion | completionexpected |
    | lti      | C1     | ltcc1    | TCC Eixo 1        | TCC intro  | 1          | 978307200          |

  And the LTI activity "ltcc1" is configured as TCC with tcc_definition_id "1"
  And the TCC webservice returns definition with chapters:
    | id | title      | position |
    | 1  | Capítulo 1 | 1        |

  And the TCC webservice returns student data:
    | username  | chapter_position | state | state_date |
    | student1  | 0                | done  | 2024-01-01 |
    | student1  | 1                | done  | 2024-01-02 |
    | student2  | 0                | done  | 2024-01-01 |
    | student2  | 1                | done  | 2024-01-02 |
    | student3  | 0                | done   | 2024-01-01 |
    | student3  | 1                | review | 2024-01-02 |
    | student4  | 0                | null   | 2024-01-01 |
    | student4  | 1                | null   | 2024-01-01 |
    | student5  | 0                | done  | 2024-01-01 |
    | student5  | 1                | done  | 2024-01-02 |
    | student6  | 0                | done  | 2024-01-01 |
    | student6  | 1                | done  | 2024-01-02 |
    | student7  | 0                | done  | 2024-01-01 |
    | student7  | 1                | done  | 2024-01-02 |
    | student8  | 0                | done  | 2024-01-01 |
    | student8  | 1                | done  | 2024-01-02 |
    | student9  | 0                | done  | 2024-01-01 |
    | student9  | 1                | done  | 2024-01-02 |
    | student10 | 0                | done  | 2024-01-01 |
    | student10 | 1                | done  | 2024-01-02 |
    | student11 | 0                | done  | 2024-01-01 |
    | student11 | 1                | done  | 2024-01-02 |
    | student12 | 0                | null   | 2024-01-01 |
    | student12 | 1                | null   | 2024-01-01 |

  And the following "course enrolments" exist:
    | user      | course | role           |
    | student1  | c1     | student        |
    | student2  | c1     | student        |
    | student3  | c1     | student        |
    | student4  | c1     | student        |
    | student5  | c1     | student        |
    | student6  | c1     | student        |
    | student7  | c1     | student        |
    | student8  | c1     | student        |
    | student9  | c1     | student        |
    | student10 | c1     | student        |
    | student11 | c1     | student        |
    | student12 | c1     | student        |
    | teacher1  | c1     | editingteacher |
    | teacher2  | c1     | editingteacher |
    | teacher3  | c1     | editingteacher |
    | manager1  | c1     | manager        |

  And the following "permission overrides" exist:
    | capability                    | permission | role           | contextlevel | reference |
    | local/relationship:view       | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:manage     | Allow      | editingteacher | Category     | CAT1      |
    | local/relationship:assign     | Allow      | editingteacher | Category     | CAT1      |
    | moodle/cohort:view            | Allow      | editingteacher | Category     | CAT1      |
    | report/unasus:view_tutoria    | Allow      | editingteacher | Course       | c1        |
    | report/unasus:view_orientacao | Allow      | editingteacher | Course       | c1        |
    | report/unasus:view_all        | Allow      | manager        | Course       | c1        |

  And the following "role assigns" exist:
    | user     | role           | contextlevel | reference |
    | teacher1 | editingteacher | Category     | CAT1      |
    | teacher2 | editingteacher | Category     | CAT1      |
    | teacher3 | editingteacher | Category     | CAT1      |

  And the following users are added to cohorts:
    | user      | cohort  |
    | teacher1  | teacher |
    | teacher2  | teacher |
    | teacher3  | teacher |
    | student1  | student |
    | student2  | student |
    | student3  | student |
    | student4  | student |
    | student5  | student |
    | student6  | student |
    | student7  | student |
    | student8  | student |
    | student9  | student |
    | student10 | student |
    | student11 | student |
    | student12 | student |

  And a basic unasus tutoria environment exists:
  And instance the tag "grupo_orientacao" at relationship "relationship1"

  And the following tutoria memberships exist:
    | user      | group               |
    | teacher1  | relationship_group1 |
    | student1  | relationship_group1 |
    | student2  | relationship_group1 |
    | student3  | relationship_group1 |
    | student4  | relationship_group1 |
    | teacher2  | relationship_group2 |
    | student5  | relationship_group2 |
    | student6  | relationship_group2 |
    | student7  | relationship_group2 |
    | student8  | relationship_group2 |
    | teacher3  | relationship_group3 |
    | student9  | relationship_group3 |
    | student10 | relationship_group3 |
    | student11 | relationship_group3 |
    | student12 | relationship_group3 |

  # CHs1 tem apenas student1 (grupo de teacher1); CHs6 tem apenas student6 (grupo de teacher2).
  # Adicionados após os tutoria memberships para evitar conflito na query de create_relationship_members:
  # se o usuário estiver em múltiplos cohorts antes de ser adicionado ao relationship group, a
  # subquery "WHERE rc.cohortid = (SELECT cm.cohortid ...)" retorna múltiplas linhas e falha.
  And the following users are added to cohorts:
    | user     | cohort |
    | student1 | s1     |
    | student6 | s6     |

# tcc_entrega_atividades e tcc_concluido listam estudantes individualmente como linhas da tabela.
# tcc_consolidado agrega por grupo de orientação (não por estudante) — testado em cenário separado abaixo.
# Filtro de cohort: CHs1 tem só student1 → apenas s1 aparece (s2–s4 do mesmo grupo, não).
# Filtro de grupo: orientadores group1 tem students 1–4 → s1 e s2 aparecem; group2 tem students 5–8.
@filtro_cohort @tcc
Scenario Outline: filtro de cohort e grupo restringe estudantes exibidos em relatórios TCC
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
    | name            | value                                 |
    | modo_exibicao   | tabela                                |
    | orientadores[0] | relationshipgroup:relationship_group1 |
  Then I should see "Student s1"
  And I should see "Student s2"
  And I should not see "Student s6"

  When I open the unasus report "<report>" directly for course "c1" with params:
    | name            | value                                 |
    | modo_exibicao   | tabela                                |
    | orientadores[0] | relationshipgroup:relationship_group2 |
  Then I should see "Student s6"
  And I should see "Student s5"
  And I should not see "Student s1"

  Examples:
    | report                 |
    | tcc_entrega_atividades |
    | tcc_concluido          |

# tcc_consolidado agrega por grupo de orientação como linha da tabela (não por estudante).
# Colunas: Resumo | Capítulo 1 | Atividades Concluídas | Não acessado | Avaliado
# Dados do mock:
#   group1 (s1–s4): s1=done/done, s2=done/done, s3=done/review, s4=null/null
#     → Resumo=3, Cap1=2, tcc_completo=2, nao_acessado=1
#   group2 (s5–s8): s5=done/done, s6=done/done, s7=done/done, s8=done/done
#     → Resumo=4, Cap1=4, tcc_completo=4, nao_acessado=0
#   group3 (s9–s12): s9=done/done, s10=done/done, s11=done/done, s12=null/null
#     → Resumo=3, Cap1=3, tcc_completo=3, nao_acessado=1
# Nota: denominador = 12 (mock retorna todos os 12 alunos independente do grupo).
# Filtro de grupo: remove linhas dos outros grupos da tabela.
# Filtro de cohort: mantém todas as linhas mas altera os contadores
#   (CHs1 = só student1 → group1 mostra 1/ em Resumo e Cap1).
@filtro_cohort @tcc @tcc_consolidado
Scenario: tcc_consolidado exibe valores por grupo e por capítulo, respeitando filtros
  Given I log in as "manager1"

  # Sem filtro: todos os grupos e total visíveis, com contagens por capítulo e por tipo.
  # Denominador = 12 (mock retorna todos os 12 estudantes independente do grupo).
  # group1: Resumo=3 (s4 null), Cap1=2 (s3 review, s4 null), AtivConc=2 (s3/s4 incompletos), NaoAcess=1 (s4 null).
  # group2: Resumo=4, Cap1=4, AtivConc=4, NaoAcess=0 (todos done).
  # group3: Resumo=3 (s12 null), Cap1=3 (s12 null), AtivConc=3, NaoAcess=1 (s12 null).
  # Total: Resumo=3+4+3=10, Cap1=2+4+3=9, AtivConc=2+4+3=9, NaoAcess=1+0+1=2. Denominador total=36.
  When I open the unasus report "tcc_consolidado" directly for course "c1" with params:
    | name          | value  |
    | modo_exibicao | tabela |
  Then I should see "Teacher t1"
  
  And I take a screenshot
  
  And I should see "Teacher t2"
  And I should see "Teacher t3"
  And I should see "Total por curso"
  And the unasus report table cell at row "Teacher t1" and column "Resumo" should contain "3/"
  And the unasus report table cell at row "Teacher t1" and column "Capítulo 1" should contain "2/"
  And the unasus report table cell at row "Teacher t1" and column "Atividades Concluídas" should contain "2/"
  And the unasus report table cell at row "Teacher t1" and column "Não acessado" should contain "1/"
  And the unasus report table cell at row "Teacher t2" and column "Resumo" should contain "4/"
  And the unasus report table cell at row "Teacher t2" and column "Capítulo 1" should contain "4/"
  And the unasus report table cell at row "Teacher t2" and column "Atividades Concluídas" should contain "4/"
  And the unasus report table cell at row "Teacher t2" and column "Não acessado" should contain "0/"
  And the unasus report table cell at row "Teacher t3" and column "Resumo" should contain "3/"
  And the unasus report table cell at row "Teacher t3" and column "Capítulo 1" should contain "3/"
  And the unasus report table cell at row "Teacher t3" and column "Atividades Concluídas" should contain "3/"
  And the unasus report table cell at row "Teacher t3" and column "Não acessado" should contain "1/"
  And the unasus report table cell at row "Total por curso" and column "Resumo" should contain "10/"
  And the unasus report table cell at row "Total por curso" and column "Capítulo 1" should contain "9/"
  And the unasus report table cell at row "Total por curso" and column "Atividades Concluídas" should contain "9/"
  And the unasus report table cell at row "Total por curso" and column "Não acessado" should contain "2/"

  # Filtro de grupo: group1 → apenas Teacher t1 visível; total = valores de group1.
  When I open the unasus report "tcc_consolidado" directly for course "c1" with params:
    | name            | value                                 |
    | modo_exibicao   | tabela                                |
    | orientadores[0] | relationshipgroup:relationship_group1 |
  Then I should see "Teacher t1"
  
  And I take a screenshot
  
  And I should not see "Teacher t2"
  And I should not see "Teacher t3"
  And I should see "Total por curso"
  And the unasus report table cell at row "Teacher t1" and column "Resumo" should contain "3/"
  And the unasus report table cell at row "Teacher t1" and column "Capítulo 1" should contain "2/"
  And the unasus report table cell at row "Teacher t1" and column "Atividades Concluídas" should contain "2/"
  And the unasus report table cell at row "Teacher t1" and column "Não acessado" should contain "1/"
  And the unasus report table cell at row "Total por curso" and column "Resumo" should contain "3/"
  And the unasus report table cell at row "Total por curso" and column "Capítulo 1" should contain "2/"
  And the unasus report table cell at row "Total por curso" and column "Atividades Concluídas" should contain "2/"
  And the unasus report table cell at row "Total por curso" and column "Não acessado" should contain "1/"

  # Filtro de grupo: group2 → apenas Teacher t2 visível; total = valores de group2.
  When I open the unasus report "tcc_consolidado" directly for course "c1" with params:
    | name            | value                                 |
    | modo_exibicao   | tabela                                |
    | orientadores[0] | relationshipgroup:relationship_group2 |
  Then I should see "Teacher t2"
  
  And I take a screenshot
  
  And I should not see "Teacher t1"
  And I should not see "Teacher t3"
  And I should see "Total por curso"
  And the unasus report table cell at row "Teacher t2" and column "Resumo" should contain "4/"
  And the unasus report table cell at row "Teacher t2" and column "Capítulo 1" should contain "4/"
  And the unasus report table cell at row "Teacher t2" and column "Atividades Concluídas" should contain "4/"
  And the unasus report table cell at row "Teacher t2" and column "Não acessado" should contain "0/"
  And the unasus report table cell at row "Total por curso" and column "Resumo" should contain "4/"
  And the unasus report table cell at row "Total por curso" and column "Capítulo 1" should contain "4/"
  And the unasus report table cell at row "Total por curso" and column "Atividades Concluídas" should contain "4/"
  And the unasus report table cell at row "Total por curso" and column "Não acessado" should contain "0/"

  # Filtro de cohort CHs1 (só student1, group1): todos os grupos ainda visíveis (cohort não remove linhas).
  # student1 tem abstract=done e cap1=done: group1 Resumo=1, Cap1=1, AtivConc=1, NaoAcess=0.
  # Groups 2 e 3 com 0 estudantes do cohort CHs1: colunas Resumo e Cap1 aparecem com 0/,
  # mas 'Atividades Concluídas' e 'Não acessado' podem não aparecer para esses grupos.
  # Total: Resumo=1/36, Cap1=1/36; AtivConc e NaoAcess acumulam apenas group1.
  When I open the unasus report "tcc_consolidado" directly for course "c1" with params:
    | name          | value       |
    | modo_exibicao | tabela      |
    | cohorts[0]    | cohort:CHs1 |
  Then I should see "Teacher t1"

  And I take a screenshot

  And I should see "Teacher t2"
  And I should see "Teacher t3"
  And I should see "Total por curso"
  And the unasus report table cell at row "Teacher t1" and column "Resumo" should contain "1/"
  And the unasus report table cell at row "Teacher t1" and column "Capítulo 1" should contain "1/"
  And the unasus report table cell at row "Teacher t1" and column "Atividades Concluídas" should contain "1/"
  And the unasus report table cell at row "Teacher t1" and column "Não acessado" should contain "0/"
  And the unasus report table cell at row "Teacher t1" and column "Avaliado" should contain "0/"
  And the unasus report table cell at row "Teacher t2" and column "Resumo" should contain "0/"
  And the unasus report table cell at row "Teacher t2" and column "Capítulo 1" should contain "0/"
  And the unasus report table cell at row "Teacher t2" and column "Atividades Concluídas" should contain "0/"
  And the unasus report table cell at row "Teacher t2" and column "Não acessado" should contain "0/"
  And the unasus report table cell at row "Teacher t2" and column "Avaliado" should contain "0/"
  And the unasus report table cell at row "Teacher t3" and column "Resumo" should contain "0/"
  And the unasus report table cell at row "Teacher t3" and column "Capítulo 1" should contain "0/"
  And the unasus report table cell at row "Teacher t3" and column "Atividades Concluídas" should contain "0/"
  And the unasus report table cell at row "Teacher t3" and column "Não acessado" should contain "0/"
  And the unasus report table cell at row "Teacher t3" and column "Avaliado" should contain "0/"
  And the unasus report table cell at row "Total por curso" and column "Resumo" should contain "1/"
  And the unasus report table cell at row "Total por curso" and column "Capítulo 1" should contain "1/"
  And the unasus report table cell at row "Total por curso" and column "Atividades Concluídas" should contain "1/"
  And the unasus report table cell at row "Total por curso" and column "Não acessado" should contain "0/"
