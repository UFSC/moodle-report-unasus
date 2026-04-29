# TESTS.md — Documentação dos Testes

Este arquivo descreve os testes automatizados do plugin `report_unasus`.

---

## Testes Unitários (PHPUnit)

**Arquivo:** `tests/unasus_datastructures_test.php`
**Classe:** `unasus_datastructures_testcase` (extends `advanced_testcase`)
**Grupo:** `@group report_unasus`
**Total:** 22 testes, 79 asserções

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
| `test_is_submission_due_exactly_at_deadline` | **Borda:** entregue exatamente no prazo → NOT due (`<`, não `<=`) |
| `test_is_submission_due_one_second_after_deadline` | **Borda:** 1 segundo após o prazo → IS due |
| `test_grade_zero_with_grade_date_is_valid_grade` | **Borda:** nota=0 com data de avaliação → `has_grade()=true` (0 ≠ null ≠ -1) |
| `test_grade_zero_without_grade_date_is_not_valid_grade` | **Borda:** nota=0 sem data de avaliação → `has_grade()=false` |
| `test_is_grade_needed_offline_activity_at_deadline_boundary` | **Borda:** atividade offline, deadline==now → nota IS necessária (usa `>`, não `>=`) |
| `test_is_activity_pending_offline_at_deadline_boundary` | **Borda:** atividade offline, deadline==now → IS pendente (usa `>`, não `>=`) |

---

## Testes de Integração (Behat)

**Driver:** Selenium Chrome Standalone (`selenium/standalone-chrome:3.141.59`)
**Container:** `moodle-local-unasuscp`
**URL base:** `http://local-unasus-cp.moodle.ufsc.br`
**Tags:** `@unasus @report_unasus @javascript`

### Como executar

```bash
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

### Feature: `tests/behat/unasus_acesso_tutor.feature`

**Descrição:** Cenários do relatório de acesso diário do tutor (exclusivo de coordenadores). Usa Background próprio com dados determinísticos de março de 2026.

| Cenário | O que verifica |
|---------|----------------|
| `acesso_tutor com período fixo mostra tutores e estados de acesso no mês` | Classes CSS acessou/nao_acessou por tutor e dia |
| `acesso_tutor separa acessos antes e depois da virada do dia` | Separação de acessos por dia calendário |
| `acesso_tutor não mostra dia de abril quando o período é março de 2026` | Colunas limitadas ao intervalo selecionado |
| `usuário sem view_all não visualiza relatório restrito acesso_tutor` | Menu não exibido para tutor sem capability |
| `usuário sem view_all não acessa diretamente relatório restrito acesso_tutor` | Acesso direto bloqueado por capability |
| `acesso_tutor exibe aviso para intervalo de datas inválido` | Validação de formato de data |
| `acesso_tutor exibe opção de exportar CSV para usuário com view_all` | Botão CSV visível para coordenador |
| `acesso_tutor exporta CSV com conteúdo esperado` | CSV com acessos, Media e Total corretos |

---

### Feature: `tests/behat/unasus_uso_sistema_tutor.feature`

**Descrição:** Cenários do relatório de horas de uso do sistema pelo tutor (exclusivo de coordenadores). Usa Background próprio com dados determinísticos de março de 2026.

| Cenário | O que verifica |
|---------|----------------|
| `uso_sistema_tutor com período fixo mostra tutores e colunas de média e total` | Classes CSS acessou/nao_acessou; colunas Media e Total presentes |
| `uso_sistema_tutor contabiliza faixas de meia hora, média e total` | Contagem em blocos de 30 min; Media e Total exatos |
| `uso_sistema_tutor separa acessos antes e depois da virada do dia` | Horas separadas por dia calendário |
| `uso_sistema_tutor não mostra dia de abril quando o período é março de 2026` | Colunas limitadas ao intervalo selecionado |
| `usuário sem view_all não visualiza relatório restrito uso_sistema_tutor` | Menu não exibido para tutor sem capability |
| `usuário sem view_all não acessa diretamente relatório restrito uso_sistema_tutor` | Acesso direto bloqueado por capability |
| `uso_sistema_tutor exibe aviso para formato de data inválido` | Validação de formato de data |
| `uso_sistema_tutor exibe opção de exportar CSV para usuário com view_all` | Botão CSV visível para coordenador |
| `uso_sistema_tutor exporta CSV com conteúdo esperado` | CSV com horas, Media e Total corretos |
| `uso_sistema_tutor exporta CSV com meia hora, média e total exatos` | CSV com valores de 0.5h, média e total precisos |

---

### Feature: `tests/behat/unasus_atividades_concluidas_agrupadas.feature`

**Descrição:** Cenários do relatório de síntese agrupada de atividades concluídas. Usa Background próprio com fixture reduzida.

| Cenário | O que verifica |
|---------|----------------|
| `síntese agrupada exibe valores por grupo e total` | Contadores por grupo de tutoria e total geral |
| `tutor sem view_all não visualiza dados de outro grupo na síntese agrupada` | Escopo do tutor sem `view_all` |

---

### Feature: `tests/behat/unasus_tcc.feature`

**Descrição:** Cenários dos relatórios de TCC (orientação). Usa Background próprio com fixture de orientação e webservice TCC mockado.

| Cenário | O que verifica |
|---------|----------------|
| `tcc_entrega_atividades - exibe capítulos por estudante com estados corretos` | Estados de entrega por capítulo (entregue, pendente, avaliado) |
| `tcc_concluido - capítulo avaliado vs não avaliado por estudante` | Distinção entre capítulos avaliados e pendentes |
| `tcc_consolidado - síntese de progresso por grupo de orientação` | Contadores de progresso por grupo e totais |
| `tcc_consolidado exporta CSV com dados esperados` | CSV com mesmos dados da tabela |

---

### Feature: `tests/behat/unasus_sem_dados.feature`

**Descrição:** Cenários sem nenhuma submissão — valida o comportamento dos relatórios com dados zero (edge case).

#### Dados de Background

Mesmos 15 usuários (`student1–12`, `teacher1–3`), mesmo curso `Course1`, mesmas atividades (`a1–a6`, `f1–f2`, `q1`, `d1`, `l1`) e mesmos 3 grupos de tutoria dos outros features — **porém sem nenhuma submissão realizada por qualquer estudante**.

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
| `sem_dados - atividades_concluidas_agrupadas mostra zero conclusoes por grupo` | atividades_concluidas_agrupadas | **Borda:** 0/4 0.0% por grupo, 0/12 total — relatório funciona sem dados |

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
