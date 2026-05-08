# NOTES.md

Background context for `report_unasus` not needed for routine work, but worth keeping out of git history's reach. Linked from `CLAUDE.md`.

## Known Performance Bottlenecks

Identified during analysis (April 2026). Items marked ✅ have been fixed; remaining items are documented so they are not re-discovered.

| Location | Pattern | Severity | Status |
|----------|---------|----------|--------|
| `queries.php:63-67` | Correlated subquery inside JOIN for `user_info_field`/`user_info_data` | HIGH | ✅ Fixed — replaced with explicit `LEFT JOIN {user_info_field} uif` + `LEFT JOIN {user_info_data} uid` in both `query_alunos_relationship()` and `query_alunos_relationship_student()` |
| `queries.php:72-85` | Redundant `SELECT DISTINCT` outer wrapper; can use inner `GROUP BY` | MEDIUM | Pending |
| `queries.php:720-810` | Five near-identical query functions duplicated per activity type | MEDIUM | Pending |
| `loops.php:32-289` | O(n³) nested loops (groups × courses × activities × students) with object construction at innermost level | CRITICAL | Pending |
| `loops.php:332-337` | Recursive double-pass over full dataset to build `atividades_alunos_grupos` | HIGH | Pending |
| `locallib.php:316` | One WebService call per LTI activity — no batching | HIGH | ✅ Fixed — `SistemaTccClient::get_tcc_definition()` now memoizes by `url|consumer_key|tcc_definition_id` |
| `locallib.php:779-809` | N+1 pattern: separate config query per LTI | HIGH | ✅ Fixed — new `report_unasus_get_lti_type_config($typeid)` with `static $cache` eliminates duplicate queries per `typeid` |
| `locallib.php:895-906` | `report_unasus_get_tcc_definition()` parses string on every call; no memoization | LOW | ✅ Fixed — added `static $cache` keyed by input string |
| `queries.php:1023` (LtiPortfolioQuery) | Instance recreated per loop iteration in `loops.php:23`; cache is lost each time | MEDIUM | N/A — `new LtiPortfolioQuery()` is already outside the loop; no change needed |

**Existing caches:** Factory singleton (`factory.php`), LtiPortfolioQuery instance-level cache by `grupo_tutoria`, `sistematcc.php` static variable for mock detection, `report_unasus_get_lti_type_config()` static cache by `typeid`, `report_unasus_get_tcc_definition()` static cache by input, `SistemaTccClient::get_tcc_definition()` static cache by `url|key|id`. All are single-request scope only.

## Pending PHPUnit Coverage Gaps

Identified by REVIEW_10136 (May 2026). None of the items below correspond to a bug — the production code paths are validated end-to-end by Behat. They are pure hardening: pinning invariants in fast unit tests so future refactors fail loudly instead of regressing silently.

A maior parte dos gaps foi fechada pelo plano de cobertura pré-refatoração (Fases 1–7, May 2026 — ver `docs/refactor-test-coverage-plan.md`). PHPUnit cobre agora 4 arquivos: `unasus_datastructures_test.php`, `unasus_memoization_test.php`, `unasus_factory_helpers_test.php` e `unasus_render_test.php` (~80 testes / ~250 asserções).

| Target | What to assert | Notes |
|---|---|---|
| `datastructures.php:790-807` — `report_unasus_person::user_activity_completion()` | Lookup by `coursemoduleid` returns the right row when populated; returns falsy when missing | Requires DB fixture. Verifies the JOIN→`get_field` migration that landed with the `coursemoduleid` work |
| `report_atividades_nota_atribuida::get_completion_states_by_user()` / `is_activity_complete_for_user()` | Sentinel completion states (`COMPLETION_COMPLETE`, `COMPLETION_INCOMPLETE`) drive the boolean correctly; the IN-list filter from the blocker fix returns only requested cmids | Gates the entire synthesis report; today only covered indirectly via Behat |
| `locallib.php` — `report_unasus_get_lti_type_config($typeid)` | Second call with the same `$typeid` does **not** issue another DB query (assert via `$DB` mock or call counter) | Direct PHPUnit cobertura exige DB fixture e mock de `$DB`; aceita-se cobertura indireta via `unasus_tcc.feature` (memoization invariant) |
| `locallib.php` — `report_unasus_get_tcc_definition($input)` | Second call with the same input string returns same parsed object; cache keyed by input | ✅ Coberto em `tests/unasus_memoization_test.php` (Fase 3) |
| `sistematcc.php` — `SistemaTccClient::get_tcc_definition($id)` | Second call with same `(url, consumer_key, id)` triple returns cached object without invoking `post()` | ✅ Coberto em `tests/unasus_memoization_test.php` (Fase 3) |
| `sistematcc.php` — `SistemaTccClient::post()` mock plumbing | `behat_tcc_mock_*` config lookup is read at most once per `$config_key` per process (achado #7 da review) | Memoization invariant |
| `relatorios/queries.php` — `query_database_synthesis_from_users()` / `query_lti_synthesis_from_users()` | Returns every student row of the tutoria group with grade-or-null (vs. INNER-JOIN sibling that filters); ordering stable | ✅ Coberto via Behat em `tests/behat/unasus_synthesis_queries.feature` (Fase 7) — denominador "1/12" prova LEFT JOIN |
| `reports/report_acesso_tutor.php` — `get_interval_boundaries()` / `get_days_interval()` | Pure date arithmetic edge cases (DST, month boundaries, same-day, cross-year) | ✅ Coberto em `tests/unasus_factory_helpers_test.php` (Fase 4) |
| `activities_datastructures.php` — grade `-1` (string `"-1"`) | The cast-to-int filter applied across all activity classes — currently only `report_unasus_data_activity` has the string-form test (`test_report_unasus_data_activity_grade_minus_one_as_string`); the same change in forum, quiz, db, scorm, lti, nota_final remains uncovered | ✅ Coberto em `tests/unasus_datastructures_test.php` (Fase 2) — `_grade_minus_one_string` por subclasse |

The gaps are documented here (rather than in the closed `REVIEW_10136.md`) so they remain discoverable when planning the next hardening pass. Each item is independently picked up — no ordering dependency.

## Excluded from Coverage

Itens deliberadamente fora do escopo de testes automatizados — documentados aqui para evitar redescoberta:

- **SCORM Behat:** excluído por bug de iconv em `mod/scorm` no ambiente Moodle 3.0.5 que impede criar a atividade via fixture. Cobertura PHPUnit das estruturas (`report_unasus_scorm_activity`, `report_unasus_data_scorm`) supre o gap em `tests/unasus_datastructures_test.php`.
- **`potenciais_evasoes`:** comentado em `lib.php:29`. O arquivo `reports/report_potenciais_evasoes.php` ainda existe mas `report_unasus_factory::set_relatorio()` rejeita a entrada (nenhuma das 3 listas `report_unasus_relatorios_validos_*_list()` o inclui). Tratado como código morto até nova decisão de produto.
- **`report_unasus_activity_config` family** (`activities_datastructures.php:77-145`): DTOs sem lógica observável — apenas atribuem campos do `db_model`. Pinar via PHPUnit não acrescenta valor de regressão.
- **`!is_member_of(grouping)` branch em `loops.php`:** descopo da Fase 6 — exigiria fixtures de `groupings` + `grouping groups` (Moodle generators ou steps customizados). Pode ser retomado pós-refatoração se houver regressão nesse caminho.
- **`recursão is_null($loop)` em `loops.php:332-337`:** coberto indiretamente por `tests/behat/unasus_atividades_nota_atribuida.feature` — qualquer cenário que abre o relatório `atividades_nota_atribuida` ou `atividades_concluidas_agrupadas` exercita a chamada recursiva.
- **`agrupar_relatorios` switch (4 valores)** e **`modo_exibicao` inválido via URL** (Fase 8 do plano): descopo desta passada — podem ser adicionados caso surjam regressões durante a refatoração.

## Follow-up TODOs (pré-refatoração — code review da branch `refactor/test-coverage-pre-refactor`)

Pendências identificadas no review que não bloqueiam o merge mas devem ser tratadas em passada futura:

- **`test_interval_boundaries_dst_transition` é fraco** (`tests/unasus_factory_helpers_test.php`): roda no timezone do servidor PHPUnit (provavelmente UTC ou America/Sao_Paulo). Em UTC não há DST, então o teste passa por `P1D` ser civil — não testa o que o nome promete. Ação: forçar timezone explicitamente no `setUp` (`date_default_timezone_set('America/Sao_Paulo')`) ou renomear para deixar claro que é apenas sanity check de calendário.
- **`docs/refactor-test-coverage-plan.md` ficará stale após a refatoração** (271 linhas servindo agora, ruído depois). Ação: remover ou arquivar quando a refatoração estrutural de `loops.php` / `queries.php` for concluída e validada.

## Recent Changes Context

Recent commits focus on report ordering, activity presentation, test coverage, documentation, bug fixes, and infrastructure improvements:

### Etapa 1 (April 2026) — Performance & Bug Fixes

- Activity reordering for synthetic reports
- Support for hidden Moodle courses in reports
- Fine-tuning report presentation order
- Expanded unit test suite from 5 to 22 tests (39 → 79 assertions), covering `is_activity_pending`, `is_a_future_due`, `is_member_of`, forum/quiz data classes, grade -1 handling, invalid constructor detection, and 6 boundary/edge-case tests
- Fixed `run_tests.sh` to discover `*_test.php` files explicitly (avoids PHPUnit 4.8 discovery bug)
- Suppressed Composer 1 deprecation noise in init output
- Fixed `phpunit/dbUnit` → `phpunit/dbunit` (lowercase) in root `composer.json`
- Consolidated Behat scenarios into 17 feature files (61 scenarios) with reusable steps
- Completed README.md: fixed incomplete sentence, added file structure, troubleshooting, and changelog sections
- Documented performance bottlenecks (see "Known Performance Bottlenecks" above)
- Performance optimizations: correlated subquery → explicit LEFT JOINs in `queries.php`; memoized `report_unasus_get_tcc_definition()` and new `report_unasus_get_lti_type_config()` in `locallib.php`; memoized `SistemaTccClient::get_tcc_definition()` in `sistematcc.php`
- **Bug fix**: Fixed grade rendering in atividades_vs_notas report — grade value -1 (Moodle's "no grade" sentinel) was being rendered as a valid grade when received as string from database. Standardized grade -1 filtering across all activity data classes by casting to int before comparison in `activities_datastructures.php`; added test case `test_report_unasus_data_activity_grade_minus_one_as_string()` to cover string "-1" scenario.

### Etapa 2 (April 2026) — Infrastructure & Configuration

- Created `.env.template` with environment variables for flexible multi-environment setup
- Updated `.gitignore` to exclude `.env` (local configurations)
- Refactored `run_tests.sh`, `run_behat.sh`, `stop_behat.sh`:
  - Added helper functions: `log()`, `warn()`, `err()` for consistent logging
  - Added `.env` file reading with validation and error handling
  - Replaced hardcoded values with environment variables (`$CORE_NAME`, `$DOCKER_VERSION`, `$USER`)
  - Made container names, directories, and URLs dynamic based on configuration
  - Added Behat dataroot permission fixes (ensures `/home/moodle/moodledata` exists and has correct ownership)
- Scripts now work across different developer environments without modification

### Etapa 3 (May 2026) — Behat Infrastructure Completion

- **Goal:** Make `./run_behat.sh` actually work end-to-end. Previously failed in `before_scenario` with "The base URL ... is not a behat test site".
- **Root cause:** Three independent issues stacked, plus one critical vendor incompatibility:
  1. Moodle container had no `/etc/hosts` entry for `$URL_NAME` — DNS resolved to UFSC production server (192.168.0.1), got HTTP 301 → HTTPS, broke `util.php` HTTP probe.
  2. `selenium/standalone-chrome:3.141.59` (latest) shipped with Chromium 94 + chromedriver 94 — only speaks W3C WebDriver protocol.
  3. Even with Chrome 75 image (`3.141.59-selenium`), chromedriver 75 still negotiates W3C dialect with Selenium 3.141 server by default.
  4. Mink Selenium2Driver bundled with Moodle 3.0.5 (Behat 2.x era) only parses OSS WebDriver responses (key `ELEMENT`); W3C responses use key `element-6066-11e4-a52e-4f735466cecf`, so all `find()` calls return null. First failure surfaces in `lib/tests/behat/behat_hooks.php:301` even though Chrome rendered the correct page.
- **Fixes applied:**
  - `run_behat.sh:53` — pinned `SELENIUM_IMAGE="selenium/standalone-chrome:3.141.59-selenium"` (Chrome 75 + chromedriver 75)
  - `run_behat.sh:200-208` — auto-recreates Selenium container via `docker rm -f` when configured image differs from existing
  - `run_behat.sh:257-261` — adds `127.0.0.1 $URL_NAME` to Moodle container's `/etc/hosts`, mirroring what was already done for Selenium container
  - **Patch in `vendor/behat/mink-selenium2-driver/src/Behat/Mink/Driver/Selenium2Driver.php::setDesiredCapabilities()`** — injects `chromeOptions.w3c=false` when browser is `chrome`, forcing chromedriver to respond in OSS dialect. **This is what actually unblocks the entire stack.** Composer rarely runs in this legacy stack, but if it does, vendor/ is regenerated and patch is lost — re-apply.
  - Removed `sudo` from all `docker` calls in `run_behat.sh`, `run_tests.sh`, `stop_behat.sh` — user must be in `docker` group (`sudo usermod -aG docker $USER` + new shell session).
- **Confirmed result:** 90 scenarios pass, 2528 steps pass, ~16 min total runtime.
- **Diagnosis-confusing symptoms** (worth knowing for future debugging):
  - `curl` from Moodle/Selenium containers returns correct title.
  - `google-chrome --headless --dump-dom` returns correct title.
  - Raw WebDriver session via curl returns correct title in JSON.
  - Only Behat/Mink fails — because it's the only component parsing the JSON element structure.
  - Confirm dialect by checking `docker logs selenium-chrome-... | grep dialect` (should show `OSS`, not `W3C`) or `POST /wd/hub/session/<sid>/elements` response (should have key `ELEMENT`, not `element-6066-...`).
