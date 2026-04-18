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
# Feature principal (com submissões)
./run_behat.sh tests/behat/unasus.feature

# Feature sem dados (sem submissões)
./run_behat.sh tests/behat/unasus_sem_dados.feature
```

---

### Feature: `tests/behat/unasus.feature`

**Descrição:** Cenários com dados reais de submissão — valida o comportamento dos relatórios quando estudantes realizaram entregas.

#### Dados de Background

- **15 usuários:** `student1–12`, `teacher1–3`
- **1 curso:** `Course1` (c1, CAT1, groupmode=1, enablecompletion=1)
- **Atividades:**
  - `a1–a7`: assignments com diferentes deadlines (futuro `2147483647`, passado `978307200`, zero `0`)
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
  - `student1` → `a7` (11 dias após deadline)
  - `student1` → `f1` (discussão no fórum com prazo futuro)
  - `student2` → `f2` (discussão no fórum com prazo passado)

#### Cenários (8 total)

| Cenário | Relatório | O que verifica |
|---------|-----------|----------------|
| `Correct report generation` | entrega_de_atividades | Geração básica; vê "Test assignment one" |
| `entrega_de_atividades - todas as legendas de entrega` | entrega_de_atividades | "sem prazo", "10 dias", "11 dias" |
| `estudante_sem_atividade_postada - lista de atividades nao postadas` | estudante_sem_atividade_postada | Vê "Student" e "Test assignment two" |
| `estudante_sem_atividade_avaliada - lista de atividades entregues sem nota` | estudante_sem_atividade_avaliada | Vê "Test assignment two" (student2 entregou mas sem nota) |
| `avaliacoes_em_atraso - sintese de avaliacoes pendentes` | avaliacoes_em_atraso | Vê "Test assignment one" |
| `atividades_vs_notas - estados de entrega e nota` | atividades_vs_notas | "sem prazo", "no prazo", "não entregue" |
| `boletim - verificacao de notas` | boletim | student1 com nota 100 em a4; vê "Test assignment four" |
| `modulos_concluidos - verificacao de completion de atividades` | modulos_concluidos | student1 concluiu a4; vê "Test assignment four" |

---

### Feature: `tests/behat/unasus_sem_dados.feature`

**Descrição:** Cenários sem nenhuma submissão — valida o comportamento dos relatórios com dados zero (edge case).

#### Dados de Background

Idêntico ao `unasus.feature` (mesmos 15 usuários, mesmo curso, mesmas atividades, mesmos 3 grupos), **porém sem nenhuma submissão realizada por qualquer estudante**.

#### Cenários (7 total)

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

## Steps Behat Customizados

Definidos em `tests/behat/behat_unasus.php`:

| Step | Descrição |
|------|-----------|
| `I add the user "X" with cohort "Y" to cohort members` | Adiciona usuário ao cohort via PHP (sem UI); aceita múltiplos espaços entre argumentos |
| `I set the grade of activity "X" for user "Y" to "Z"` | Atribui nota a uma entrega via banco de dados |
| `I set the submission date of activity "X" to "N" days after` | Ajusta data de submissão relativa ao deadline da atividade |
| `I mark activity "X" as complete for user "Y"` | Marca conclusão de atividade diretamente no banco |

---

## Notas e Limitações

- **SCORM excluído dos testes Behat:** falha em `iconv('iso-8859-1', 'utf-8//TRANSLIT')` durante `scorm_parse()` em `mod/scorm/tests/generator/lib.php` neste ambiente (PHP 5.6 + Linux).
- **potenciais_evasoes:** comentado em `lib.php` — entrada de menu indisponível nesta versão.
- **acesso_tutor / uso_sistema_tutor:** exclusivos de coordenadores; não cobertos nos cenários atuais.
- **tcc_*:** relatórios de orientação; requerem configuração adicional do sistema TCC externo.
