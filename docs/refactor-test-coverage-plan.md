# Cobertura de testes antes da refatoração estrutural — `report_unasus`

## Context

O usuário planeja uma refatoração estrutural dos relatórios — em particular, alvos pendentes documentados em [NOTES.md](../../moodle-report-unasus/NOTES.md):

- `relatorios/loops.php:32-289` (O(n³) nested loops — CRITICAL pending)
- `relatorios/loops.php:332-337` (recursão double-pass — HIGH pending)
- `relatorios/queries.php:72-85` (SELECT DISTINCT redundante — MEDIUM pending)
- `relatorios/queries.php:720-810` (5 queries duplicadas por tipo de atividade — MEDIUM pending)

Recentemente foi expandida a cobertura: 22 testes PHPUnit em `tests/unasus_datastructures_test.php` (focados em `report_unasus_data` base + 3 subclasses), 90 cenários Behat em 20 features cobrindo 14 dos 15 relatórios. Apesar disso, a análise profunda revelou 13 gaps reais — alguns já anotados em NOTES.md (REVIEW_10136), outros encontrados nesta análise. A meta é: **antes de iniciar a refatoração, fechar todos os gaps que possam permitir uma regressão silenciosa**.

Escolhas do usuário: plano completo, **um commit por fase** (8 commits totais); `potenciais_evasoes` apenas documentado como morto; ordem **PHPUnit primeiro, Behat por último**.

---

## Lacunas mapeadas (13 gaps, ordem de execução)

| # | Gap | Onde | Tipo | Fase |
|---|---|---|---|---|
| 1 | Subclasses `report_unasus_activity` sem teste (assign, db, scorm, quiz, lti, lti_tcc, chapter_tcc) | `activities_datastructures.php:147-441` | PHPUnit | 1 |
| 2 | Subclasses `report_unasus_data` sem teste (db, scorm, lti, lti_tcc, nota_final, empty) | `activities_datastructures.php:835-1076` | PHPUnit | 2 |
| 3 | Render classes (8 estados de `atividades_vs_notas_render`, 6 de `entrega_de_atividades_render`, 5 de `historico_atribuicao_notas`, etc.) | `datastructures.php:122-1149` | PHPUnit | 5 |
| 4 | Grade `-1` cast string em todas data subclasses (só `data_activity` testa) | NOTES.md REVIEW_10136 | PHPUnit | 1 + 2 |
| 5 | Factory: `set_relatorio`, `set_modo_exibicao`, `date_interval_is_valid`, `agrupar_relatorios` switch | `factory.php` | PHPUnit + Behat | 4 + 8 |
| 6 | Loop branches: `!$enrol`, `!is_member_of(grouping)`, recursão `is_null($loop)` | `relatorios/loops.php` | Behat | 6 |
| 7 | Memoization invariants: `SistemaTccClient::get_tcc_definition`, `report_unasus_get_lti_type_config`, `report_unasus_get_tcc_definition` | NOTES.md REVIEW_10136 | PHPUnit | 3 |
| 8 | Synthesis queries (`query_database_synthesis_from_users`, `query_lti_synthesis_from_users`) — LEFT JOIN inclui aluno sem nota | `relatorios/queries.php` | Behat | 7 |
| 9 | SCORM | excluído de Behat por bug iconv | PHPUnit (Fase 2.2) cobre data class | — |
| 10 | `potenciais_evasoes` comentado em `lib.php:29` | Documentação NOTES.md | Doc | 8 |
| 11 | Capabilities combinadas (`view_tutoria` + `view_orientacao`) | `index.php` + `lib.php` | Behat | 8 |
| 12 | Hook `needs_lti_synthesis_fetch()` (default false; override true em `atividades_nota_atribuida`) | `factory.php` + `relatorios/queries.php` | Behat | 7 |
| 13 | `modo_exibicao` inválido via URL bloqueado | `index.php` | Behat | 8 |

---

## Critical files

**Estendidos:**
- `tests/unasus_datastructures_test.php` (Fases 1, 2)
- `tests/behat/behat_unasus.php` (Fase 6 — novos steps)
- `tests/behat/unasus_permissions.feature` (Fase 8)
- `NOTES.md`, `TESTS.md` (Fase 8 — atualização)

**Novos:**
- `tests/unasus_memoization_test.php` (Fase 3)
- `tests/unasus_factory_helpers_test.php` (Fase 4)
- `tests/unasus_render_test.php` (Fase 5)
- `tests/behat/unasus_branches_loops.feature` (Fase 6)
- `tests/behat/unasus_synthesis_queries.feature` (Fase 7)
- `tests/behat/unasus_factory_filters.feature` (Fase 8)

**Não alterados (subjects under test):**
- `activities_datastructures.php` (1076 LoC; 9 activity subclasses, 8 data subclasses)
- `datastructures.php` (1199 LoC; ~20 render classes)
- `factory.php` (401 LoC)
- `relatorios/loops.php` (620 LoC), `relatorios/queries.php` (1349 LoC)
- `sistematcc.php`, `locallib.php`

**Utilidades existentes a reutilizar:**
- `$this->getMockForAbstractClass('report_unasus_activity', array($has_submission, $has_grade))` — Fases 1, 2, 4, 5
- `report_unasus_SistemaTccClient::set_mock_responses($responses)` — Fase 3.2 (sistematcc.php:47)
- `set_config('prazo_maximo_avaliacao', N, 'report_unasus')` — Fase 5.1 (CSS class boundary)
- Steps Behat já existentes em `behat_unasus.php`: `I submit assignment`, `I post to forum`, `I set the grade`, `I mark activity as complete`, `I open the unasus report directly` — Fases 6, 7, 8
- Mapeamento arquivo → features em CLAUDE.md ("Behat Validation Policy")

---

## Plano em fases (PHPUnit primeiro)

### Fase 1 — Activity Wrappers PHPUnit (1 commit)

**Objetivo:** pinning dos construtores das 7 subclasses não cobertas.

**Mensagem de commit sugerida:** `tests: cover report_unasus_activity subclass constructors`

**Arquivo alterado:** `tests/unasus_datastructures_test.php`

**Testes a adicionar:**
- `test_report_unasus_assign_activity_constructor`, `test_assign_activity_grade_zero_disables_grade`
- `test_report_unasus_generic_activity_constructor`, `test_generic_activity_inverts_nosubmissions_flag`
- `test_report_unasus_db_activity_defaults_true_true`
- `test_report_unasus_scorm_activity_no_submission`, `test_scorm_activity_has_grade_true`
- `test_report_unasus_quiz_activity_constructor`, `test_quiz_activity_grade_zero_no_grade`, `test_quiz_activity_short_name_switch`
- `test_lti_activity2_inherits_generic`, `test_lti_activity_tcc2_carries_tcc_definition`
- `test_chapter_tcc_activity_no_submission_no_grade`, `test_chapter_tcc_activity_toString_label`

**Verificação antes do commit:** `./run_tests.sh tests/unasus_datastructures_test.php`

### Fase 2 — Data Subclasses PHPUnit (1 commit)

**Objetivo:** cobrir as 6 subclasses de `report_unasus_data` ainda sem teste; adicionar gap 4 (string `"-1"`) em todas.

**Mensagem de commit sugerida:** `tests: cover report_unasus_data subclasses and string "-1" grade sentinel`

**Arquivo alterado:** `tests/unasus_datastructures_test.php`

**Testes a adicionar:**
- **data_db:** `test_report_unasus_data_db`, `test_data_db_grade_minus_one_string`
- **data_scorm:** `test_report_unasus_data_scorm_completion`, `test_data_scorm_partial_grade_no_completion`, `test_data_scorm_grade_minus_one_string`
- **data_lti + string `"-1"` em data_quiz/data_forum:** `test_report_unasus_data_lti`, `test_data_lti_grade_minus_one_string`, `test_data_quiz_grade_minus_one_string`, `test_data_forum_grade_minus_one_string`
- **data_lti_tcc (chapter status array):** `test_data_lti_tcc_has_submitted_with_chapters`, `test_data_lti_tcc_has_evaluated_chapters_only_when_done`, `test_data_lti_tcc_toString_resume`
- **data_nota_final:** `test_report_unasus_data_nota_final`, `test_data_nota_final_grade_minus_one_string`, `test_data_nota_final_null_grade`
- **data_empty:** `test_report_unasus_data_empty_all_predicates_false` (has_submitted, has_grade, is_grade_needed, is_submission_due, is_a_future_due, is_activity_pending, submission_due_days, grade_due_days todos `false`)

**Verificação antes do commit:** `./run_tests.sh tests/unasus_datastructures_test.php`

### Fase 3 — Memoization Invariants PHPUnit (1 commit)

**Objetivo:** pinning das caches estáticas adicionadas em REVIEW_10136. Garante que refator não invalide invariância.

**Mensagem de commit sugerida:** `tests: pin TCC definition + SistemaTccClient memoization invariants`

**Arquivos alterados:**
- `tests/unasus_memoization_test.php` (NEW; header `defined('MOODLE_INTERNAL') || die();` + `require_once($CFG->dirroot . '/report/unasus/sistematcc.php')`)
- `NOTES.md` (nota sobre cobertura indireta da cache `lti_type_config`)

**Testes a adicionar:**
- **TCC definition cache (locallib):** `test_get_tcc_definition_memoizes_by_input_string`, `test_get_tcc_definition_distinct_inputs_distinct_results`, `test_get_tcc_definition_handles_malformed_input`
- **SistemaTccClient cache:** `test_sistema_tcc_client_caches_get_tcc_definition` (usa `set_mock_responses()` + spy contador via subclasse anônima); `test_sistema_tcc_client_distinct_keys_distinct_calls`
- **LTI type config cache:** documentação em NOTES.md — cache de `report_unasus_get_lti_type_config()` exige DB; cobertura indireta por `unasus_tcc.feature` é aceita

**Verificação antes do commit:** `./run_tests.sh tests/unasus_memoization_test.php`

### Fase 4 — Factory Helpers PHPUnit (1 commit)

**Objetivo:** funções puras de validação e aritmética de datas.

**Mensagem de commit sugerida:** `tests: cover date_interval_is_valid and acesso_tutor interval boundaries`

**Arquivo alterado:** `tests/unasus_factory_helpers_test.php` (NEW)

**Testes a adicionar:**
- **`report_unasus_date_interval_is_valid`:** `test_date_interval_normal_range_valid`, `test_date_interval_inverse_range_invalid`, `test_date_interval_invalid_format_returns_false`, `test_date_interval_empty_returns_false`, `test_date_interval_same_day_valid`
- **`report_acesso_tutor::get_interval_boundaries` (REVIEW_10136 date edge cases):** `test_interval_boundaries_adds_one_day_to_data_fim`, `test_interval_boundaries_dst_transition`, `test_interval_boundaries_cross_year`, `test_get_days_interval_same_day`

**Verificação antes do commit:** `./run_tests.sh tests/unasus_factory_helpers_test.php`

### Fase 5 — Render Classes PHPUnit (1 commit)

**Objetivo:** estados internos das render classes (Behat só valida o caso feliz). Render classes são puras (sem DB).

**Mensagem de commit sugerida:** `tests: cover render classes state transitions and CSS class boundaries`

**Arquivo alterado:** `tests/unasus_render_test.php` (NEW)

**Testes a adicionar:**
- **`atividades_vs_notas_render` (8 estados):** 8 testes parametrizados — uma const por teste; verifica `__toString()` + `get_css_class()` esperados; inclui borda `CORRECAO_ATRASADA` × `prazo_maximo_avaliacao` (CSS muda entre `pouco_atraso` e `muito_atraso`) — usa `set_config('prazo_maximo_avaliacao', N, 'report_unasus')`
- **`boletim_render` + `nota_final_render` (3 estados cada):** `test_boletim_render_com_nota_acima_media`, `test_boletim_render_com_nota_abaixo_media`, `test_boletim_render_sem_nota`, `test_boletim_render_nao_aplicado` (idem para nota_final_render)
- **`entrega_de_atividades_render` (6 estados):** 6 testes: cada const + bordas no `prazo_maximo_entrega`
- **`tcc_entrega_atividades_render` + `tcc_concluido_render`:** 4 testes para cada const TCC entrega; 2 testes TCC concluído + render `Hoje` vs N dias
- **`historico_atribuicao_notas_render` + `modulos_concluidos_render` + `potenciais_evasoes_render`:** 5 estados de historico (CORRECAO_NO_PRAZO etc.); 3 estados de modulos_concluidos; transições internas de potenciais_evasoes (`add_atividade_nao_realizada`)

**Verificação antes do commit:** `./run_tests.sh tests/unasus_render_test.php`

### Fase 6 — Loop Branches via Behat (1 commit)

**Objetivo:** cobrir os 4 branches críticos de `loops.php` que a refatoração vai alterar. Exige fixtures relacionais (aluno não-enrolado, fora de grouping) → Behat necessário.

**Mensagem de commit sugerida:** `tests: behat coverage for loops.php enrol + grouping + recursion branches`

**Arquivos alterados:**
- `tests/behat/behat_unasus.php` (novos steps)
- `tests/behat/unasus_branches_loops.feature` (NEW)

**Conteúdo:**
- **Steps novos em `behat_unasus.php`:**
    - `@Given /^I unenrol user "([^"]*)" from course "([^"]*)"$/` (manipula `user_enrolments` via `enrol_user` reverse)
    - `@Given /^I create grouping "([^"]*)" with members:/` (Gherkin table)
    - `@Given /^I assign activity "([^"]*)" to grouping "([^"]*)"$/`
- **Cenários em `unasus_branches_loops.feature`:**
    - **Branches `!$enrol`:** aluno-nao-enrolado-vê-células-vazias em `atividades_vs_notas` (assert CSS class `nao_aplicado` para todas as atividades dele)
    - **Branch `!is_member_of` (grouping):** aluno-enrolado-fora-do-grouping-vê-células-vazias — mínimo 1 cenário por tipo (assign/forum/quiz/data/lti)
    - **Recursão `is_null($loop)`:** `recursao-nota-atribuida-conta-modulos` — validar contagem correta na coluna "modulo_X" do relatório `atividades_nota_atribuida` (protege chamada recursiva linha 333)

**Verificação antes do commit:** `./run_behat.sh tests/behat/unasus_sem_dados.feature && ./run_behat.sh tests/behat/unasus_permissions.feature && ./run_behat.sh tests/behat/unasus_branches_loops.feature`

### Fase 7 — Synthesis Queries Behat (1 commit)

**Objetivo:** documentar via fixture o contrato `LEFT JOIN includes students without grade` antes de consolidar as 5 queries duplicadas em queries.php:720-810.

**Mensagem de commit sugerida:** `tests: behat coverage for synthesis queries LEFT JOIN contract`

**Arquivos alterados:**
- `tests/behat/behat_unasus.php` (novo step se necessário)
- `tests/behat/unasus_synthesis_queries.feature` (NEW)

**Conteúdo:**
- **Step novo (se Moodle generator não cobrir):** `@Given /^I create database activity "([^"]*)" in course "([^"]*)"$/`
- **Cenários:**
    - `synthesis-database-includes-students-without-grade`: 3 estudantes em data activity; só 2 com grade. Abrir `atividades_nota_atribuida`, verificar que estudante 3 aparece como linha (não excluído) — protege `query_database_synthesis_from_users`
    - `synthesis-lti-portfolio-includes-students-without-grade` — só se fixture LTI for viável; do contrário registrar como tradeoff em NOTES.md. Cobre branch `loops.php:531` + hook `needs_lti_synthesis_fetch` em `factory.php`

**Verificação antes do commit:** `./run_behat.sh tests/behat/unasus_sem_dados.feature && ./run_behat.sh tests/behat/unasus_permissions.feature && ./run_behat.sh tests/behat/unasus_synthesis_queries.feature`

### Fase 8 — Cross-cutting & Docs (1 commit)

**Objetivo:** capabilities combinadas, validação de URL, agrupamentos, documentação.

**Mensagem de commit sugerida:** `tests: cover combined capabilities, agrupar_relatorios switch and document exclusions`

**Arquivos alterados:**
- `tests/behat/unasus_permissions.feature` (estender)
- `tests/behat/unasus_factory_filters.feature` (NEW)
- `NOTES.md`
- `TESTS.md`

**Conteúdo:**
- **Capabilities combinadas** em `unasus_permissions.feature`: cenário `usuario-com-tutoria-e-orientacao-ve-12-relatorios` — usuário com ambas capabilities navega menu com 9+3 entradas
- **`modo_exibicao` inválido** em `unasus_permissions.feature`: cenário `modo_exibicao-invalido-via-url-bloqueado` — acesso direto com `&modo_exibicao=foo` retorna `print_error`
- **`agrupar_relatorios` switch (4 valores)** em `unasus_factory_filters.feature`: 3 cenários — `agrupar-por-cohorts-mostra-cohorts-como-linhas`, `agrupar-por-polos`, `agrupar-por-orientadores` (default `tutores` já coberto)
- **Documentação de exclusões** em `NOTES.md`: nova seção "Excluded from Coverage" — SCORM Behat (iconv bug), `potenciais_evasoes` (comentado em lib.php:29 — confirmado morto: arquivo `report_potenciais_evasoes.php` existe mas `set_relatorio()` rejeita pois nenhuma das 3 listas o inclui), `report_unasus_activity_config` family (DTOs sem lógica)
- **Atualizar `TESTS.md`:** refletir todos os testes/cenários adicionados nas Fases 1-7; atualizar contagens (era 22 PHPUnit + 90 Behat → ~80 PHPUnit + ~100 Behat)

**Verificação antes do commit:** `./run_behat.sh tests/behat/unasus_sem_dados.feature && ./run_behat.sh tests/behat/unasus_permissions.feature && ./run_behat.sh tests/behat/unasus_factory_filters.feature`

---

## Riscos e tradeoffs

- **Fase 3.2 (SistemaTccClient cache):** `post()` é privado. Opções: (a) Reflection no teste (frágil); (b) tornar `protected` (alteração mínima em produção, baixo risco). Decidir no momento da implementação.
- **Fase 6 (steps unenrol/grouping):** manipulação direta da tabela `user_enrolments` em Behat exige cuidado para não romper o estado entre cenários — usar `MoodleQuickForm`/`enrol_meta_handler` quando possível.
- **Fase 7.3 (synthesis LTI):** se fixture LTI Portfolio não for viável neste ambiente Behat, documentar como gap aceito em NOTES.md (a alteração em `query_lti_synthesis_from_users` ainda fica coberta por `unasus_atividades_nota_atribuida.feature` indiretamente).
- **SCORM Behat:** mantém-se excluído (iconv bug em `mod/scorm`). Cobertura PHPUnit em Fase 2.2 é a rede para este tipo.
- **Smoke set obrigatório:** após cada fase, rodar `./run_behat.sh tests/behat/unasus_sem_dados.feature && ./run_behat.sh tests/behat/unasus_permissions.feature` antes de avançar (regra do CLAUDE.md).

---

## Verificação end-to-end

**Antes da Fase 1:** rodar baseline completo e anotar tempos.
```bash
./run_tests.sh                                          # PHPUnit existentes
for f in tests/behat/*.feature; do ./run_behat.sh "$f"; done   # ~16min
```

**Durante cada fase PHPUnit (1-5):**
```bash
./run_tests.sh tests/unasus_datastructures_test.php     # Fases 1, 2
./run_tests.sh tests/unasus_memoization_test.php        # Fase 3
./run_tests.sh tests/unasus_factory_helpers_test.php    # Fase 4
./run_tests.sh tests/unasus_render_test.php             # Fase 5
```

**Durante cada fase Behat (6-8):**
```bash
./run_behat.sh tests/behat/unasus_sem_dados.feature       # smoke
./run_behat.sh tests/behat/unasus_permissions.feature     # smoke
./run_behat.sh tests/behat/<nova-feature>.feature
```

**Antes de iniciar a refatoração estrutural:** todos os testes verdes; gravar contagem `$DB->perf_get_queries()` em uma página representativa de cada relatório como baseline de performance.

**Após cada etapa da refatoração:** rerun da suite afetada conforme mapeamento `CLAUDE.md` ("Behat Validation Policy" — ex.: `loops.php` → full suite minus `acesso_tutor`/`uso_sistema_tutor`); zero regressão funcional + redução de tempo Behat.

---

## Sumário de esforço

| Fase | Tipo | Commits | LoC ~ | Tempo execução add. |
|---|---|---|---|---|
| 1 | Activity wrappers PHPUnit | 1 | 215 | <1s |
| 2 | Data subclasses PHPUnit | 1 | 280 | <2s |
| 3 | Memoization PHPUnit | 1 | 125 | <1s |
| 4 | Factory helpers PHPUnit | 1 | 130 | <1s |
| 5 | Render classes PHPUnit | 1 | 330 | <2s |
| 6 | Loop branches Behat | 1 | 200 | ~3min |
| 7 | Synthesis queries Behat | 1 | 160 | ~2min |
| 8 | Cross-cutting + Docs | 1 | 150 | ~1min |
| **Total** | — | **8** | **~1590** | **+~6min Behat / +~6s PHPUnit** |
