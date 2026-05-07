# TESTS.md — Documentação dos Testes

Este arquivo descreve os testes automatizados do plugin `report_unasus`.

---

## Testes Unitários (PHPUnit)

**Arquivos:**
- `tests/unasus_datastructures_test.php` — classe `unasus_datastructures_testcase`
- `tests/unasus_memoization_test.php` — classe `unasus_memoization_testcase` (Fase 3)
- `tests/unasus_factory_helpers_test.php` — classe `unasus_factory_helpers_testcase` (Fase 4)
- `tests/unasus_render_test.php` — classe `unasus_render_testcase` (Fase 5)

Todos extendem `advanced_testcase` no grupo `@group report_unasus`.

**Total:** ~80 testes, ~250 asserções (todos verdes em ~5s combinados).

### Como executar

```bash
# Todos os testes do plugin
./run_tests.sh

# Um arquivo específico
./run_tests.sh tests/unasus_datastructures_test.php
./run_tests.sh tests/unasus_memoization_test.php
./run_tests.sh tests/unasus_factory_helpers_test.php
./run_tests.sh tests/unasus_render_test.php

# Forçar reset e reinicialização do banco
./run_tests.sh --reset
```

### Cobertura por arquivo

#### `tests/unasus_datastructures_test.php` (~50 testes)

Cobre `report_unasus_activity`, `report_unasus_data` e suas subclasses (`activities_datastructures.php`):

- **Base:** `has_deadline`, `has_submission`, `has_grouping`, `has_submitted`, `has_grade`, `is_submission_due` (incluindo dados históricos), `is_grade_needed`, `is_activity_pending`, `is_a_future_due`, `is_member_of`, construtor inválido.
- **Subclasses de activity (Fase 1):** `assign_activity` (nosubmissions/grade=0), `generic_activity` (inversão `nosubmissions`), `db_activity` (defaults true/true), `scorm_activity` (false/true), `quiz_activity` (switch de nome curto), `lti_activity2` (heredita generic + campos LTI), `lti_activity_tcc2` (carrega `tcc_definition`), `chapter_tcc_activity` (false/false + `__toString` label).
- **Subclasses de data (Fase 2):** `data_db`, `data_scorm` (completion = grade==grademax), `data_lti` (has_grade simples), `data_lti_tcc` (status como dict por capítulo), `data_nota_final`, `data_empty` (todos predicados overridden = false).
- **Sentinela `"-1"` (Fase 2):** parse para `null` em `data_db`, `data_scorm`, `data_lti`, `data_quiz`, `data_forum`, `data_nota_final` (além do já existente em `data_activity`).
- **Bordas:** `submission_date == deadline` (false), `submission_date == deadline + 1s` (true), `grade=0 + grade_date` (válida), `deadline > now` vs `deadline >= now` para atividade offline.

#### `tests/unasus_memoization_test.php` (5 testes — Fase 3)

| Método | O que verifica |
|--------|----------------|
| `test_get_tcc_definition_memoizes_by_input_string` | Idempotência de `report_unasus_get_tcc_definition()` na mesma entrada |
| `test_get_tcc_definition_distinct_inputs_distinct_results` | Inputs distintos não colidem na cache |
| `test_get_tcc_definition_handles_malformed_input` | Pares sem `=` são silenciosamente ignorados |
| `test_sistema_tcc_client_caches_get_tcc_definition` | `SistemaTccClient::get_tcc_definition()` cacheia por `url|key|id` (verificado mutando `set_mock_responses` entre chamadas) |
| `test_sistema_tcc_client_distinct_keys_distinct_calls` | Clients com url/consumer_key diferentes não compartilham cache |

#### `tests/unasus_factory_helpers_test.php` (10 testes — Fase 4)

| Método | O que verifica |
|--------|----------------|
| `test_date_interval_normal_range_valid` / `_inverse_range_invalid` / `_invalid_format_returns_false` / `_empty_returns_false` / `_same_day_valid` | `report_unasus_date_interval_is_valid` em todos os casos críticos |
| `test_interval_boundaries_adds_one_day_to_data_fim` | `report_acesso_tutor::get_interval_boundaries()` adiciona +1 dia em `data_fim` (limite aberto do `DatePeriod`) e normaliza para meia-noite |
| `test_interval_boundaries_dst_transition` | Atravessa transição DST (Brasil 2018) — `P1D` é civil, calendar-aware |
| `test_interval_boundaries_cross_year` | Intervalo que cruza ano novo |
| `test_get_days_interval_same_day` / `_full_range` | Expansão de `get_days_interval()` para 1 dia / 5 dias |

#### `tests/unasus_render_test.php` (~43 testes — Fase 5)

Cobre 8 classes render de `datastructures.php` — transições puras `estado → __toString` e `estado → get_css_class`, com bordas em `prazo_maximo_avaliacao`, `prazo_maximo_entrega` e `passing_grade_percentage`:

- `atividades_vs_notas_render` — 8 estados + bordas pouco/muito atraso
- `boletim_render` / `nota_final_render` — 3 estados cada + bordas acima/abaixo da média
- `entrega_de_atividades_render` — 6 estados + bordas pouco/muito atraso
- `tcc_entrega_atividades_render` — 4 estados + render `Hoje` vs N dias
- `tcc_concluido_render` — 2 estados
- `historico_atribuicao_notas_render` — 5 estados
- `modulos_concluidos_render` — 3 estados via `get_state()`
- `potenciais_evasoes_render` — transições internas via `add_atividade_nao_realizada`

---

## Testes de Integração (Behat)

**Driver:** Selenium Chrome Standalone (`selenium/standalone-chrome:3.141.59-selenium`, Chrome 75)
**Container Moodle:** `moodle-local-unasuscp`
**Container Selenium:** `selenium-chrome-unasuscp`
**URL base:** `http://local-unasus-cp.moodle.ufsc.br`
**Tags:** `@unasus @report_unasus @javascript`
**Cobertura confirmada:** 92 cenários, ~2540 passos, ~16 min de execução total. Inclui:
- `unasus_branches_loops.feature` (Fase 6) — pin do comportamento "membro de tutoria sem matrícula" em `relatorios/loops.php`.
- `unasus_synthesis_queries.feature` (Fase 7) — pin do contrato LEFT JOIN em `query_database_synthesis_from_users` (denominador 1/12 prova que estudantes sem registro permanecem no total).

> **Pré-requisito de ambiente:** o usuário precisa estar no grupo `docker` (`sudo usermod -aG docker $USER` + nova sessão). Os scripts não usam `sudo`.

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

# Filtro de cohort e grupo — relatórios de tutoria (9 relatórios)
./run_behat.sh tests/behat/unasus_filtro_cohort.feature

# Filtro de cohort e grupo — relatórios TCC (3 relatórios)
./run_behat.sh tests/behat/unasus_filtro_cohort_tcc.feature

# Controle de acesso por capability
./run_behat.sh tests/behat/unasus_permissions.feature

# Pin de branches em loops.php (membro de tutoria sem matrícula)
./run_behat.sh tests/behat/unasus_branches_loops.feature

# Pin do contrato LEFT JOIN das synthesis queries
./run_behat.sh tests/behat/unasus_synthesis_queries.feature
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
| `config_borda - todos os estudantes com nota identica mostram mesma classificacao CSS` | **Borda:** variância zero — student1 e student2 com nota 80 → ambos `na_media` |

---

### Feature: `tests/behat/unasus_atividades_vs_notas.feature`

**Descrição:** Cenários do relatório de atribuição de notas extraídos da feature principal. O `Background` foi mantido igual temporariamente para preservar comportamento; a deduplicação por fixtures compostas fica para etapa posterior.

#### Cenários principais

| Cenário | O que verifica |
|---------|----------------|
| `atividades_vs_notas - estados de entrega e nota` | Estados textuais, notas, legendas e classes CSS |
| `atividades_vs_notas exporta CSV com dados esperados` | Exportação CSV com estados, notas e atraso |
| `tutor teacher1 vê apenas estudantes da própria tutoria no relatório de atribuição de notas` | Escopo do tutor sem `view_all` |
| `config_borda - prazo_avaliacao zero classifica nota de 1 dia como atrasada` | **Borda:** `prazo_avaliacao=0` → nota 1 dia após submissão = `nota_atribuida_atraso` (com prazo=1 seria `nota_atribuida`) |

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
| *(grupo mínimo)* | **Borda:** grupos já têm 1 estudante cada (1/1 100.0%, 0/1 0.0%) — boundary implícito coberto nos dois cenários acima |

---

### Feature: `tests/behat/unasus_tcc.feature`

**Descrição:** Cenários dos relatórios de TCC (orientação). Usa Background próprio com fixture de orientação e webservice TCC mockado.

| Cenário | O que verifica |
|---------|----------------|
| `tcc_entrega_atividades - exibe capítulos por estudante com estados corretos` | Estados de entrega por capítulo (entregue, pendente, avaliado) |
| `tcc_concluido - capítulo avaliado vs não avaliado por estudante` | Distinção entre capítulos avaliados e pendentes |
| `tcc_consolidado - síntese de progresso por grupo de orientação` | Contadores de progresso por grupo e totais |
| `tcc_consolidado exporta CSV com dados esperados` | CSV com mesmos dados da tabela |
| `orientador teacher1 vê apenas estudantes do próprio grupo de orientação` (Outline × 2) | Escopo por grupo de orientação em tcc_entrega_atividades e tcc_concluido |
| Borda: `tcc_borda - webservice indisponivel exibe relatorio sem capitulos TCC` | WS retorna null → relatório carrega sem erro, sem colunas de capítulo |

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

### Feature: `tests/behat/unasus_filtro_cohort.feature`

**Descrição:** Verifica que o filtro de cohort e o filtro de grupo de tutoria restringem corretamente os dados exibidos em todos os 9 relatórios de tutoria com `mostrar_filtro_cohorts = true`. Usa `a standard report_unasus tutoria fixture exists` com manager1 adicionado via Gherkin e dois cohorts individuais: `CHs1` (somente student1, grupo de teacher1) e `CHs6` (somente student6, grupo de teacher2). Students 1–8 submetem a2 (deadline passado, `prazo_avaliacao=0`) para alimentar `avaliacoes_em_atraso` e `estudante_sem_atividade_avaliada`.

#### Dados de Background

- **Fixture base:** `a standard report_unasus tutoria fixture exists` (student1–12, teacher1–3, 3 grupos, a1–a6, f1–f2, q1, d1, l1)
- **Cohorts extras:** `Cohort s1` (CHs1) → student1; `Cohort s6` (CHs6) → student6
- **Submissões de a2:** student1–student8 (sem nota, `prazo_avaliacao=0`)

#### Cenários

| Scenario Outline | Relatórios cobertos | O que verifica |
|-----------------|---------------------|----------------|
| `filtro de cohort e grupo restringe estudantes exibidos em relatórios de tutoria` | entrega_de_atividades, atividades_vs_notas, boletim, estudante_sem_atividade_postada, estudante_sem_atividade_avaliada, modulos_concluidos | CHs1 → vê s1, não vê s2/s6; CHs6 → vê s6, não vê s5/s1; group1 → vê s1 e s2, não s6; group2 → vê s6 e s5, não s1 |
| `filtro de grupo restringe tutores exibidos em relatórios de síntese` | avaliacoes_em_atraso, atividades_nota_atribuida, atividades_concluidas_agrupadas | group1 → vê Teacher t1, não t2; group2 → vê Teacher t2, não t1 (filtro de grupo remove a linha do tutor; cohort filter apenas altera contadores, não remove linhas) |
| `filtro de cohort altera contadores em relatório de síntese avaliacoes_em_atraso` | avaliacoes_em_atraso | CHs1 → célula Teacher t1 / "Test assignment two" contém "1/" (1 avaliação pendente); célula Teacher t2 contém "0/"; CHs6 → invertido |

---

### Feature: `tests/behat/unasus_filtro_cohort_tcc.feature`

**Descrição:** Verifica que o filtro de cohort e o filtro de grupo de orientação restringem corretamente os dados exibidos nos relatórios TCC. Cobre `tcc_entrega_atividades` e `tcc_concluido` (linhas por estudante) via Scenario Outline e `tcc_consolidado` (linhas por grupo de orientação) via cenário dedicado. Usa Background Gherkin completo (como `unasus_manager_tcc.feature`) com dois cohorts individuais: `CHs1` (somente student1) e `CHs6` (somente student6). O webservice TCC mockado retorna dados `done` para todos os 12 estudantes.

#### Dados de Background

- **Usuários:** student1–12, teacher1–3, manager1 (igual ao manager_tcc.feature)
- **Cohorts extras:** `Cohort s1` (CHs1) → student1; `Cohort s6` (CHs6) → student6
- **Relationship tag:** `grupo_orientacao` (relatórios TCC usam `orientadores[]` no filtro de grupo)

#### Cenários

| Cenário | Relatórios cobertos | O que verifica |
|---------|---------------------|----------------|
| `filtro de cohort e grupo restringe estudantes exibidos em relatórios TCC` (Outline ×2) | tcc_entrega_atividades, tcc_concluido | CHs1 → vê s1, não vê s2/s6; CHs6 → vê s6, não vê s5/s1; orientadores group1 → vê s1 e s2, não s6; group2 → vê s6 e s5, não s1 |
| `tcc_consolidado exibe valores por grupo e por capítulo, respeitando filtros` | tcc_consolidado | Sem filtro: contagens por grupo (Resumo, Capítulo 1, Atividades Concluídas, Não acessado) e total geral; filtro de grupo remove linhas dos outros grupos; filtro de cohort (CHs1) mantém todas as linhas mas reduz contadores ao único estudante do cohort |

---

### Feature: `tests/behat/unasus_permissions.feature`

**Descrição:** Cenários de controle de acesso por capability — valida que estudantes não conseguem acessar diretamente relatórios restritos.

| Cenário | O que verifica |
|---------|----------------|
| `estudante não acessa diretamente relatório de tutoria` | `student1` não tem acesso direto a `entrega_de_atividades` |
| `estudante não acessa diretamente relatório TCC` | `student1` não tem acesso direto a `tcc_entrega_atividades` |

---

### Feature: `tests/behat/unasus_permissions_scope.feature`

**Descrição:** Cenários de borda sobre escopo de visualização do tutor — isolamento por grupo de tutoria e comportamento com grupo sem estudantes.

| Cenário | O que verifica |
|---------|----------------|
| `tutor_scope - tutor ve apenas estudantes do seu grupo` | **Borda:** teacher1 (group1) vê "Student s1", não vê "Student s5" (group2) |
| `tutor_scope - tutor com grupo sem estudantes nao causa erro` | **Borda:** teacher3 com group3 vazio — relatório carrega sem erro, sem "Student" |

---

### Feature: `tests/behat/unasus_csv_borda.feature`

**Descrição:** Cenários de borda para exportação CSV com caracteres especiais — verifica que vírgulas em nomes de estudantes e atividades são corretamente escapadas (RFC 4180, `str_getcsv`).

| Cenário | O que verifica |
|---------|----------------|
| `csv_borda - nome de estudante com virgula e exportado como campo entre aspas` | **Borda:** lastname `"da Silva, Jr."` → `str_getcsv` reconhece como campo único; lookup de linha por nome com vírgula funciona |
| `csv_borda - nome de atividade com virgula no cabecalho do CSV` | **Borda:** atividade `"Atividade, com vírgula"` → cabeçalho contém o nome; lookup por coluna funciona |

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

## Infraestrutura Behat — correções aplicadas (maio/2026)

Para que o Behat rode no stack Moodle 3.0.5 + PHP 5.6 com chromedriver moderno, o `run_behat.sh` aplica três correções de ambiente e existe um patch obrigatório em `vendor/`:

1. **Imagem Selenium pinned em `3.141.59-selenium`** (Chrome 75 + chromedriver 75). O script detecta mismatch contra a imagem do container existente e recria via `docker rm -f` automaticamente.
2. **`/etc/hosts` no container Moodle** mapeia `$URL_NAME` → `127.0.0.1`. Sem isso, o DNS do container resolve pelo DNS externo da UFSC (192.168.0.1), pega a produção e recebe 301 → HTTPS, quebrando o probe HTTP do Behat.
3. **`/etc/hosts` no container Selenium** mapeia `$URL_NAME` → IP do container Moodle na rede docker — para o Chrome alcançar o site behat local em vez do externo.
4. **Patch em `vendor/behat/mink-selenium2-driver/src/Behat/Mink/Driver/Selenium2Driver.php`** força `chromeOptions.w3c=false` quando o browser é chrome. Sem esse patch, chromedriver ≥ 75 negocia dialeto W3C com Selenium e devolve elementos com chave `element-6066-11e4-a52e-4f735466cecf`. O Mink antigo da Behat 2.x só conhece a chave OSS `ELEMENT`, então `find()` retorna vazio e o primeiro `before_scenario` morre com `"is not a behat test site"` mesmo com a página renderizando corretamente.

**Atenção ao composer:** se composer rodar e regenerar `vendor/`, o patch é perdido. Re-aplicar.
