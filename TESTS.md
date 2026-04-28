# TESTS.md — Documentação dos Testes

Este arquivo descreve os testes automatizados do plugin `report_unasus`.

---

## Testes Unitários (PHPUnit)

**Arquivo:** `tests/unasus_datastructures_test.php`
**Classe:** `unasus_datastructures_testcase` (extends `advanced_testcase`)
**Grupo:** `@group report_unasus`
**Total:** 16 testes, 73 asserções

### Como executar

```bash
# Todos os testes do plugin
./run_tests.sh

# Um arquivo específico
./run_tests.sh tests/unasus_datastructures_test.php

# Forçar reset e reinicialização do banco
./run_tests.sh --reset
```

### Lista de Testes

| Método | O que verifica |
|--------|----------------|
| `test_report_unasus_activity` | `has_deadline()` com e sem prazo |
| `test_report_unasus_data_submission` | `is_submission_due()` para atividades sem prazo, com e sem entrega |
| `test_report_unasus_data_grade` | `is_grade_needed()` e `grade_due_days()` |
| `test_report_unasus_data_activity_status` | Estados de entrega: draft, submitted, new |
| `test_report_unasus_data_activity_offline` | Envios offline (atividades sem submissão digital) |
| `test_report_unasus_activity_invalid_constructor` | Lança `InvalidArgumentException` com argumentos inválidos |
| `test_report_unasus_activity_flags` | `has_submission()` e `has_grouping()` |
| `test_report_unasus_data_has_submitted_and_grade` | `has_submitted()` e `has_grade()` na classe base |
| `test_report_unasus_data_submission_historical` | `is_submission_due()` com dados históricos |
| `test_report_unasus_data_is_activity_pending` | `is_activity_pending()` em diferentes estados |
| `test_report_unasus_data_is_a_future_due` | `is_a_future_due()` para prazos futuros |
| `test_report_unasus_data_activity_grade_minus_one` | Nota -1 tratada como ausente (convenção Moodle) |
| `test_report_unasus_data_activity_status_new` | Status "new" para atividades sem interação |
| `test_report_unasus_data_forum` | Classe `report_unasus_data_forum` |
| `test_report_unasus_data_quiz` | Classe `report_unasus_data_quiz` |
| `test_report_unasus_data_is_member_of` | `is_member_of()` para pertencimento a grouping |

---

## Testes de Integração (Behat)

**Driver:** Selenium Chrome Standalone (`selenium/standalone-chrome:3.141.59`)
**Container:** `moodle-local-unasuscp`
**URL base:** `http://local-unasus-cp.moodle.ufsc.br`
**Tags:** `@unasus @report_unasus @javascript`

### Como executar

```bash
# Background compartilhado (sem cenários — apenas fixture de dados)
./run_behat.sh tests/behat/unasus.feature

# Relatório Boletim
./run_behat.sh tests/behat/unasus_boletim.feature

# Relatório de atribuição de notas
./run_behat.sh tests/behat/unasus_atividades_vs_notas.feature

# Relatório avaliações em atraso
./run_behat.sh tests/behat/unasus_avaliacoes_em_atraso.feature

# Relatório atividades nota atribuída (síntese de completude)
./run_behat.sh tests/behat/unasus_atividades_nota_atribuida.feature

# Relatório módulos concluídos
./run_behat.sh tests/behat/unasus_modulos_concluidos.feature

# Relatório entrega de atividades
./run_behat.sh tests/behat/unasus_entrega_de_atividades.feature

# Relatório estudante sem atividade postada
./run_behat.sh tests/behat/unasus_estudante_sem_atividade_postada.feature

# Relatório estudante sem atividade avaliada
./run_behat.sh tests/behat/unasus_estudante_sem_atividade_avaliada.feature

# Relatório atividades concluídas agrupadas
./run_behat.sh tests/behat/unasus_atividades_concluidas_agrupadas.feature

# Feature sem dados (sem submissões)
./run_behat.sh tests/behat/unasus_sem_dados.feature

# Relatórios restritos de logs de tutor
./run_behat.sh tests/behat/unasus_acesso_tutor.feature
./run_behat.sh tests/behat/unasus_uso_sistema_tutor.feature

# Relatórios TCC
./run_behat.sh tests/behat/unasus_tcc.feature

# Relatórios de manager (tutoria e filtros)
./run_behat.sh tests/behat/unasus_manager_tutoria.feature

# Relatórios de manager (orientação TCC)
./run_behat.sh tests/behat/unasus_manager_tcc.feature

# Controle de acesso por capability
./run_behat.sh tests/behat/unasus_permissions.feature
```

---

### Feature: `tests/behat/unasus.feature`

**Descrição:** Background compartilhado — contém apenas a fixture de dados (sem cenários). Todos os cenários foram extraídos para features dedicadas por relatório. O Background permanece aqui como fonte de verdade; cada feature dedicada copia esse Background temporariamente até a etapa de consolidação por helpers/fixtures compostos.

#### Dados de Background

- **15 usuários:** `student1–12`, `teacher1–3`
- **1 curso:** `Course1` (c1, CAT1, groupmode=1, enablecompletion=1)
- **Atividades:**
  - `a1–a6`: assignments com diferentes deadlines (futuro `2147483647`, passado `978307200`, zero `0`, passado `946684800`)
  - `f1–f2`: forums (prazo futuro e passado)
  - `q1`: quiz; `d1`: database; `l1`: lti
- **3 grupos de tutoria:**
  - `relationship_group1`: teacher1 + students 1–4
  - `relationship_group2`: teacher2 + students 5–8
  - `relationship_group3`: teacher3 + students 9–12
- **Submissões presentes no Background:**
  - `student2` → `a2` (Test assignment two)
  - `student3` → `a3` (Test assignment three)
  - `student1` → `a4` (0 dias após deadline)
  - `student1` → `a5` (10 dias após deadline)
  - `student1` → `a6` (11 dias após deadline)
  - `student1` → `f1` (discussão no fórum com prazo futuro)
  - `student2` → `f2` (discussão no fórum com prazo passado)

---

### Feature: `tests/behat/unasus_boletim.feature`

**Descrição:** Cenários do relatório Boletim extraídos da feature principal. O `Background` foi mantido igual temporariamente para preservar comportamento; a deduplicação por fixtures compostas fica para etapa posterior.

#### Cenários principais

| Cenário | O que verifica |
|---------|----------------|
| `boletim - verificacao de notas` | Notas por atividade, média final, legendas e classes CSS |
| `boletim exporta CSV com dados esperados` | Exportação CSV com notas e média final |
| `boletim - média ponderada no gradebook` | Média final ponderada conforme gradebook |
| `boletim - média simples com vazias=zero` | Inclusão de notas vazias no cálculo |
| `boletim - média ponderada com vazias=zero` | Pesos e notas vazias combinados |
| `boletim - atividades base 100 com nota final base 10` | Exibição da nota final em escala diferente |
| `boletim - todas as atividades avaliadas verifica media final` | Média final com todas as atividades avaliadas |

---

### Feature: `tests/behat/unasus_atividades_vs_notas.feature`

**Descrição:** Cenários do relatório de atribuição de notas extraídos da feature principal. O `Background` foi mantido igual temporariamente para preservar comportamento; a deduplicação por fixtures compostas fica para etapa posterior.

#### Cenários principais

| Cenário | O que verifica |
|---------|----------------|
| `atividades_vs_notas - estados de entrega e nota` | Estados textuais, notas, legendas e classes CSS |
| `atividades_vs_notas exporta CSV com dados esperados` | Exportação CSV com estados, notas e atraso |
| `tutor teacher1 vê apenas estudantes da própria tutoria no relatório de atribuição de notas` | Escopo do tutor sem `view_all` |

---

### Feature: `tests/behat/unasus_avaliacoes_em_atraso.feature`

**Descrição:** Cenários do relatório de avaliações em atraso extraídos da feature principal. Background copiado temporariamente.

| Cenário | O que verifica |
|---------|----------------|
| `avaliacoes_em_atraso - sintese de avaliacoes pendentes` | Contadores N/total por tutor e por atividade; média geral |
| `avaliacoes_em_atraso exporta CSV com dados esperados` | Exportação CSV com os mesmos valores |

---

### Feature: `tests/behat/unasus_atividades_nota_atribuida.feature`

**Descrição:** Cenários do relatório de síntese de completude (atividades concluídas) extraídos da feature principal. Background copiado temporariamente.

| Cenário | O que verifica |
|---------|----------------|
| `atividades_nota_atribuida - sintese de completude por tutor` | Percentuais de conclusão por tutor, atividade e total |
| `atividades_nota_atribuida exporta CSV com dados esperados` | Exportação CSV com os mesmos percentuais |

---

### Feature: `tests/behat/unasus_modulos_concluidos.feature`

**Descrição:** Cenários do relatório de módulos concluídos extraídos da feature principal. Background copiado temporariamente.

| Cenário | O que verifica |
|---------|----------------|
| `modulos_concluidos - verificacao de completion de atividades` | Nota final vs gradebook, atividades pendentes por estudante, CSS concluido/nao_concluido |

---

### Feature: `tests/behat/unasus_entrega_de_atividades.feature`

**Descrição:** Cenários do relatório de entrega de atividades extraídos da feature principal. Background copiado temporariamente.

| Cenário | O que verifica |
|---------|----------------|
| `entrega_de_atividades - todas as legendas de entrega` | "sem prazo", "10 dias", "11 dias"; classes CSS nao_entregue_mas_no_prazo, nao_entregue_fora_do_prazo, no_prazo, pouco_atraso, muito_atraso |
| `tutor teacher1 vê apenas estudantes da própria tutoria no relatório de entrega de atividades` | Escopo do tutor sem `view_all` |

---

### Feature: `tests/behat/unasus_estudante_sem_atividade_postada.feature`

**Descrição:** Cenários do relatório de estudantes sem atividade postada extraídos da feature principal. Background copiado temporariamente.

| Cenário | O que verifica |
|---------|----------------|
| `estudante_sem_atividade_postada - cobertura com limites e variacoes de borda` | Presença/ausência de estudantes; atividades pendentes por linha (student1, s2, s9, s10, s11, s12) |

---

### Feature: `tests/behat/unasus_estudante_sem_atividade_avaliada.feature`

**Descrição:** Cenários do relatório de estudantes sem atividade avaliada extraídos da feature principal. Background copiado temporariamente.

| Cenário | O que verifica |
|---------|----------------|
| `estudante_sem_atividade_avaliada - cobertura com limites e variacoes de borda` | Presença/ausência de estudantes com entregas pendentes de correção; atividades por linha (s1–s8) |

---

### Feature: `tests/behat/unasus_sem_dados.feature`

**Descrição:** Cenários sem nenhuma submissão — valida o comportamento dos relatórios com dados zero (edge case).

#### Dados de Background

Idêntico ao `unasus.feature` (mesmos 15 usuários, mesmo curso, mesmas atividades, mesmos 3 grupos), **porém sem nenhuma submissão realizada por qualquer estudante**.

#### Cenários principais

| Cenário | Relatório | O que verifica |
|---------|-----------|----------------|
| `sem_dados - entrega_de_atividades mostra atividades sem submissao` | entrega_de_atividades | Vê "sem prazo", "Test assignment two", "Test assignment one" |
| `sem_dados - estudante_sem_atividade_postada lista todos os estudantes` | estudante_sem_atividade_postada | Vê "Student" e "Test assignment two" |
| `sem_dados - estudante_sem_atividade_avaliada nao lista ninguem` | estudante_sem_atividade_avaliada | Não vê "Student" (sem entrega = sem o que avaliar) |
| `sem_dados - avaliacoes_em_atraso sem pendencias de avaliacao` | avaliacoes_em_atraso | Vê "Teacher", não vê "Student" |
| `sem_dados - atividades_vs_notas mostra estados sem entrega` | atividades_vs_notas | Vê "não entregue", "sem prazo", "no prazo" |
| `sem_dados - boletim exibe atividades sem notas` | boletim | Vê "Test assignment one" e "Test assignment two" |
| `sem_dados - modulos_concluidos sem nenhuma conclusao` | modulos_concluidos | Vê "Test assignment one" e "Student" |

---

### Feature: `tests/behat/unasus_manager_tutoria.feature`

**Descrição:** Cenários do manager com `view_all` nos relatórios de tutoria — visibilidade total, filtros por grupo/módulo e escopo de tutor sem `view_all`.

| Cenário | O que verifica |
|---------|----------------|
| `manager com view_all visualiza todos os estudantes na tutoria` | Vê todos os 12 estudantes; exibe filtros Cohorts e Grupos de Tutoria |
| `manager filtra relatório de tutoria por grupo de tutoria` | Filtra por `relationship_group1` — vê s1–s4, não vê s5/s12 |
| `manager filtra relatório por módulo` (Outline ×2) | Filtra por `courseid:c1`; exibe atividades do curso correto, não do c2 |
| `manager filtra sínteses de tutoria por grupo de tutoria` (Outline ×2) | Filtra por grupo — vê Teacher t1, não vê Teacher t2 |
| `tutor sem view_all nao visualiza estudantes de outros grupos na tutoria` | teacher1 não vê "Filtrar Cohorts:"; vê s1–s4, não s5/s12 |

---

### Feature: `tests/behat/unasus_manager_tcc.feature`

**Descrição:** Cenários do manager com `view_all` nos relatórios de orientação TCC — visibilidade total, filtro por grupo de orientação e escopo de orientador sem `view_all`.

| Cenário | O que verifica |
|---------|----------------|
| `manager com view_all visualiza todos os estudantes na orientação TCC` | Vê todos os 12 estudantes; exibe "Filtrar Grupos de Orientação:", "Resumo", "Capítulo 1" |
| `manager filtra relatório TCC por grupo de orientação` | Filtra por `relationship_group1` — vê s1–s4, não vê s5/s12 |
| `orientador sem view_all nao visualiza estudantes de outros grupos no TCC` | teacher1 não vê "Filtrar Cohorts:"; vê s1–s4, não s5/s12 |

---

### Feature: `tests/behat/unasus_permissions.feature`

**Descrição:** Cenários de controle de acesso por capability — valida que estudantes não conseguem acessar diretamente relatórios restritos.

| Cenário | O que verifica |
|---------|----------------|
| `estudante não acessa diretamente relatório de tutoria` | `student1` não tem acesso direto a `entrega_de_atividades` |
| `estudante não acessa diretamente relatório TCC` | `student1` não tem acesso direto a `tcc_entrega_atividades` |

---

## Steps Behat Customizados

Definidos em `tests/behat/behat_unasus.php`:

| Step | Descrição |
|------|-----------|
| `I add the user "X" with cohort "Y" to cohort members` | Adiciona usuário ao cohort via PHP (sem UI); aceita múltiplos espaços entre argumentos |
| `I set the grade of activity "X" for user "Y" to "Z"` | Atribui nota a uma entrega via banco de dados |
| `I set the submission date of activity "X" to "N" days after` | Ajusta data de submissão relativa ao deadline da atividade |
| `I mark activity "X" as complete for user "Y"` | Marca conclusão de atividade diretamente no banco |
| `I open the unasus report "X" directly for course "Y" with params` | Abre relatório por URL com parâmetros estáveis resolvidos (`courseid:`, `cmid:`, `cohort:`, `relationshipgroup:`) |
| `the user "X" should not have direct access to the unasus report "Y" in course "Z"` | Valida a matriz de capabilities do acesso direto sem depender da página de erro do Moodle |

---

## Notas e Limitações

- **SCORM excluído dos testes Behat:** falha em `iconv('iso-8859-1', 'utf-8//TRANSLIT')` durante `scorm_parse()` em `mod/scorm/tests/generator/lib.php` neste ambiente (PHP 5.6 + Linux).
- **potenciais_evasoes:** comentado em `lib.php` — entrada de menu indisponível nesta versão.
- **acesso_tutor / uso_sistema_tutor:** exclusivos de coordenadores; cobertos em features próprias com cenários de tabela, CSV, permissões e limites de data.
- **tcc_*:** relatórios de orientação; cobertos com webservice TCC mockado nos cenários Behat.
