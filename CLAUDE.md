# CLAUDE.md

Guidance for Claude Code when working in this repository. For deeper context (performance bottlenecks catalog, change archaeology, Behat infra debugging history) see [NOTES.md](NOTES.md). For the full Behat/PHPUnit scenario reference see [TESTS.md](TESTS.md).

## Project Overview

**report_unasus** is a Moodle plugin that generates managerial reports of student progress in the UNA-SUS (Universidade Aberta do SUS) educational system. Three user roles:

- **Tutores**: track their own students' progress
- **Orientadores (Advisors)**: monitor thesis (TCC) work and advisees
- **Coordenadores**: see all tutors, advisors, and students

Supported activities: Assignment, Database, Forum, LTI, Quiz, SCORM.

## Architecture

### Core patterns

1. **Factory Singleton** (`factory.php`): centralizes report parameters, filters, and display settings; accessed globally as `report_unasus_factory::singleton()`. The singleton stores the **subclass instance** of the selected report (relevant for `is_a()`-style polymorphism in `loops.php`).
2. **Report modularity** (`/reports/`): each report extends a base class and is instantiated by the factory.

### Request flow

```
index.php (route + permission check)
  ↓
factory.php (parse GET/POST → instantiate report subclass)
  ↓
renderer.php (filters, initial page, role scoping via apply_role_scope())
  ↓
relatorios/relatorios.php (table/graph + queries via relatorios/queries.php + relatorios/loops.php)
```

### Key classes

- `report_unasus_person` / `report_unasus_student` — user data models (`datastructures.php`)
- `report_unasus_activity` — abstract activity wrapper with submission/grade tracking
- `report_unasus_data*` — per-activity-type data wrappers in `activities_datastructures.php` (`is_submission_due()`, `is_grade_needed()`, etc.). Grade `-1` (int or string) is the Moodle "no grade" sentinel and is filtered out across all subclasses.
- `report_unasus_factory` — singleton config (`factory.php`)

### Reports

**For tutors**: `estudante_sem_atividade_avaliada`, `estudante_sem_atividade_postada`, `modulos_concluidos`, `avaliacoes_em_atraso`, `atividades_nota_atribuida`, `atividades_concluidas_agrupadas`, `entrega_de_atividades`, `atividades_vs_notas`, `boletim`.

**For advisors**: `tcc_consolidado`, `tcc_entrega_atividades`, `tcc_concluido`.

**Coordinator-only**: `acesso_tutor`, `uso_sistema_tutor`.

## Plugin Dependencies

- **Required**: `local_tutores`, `local_relationship`
- **Optional**: `enrol/relationship`, `local/report_config`

## Key Files by Responsibility

- **Routing**: `index.php` (entry, permission, display routing)
- **Config**: `lib.php` (valid reports, capabilities, navigation), `settings.php`, `version.php`
- **Core logic**: `locallib.php` (data retrieval), `factory.php`, `datastructures.php`, `activities_datastructures.php`
- **Presentation**: `renderer.php`, `relatorios/queries.php`, `relatorios/loops.php`
- **UI assets**: `styles.css`, `module.js`, `/img/`
- **External**: `sistematcc.php` (TCC web service client; supports per-process mock via `set_mock_responses()` and per-process cache for `behat_tcc_mock_*` config keys)

## Important Notes

### Capability-based access

Three capabilities, checked via `lib.php` (`report_unasus_relatorios_validos_*_list()`):

- `report/unasus:view_all` — coordinator (all data)
- `report/unasus:view_tutoria` — tutor (own students)
- `report/unasus:view_orientacao` — advisor (own orientees)

A user holding both `view_tutoria` and `view_orientacao` (without `view_all`) gets both filter scopes applied.

### Report parameter flow

User submits filters via `index.php` → factory parses GET/POST → renderer reads from factory → on submit, `modo_exibicao` is one of `tabela`, `grafico_valores`, `grafico_porcentagens`, `grafico_pontos`, `export_csv`.

### Database access

Use Moodle's `$DB` API (no raw SQL). Queries live in `relatorios/queries.php`; aggregation in `relatorios/loops.php`. Performance bottlenecks catalog and existing per-request caches are documented in [NOTES.md](NOTES.md).

## Running Tests

### Initial setup

```bash
cp .env.template .env
# Edit .env:
#   CORE_NAME=unasuscp
#   DOCKER_VERSION=php56-nginx
#   URL_NAME=local-unasuscp.moodle.ufsc.br
```

### PHPUnit (`tests/unasus_datastructures_test.php` — data classes only)

```bash
./run_tests.sh                                  # all *_test.php files
./run_tests.sh tests/unasus_datastructures_test.php
./run_tests.sh --reset                          # forces util.php --drop and fresh init
```

Notes: requires `.env`; PHPUnit init reused unless first run / missing `phpunit.xml` / build marker changed; tests are run file-by-file to dodge a PHPUnit 4.8 discovery bug.

Coverage scope: `has_deadline`, `has_submission()`, `has_grouping()`, invalid constructor arguments, `is_submission_due()` (incl. historical), `is_grade_needed()`/`grade_due_days()`, has-submitted/has-grade on base, `is_activity_pending()`, `is_a_future_due()`, draft/submitted/new statuses, grade `-1` (int+string), forum/quiz data, `is_member_of()`. SQL queries, role-scoping, table render, CSV export, TCC integration and capability scoping rely on Behat — see below.

### Behat (acceptance)

```bash
./run_behat.sh                          # full @report_unasus suite
./run_behat.sh tests/behat/unasus_X.feature
./run_behat.sh --tags="@critical"
./stop_behat.sh                         # stop containers
./stop_behat.sh --down                  # stop and remove
```

Notes:
- Reads both `.env` (plugin) and `../../../../.env` (root Moodle).
- Manages Selenium container; pinned to `selenium/standalone-chrome:3.141.59-selenium` (Chrome 75 + chromedriver 75) — auto-recreates on image mismatch. Adds `/etc/hosts` for `$URL_NAME` inside both containers.
- Requires user in the `docker` group (`sudo usermod -aG docker $USER` + new shell). No `sudo` calls in the scripts.
- **Requires patch in `vendor/behat/mink-selenium2-driver/.../Selenium2Driver.php::setDesiredCapabilities()`** to force OSS WebDriver dialect (`chromeOptions.w3c=false`) — without this, the entire stack returns null elements. Re-apply if Composer ever rewrites `vendor/`. See "Etapa 3" in [NOTES.md](NOTES.md) for full diagnosis history.
- Full reference of feature files, scenarios, and known limitations: [TESTS.md](TESTS.md).

## Behat Validation Policy

**Rule**: any change to a report (`/reports/report_*.php`) or to a function/file that impacts reports must be validated by Behat before declaring the task complete. PHPUnit alone is not sufficient.

**Procedure** (blocking — wait for exit code):

1. Smoke set first (always):
   ```bash
   ./run_behat.sh tests/behat/unasus_sem_dados.feature
   ./run_behat.sh tests/behat/unasus_permissions.feature
   ```
2. If smoke passes, run features per the mapping below.
3. **On failure**: stop, report the failing scenario + error excerpt, wait for instruction. Do not attempt fixes by initiative.

**File → features mapping**:

| Changed file | Features to run (in addition to smoke) |
|---|---|
| `reports/report_X.php` | `tests/behat/unasus_X.feature` |
| `relatorios/queries.php` | full suite (`./run_behat.sh`) |
| `relatorios/loops.php` | full suite except `acesso_tutor`/`uso_sistema_tutor` |
| `datastructures.php` | full suite |
| `activities_datastructures.php` | full suite except `acesso_tutor`/`uso_sistema_tutor` |
| `factory.php` | full suite |
| `renderer.php` | full suite |
| `locallib.php` | full suite |
| `sistematcc.php` | TCC features only (`unasus_tcc.feature`, `unasus_filtro_cohort_tcc.feature`, `unasus_manager_tcc.feature`) |
| `lib.php`, `version.php`, `settings.php` | smoke set only |
| Test/infra (`run_*.sh`, `tests/behat/behat_unasus.php`, `.env*`) | nothing |
| Disposable scripts (e.g. one-off benchmark/migration files) | nothing |

For "full suite", prefer `./run_behat.sh` without filters (default tag `@report_unasus`) — cheaper than enqueueing individual features.

## Docker Environment

Repository (`www/local-unasuscp`) is mounted by container **`moodle-$CORE_NAME`** (derived from `.env`). Default with `.env.template`:

```bash
CONTAINER_NAME="moodle-local-unasuscp"
DOCKER_COMPOSE_DIR="/home/$USER/workspace/docker/php56-nginx"
MOODLE_LOCAL_SITE="www/local-unasuscp"
```

**Variables** (from `.env`): `CORE_NAME` (system identifier), `DOCKER_VERSION` (e.g. `php56-nginx`), `URL_NAME` (Behat URL), `$USER` (replaces hardcoded paths).

A separate container `moodle-local-report-unasuscp` mounts `www/report-unasuscp` (a worktree on `master`) — do not confuse the two.

**Composer / legacy stack**: root Moodle `composer.json` should use `phpunit/dbunit` (lowercase). Composer 1 deprecation notices in init output are expected — legacy Moodle constraint.

## Moodle Plugin Standards

- Plugin type: `report` (path `/report/unasus/`); component: `report_unasus`
- Lang strings: `lang/en/report_unasus.php`
- Version tracked in `version.php` (format `YYYYMMDDXX`)
- Requires Moodle 2.8.6+ and the `local_tutores`/`local_relationship` versions pinned in `version.php`
