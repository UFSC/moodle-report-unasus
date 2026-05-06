# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**report_unasus** is a Moodle plugin for generating managerial reports of student progress within Moodle courses. It tracks the performance of tutors and students in the UNA-SUS (Universidade Aberta do SUS) educational system.

The plugin provides different report types and dashboards for three user roles:
- **Tutores (Tutors)**: Track their own students' progress
- **Orientadores (Advisors)**: Monitor thesis work and advisory students  
- **Coordenadores (Coordinators)**: View all tutors, advisors, and students

Supported activities: Assignment, Database, Forum, LTI, Quiz, SCORM

## Architecture

### Core Design Patterns

The plugin uses two main design patterns:

1. **Factory Singleton** (`factory.php`): Centralizes report parameters, filters, and display settings. Acts as a global context accessible throughout report generation to avoid excessive parameter passing.

2. **Report Modularity** (`/reports/` directory): Each report type is implemented as a separate class (e.g., `report_atividades_nota_atribuida.php`) that extends a base report class. Reports are instantiated via the factory.

### Request Flow

```
index.php (route request, check permissions)
  ↓
factory.php (parse GET/POST params, instantiate report)
  ↓
renderer.php (build filters, render initial page)
  ↓
/relatorios/relatorios.php (generate table/graph, execute queries)
```

### Key Classes and Structures

**Data Models** (`datastructures.php`, `activities_datastructures.php`):
- `report_unasus_person`: Base class for users (tutors, students)
- `report_unasus_student`: Extends person, student-specific data
- `report_unasus_activity`: Abstract activity wrapper with submission/grade tracking
- `report_unasus_data`: Generic wrapper for activity data with methods like `is_submission_due()`, `is_grade_needed()`

**Factory** (`factory.php`):
- Singleton managing course context, selected report, filters, and display mode
- Properties are mostly protected to prevent accidental changes
- Filters vary by report type and are set in `index.php`

**Renderer** (`renderer.php`):
- Extends `plugin_renderer_base`
- Methods for building filters, tables, graphs, and warning messages
- Specific table layouts for different report types (e.g., `table_tutores`, `page_avaliacoes_em_atraso`)

**Reports** (`/reports/`):
- Each file implements a report class
- Accessed via query methods and rendering functions
- Examples: `report_estudante_sem_atividade_postada.php`, `report_boletim.php`, `report_tcc_consolidado.php`

### Supported Reports

**For Tutors**:
- estudante_sem_atividade_avaliada - Students without graded activities
- estudante_sem_atividade_postada - Students without submissions
- modulos_concluidos - Completed modules
- avaliacoes_em_atraso - Overdue grades
- atividades_nota_atribuida - Activities with grades assigned
- atividades_concluidas_agrupadas - Grouped completed activities
- entrega_de_atividades - Activity submissions
- atividades_vs_notas - Activities vs grades
- boletim - Grade report

**For Advisors (Orientadores)**:
- tcc_consolidado - Consolidated thesis work
- tcc_entrega_atividades - Thesis activity submissions
- tcc_concluido - Completed theses

**For Coordinators Only**:
- acesso_tutor - Tutor access tracking
- uso_sistema_tutor - Tutor system usage

## Plugin Dependencies

**Required**:
- `local_tutores` - Tutor management and middleware
- `local_relationship` - Student-tutor relationship management

**Optional**:
- `enrol/relationship` - Enrollment via relationships
- `local/report_config` - Report configuration settings

## Common Development Tasks

### Running Tests

This plugin uses Moodle's advanced_testcase framework.

**Initial Setup:**

```bash
# Copy and configure the environment file (required for both PHPUnit and Behat)
cp .env.template .env
# Edit .env with your environment settings:
#   CORE_NAME=unasuscp
#   DOCKER_VERSION=php56-nginx
#   URL_NAME=local-unasuscp.moodle.ufsc.br
```

**PHPUnit (Unit Tests):**

Preferred local command in this repository:

```bash
# Run all plugin tests (executes each *_test.php file explicitly)
./run_tests.sh

# Run a specific test file
./run_tests.sh tests/unasus_datastructures_test.php

# Force PHPUnit DB reset + init
./run_tests.sh --reset
```

Equivalent Moodle CLI examples:

```bash
# Run all tests for this plugin
php admin/tool/phpunit/cli/util.php --component=report_unasus

# Run a specific test file
php admin/tool/phpunit/cli/util.php --component=report_unasus --file=tests/unasus_datastructures_test.php
```

Notes about `run_tests.sh`:
- Requires `.env` file in the plugin directory (created from `.env.template`)
- By default, PHPUnit init is reused and only reruns when needed (first run, missing phpunit.xml, or Moodle build marker changed)
- `--reset` forces full reset (`util.php --drop`) and fresh init
- The script discovers tests by `*_test.php` and runs them file-by-file to avoid `No tests executed!` in older PHPUnit discovery behavior
- Uses `$CORE_NAME` from `.env` to determine container name dynamically

**Behat (Acceptance Tests):**

```bash
# Run all Behat scenarios for this plugin
./run_behat.sh

# Run a specific feature file
./run_behat.sh features/login.feature

# Run a specific scenario by tag
./run_behat.sh --tags="@critical"

# Stop Behat containers and cleanup
./stop_behat.sh

# Stop Behat and remove containers entirely
./stop_behat.sh --down
```

Notes about `run_behat.sh`:
- Requires `.env` file in the plugin directory (created from `.env.template`)
- Also reads `../../../../.env` (root Moodle environment) for base configuration
- Automatically sets up Behat environment, database, and Selenium container
- Handles dataroot permissions (`/home/moodle/moodledata`) automatically
- Uses `$CORE_NAME` from `.env` to determine container names dynamically
- Uses Selenium image `selenium/standalone-chrome:3.141.59-selenium` (Chrome 75); auto-recreates container if image mismatches
- Adds `/etc/hosts` mappings inside both containers (Moodle and Selenium) so `$URL_NAME` resolves locally instead of via UFSC external DNS
- **Requires user to be in `docker` group** (no `sudo`); run `sudo usermod -aG docker $USER` and start a new shell if not already
- **Requires patch in `vendor/behat/mink-selenium2-driver/.../Selenium2Driver.php`** to force OSS WebDriver dialect (see "Etapa 3" in Recent Changes Context below)
- For detailed scenario documentation and known limitations, see [TESTS.md](TESTS.md)

For a complete reference of all tests (PHPUnit and Behat), including scenarios, background data, and known limitations, see [TESTS.md](TESTS.md).

Test file: `tests/unasus_datastructures_test.php`

Tests verify (17 tests, 75+ assertions):
- Activity deadline detection (`has_deadline`)
- Activity flags: `has_submission()`, `has_grouping()`
- Invalid constructor arguments (InvalidArgumentException)
- Data submission tracking: `is_submission_due()` including historical data
- Grade requirements and deadlines: `is_grade_needed()`, `grade_due_days()`
- Has submitted / has grade on base class
- Activity pending status: `is_activity_pending()`
- Future due detection: `is_a_future_due()`
- Activity submission logic: draft, submitted, new statuses
- Grade -1 treated as absent (Moodle convention) — both int and string representations
- Forum data: `report_unasus_data_forum`
- Quiz data: `report_unasus_data_quiz`
- Grouping membership: `is_member_of()`

### Key Files by Responsibility

**Routing & Setup**:
- `index.php` - Entry point, permission checks, display mode routing

**Configuration**:
- `lib.php` - List of valid reports, permission checks, navigation integration
- `settings.php` - Plugin settings

**Core Logic**:
- `locallib.php` - Main functions for data retrieval (queries to database)
- `factory.php` - Report state management
- `datastructures.php` - Data model classes
- `activities_datastructures.php` - Activity-specific data models

**Presentation**:
- `renderer.php` - HTML rendering for tables, graphs, filters
- `/relatorios/` - Query execution and graph/table generation
  - `queries.php` - Database queries
  - `loops.php` - Data aggregation loops

**Styling & UI**:
- `styles.css` - Report styling
- `module.js` - JavaScript interactions
- `/img/` - Graphics and icons

**External Integration**:
- `sistematcc.php` - Client for TCC (thesis) system web service

## Important Notes

### Capability-Based Access

User access is controlled by three capabilities:
- `report/unasus:view_all` - Coordinator (sees all data)
- `report/unasus:view_tutoria` - Tutor (sees only their students)
- `report/unasus:view_orientacao` - Advisor (sees only their orientees)

Check `lib.php` functions like `report_unasus_relatorios_validos_tutoria_list()` to understand which reports each role can access.

### Report Parameter Flow

1. User selects report type and filters via `index.php`
2. Parameters are stored in the Factory singleton via GET/POST
3. Factory is accessed globally via `report_unasus_factory::singleton()`
4. Renderer retrieves values from factory and generates filter UI
5. When user submits, `modo_exibicao` (display mode) is set to 'tabela', 'grafico_valores', 'grafico_porcentagens', 'grafico_pontos', or 'export_csv'

### Database Access

- Uses Moodle's `$DB` API (never raw SQL)
- Queries in `relatorios/queries.php`
- Respects user permissions and course context
- Data aggregation in loops in `relatorios/loops.php`

### Known Performance Bottlenecks

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

### Recent Changes Context

Recent commits focus on report ordering, activity presentation, test coverage, documentation, bug fixes, and infrastructure improvements:

**Etapa 1 (April 2026) — Performance & Bug Fixes:**
- Activity reordering for synthetic reports
- Support for hidden Moodle courses in reports
- Fine-tuning report presentation order
- Expanded unit test suite from 5 to 22 tests (39 → 79 assertions), covering `is_activity_pending`, `is_a_future_due`, `is_member_of`, forum/quiz data classes, grade -1 handling, invalid constructor detection, and 6 boundary/edge-case tests
- Fixed `run_tests.sh` to discover `*_test.php` files explicitly (avoids PHPUnit 4.8 discovery bug)
- Suppressed Composer 1 deprecation noise in init output
- Fixed `phpunit/dbUnit` → `phpunit/dbunit` (lowercase) in root `composer.json`
- Consolidated Behat scenarios into 17 feature files (61 scenarios) with reusable steps
- Completed README.md: fixed incomplete sentence, added file structure, troubleshooting, and changelog sections
- Documented performance bottlenecks in CLAUDE.md (see "Known Performance Bottlenecks" above)
- Performance optimizations: correlated subquery → explicit LEFT JOINs in `queries.php`; memoized `report_unasus_get_tcc_definition()` and new `report_unasus_get_lti_type_config()` in `locallib.php`; memoized `SistemaTccClient::get_tcc_definition()` in `sistematcc.php`
- **Bug fix**: Fixed grade rendering in atividades_vs_notas report — grade value -1 (Moodle's "no grade" sentinel) was being rendered as a valid grade when received as string from database. Standardized grade -1 filtering across all activity data classes by casting to int before comparison in `activities_datastructures.php`; added test case `test_report_unasus_data_activity_grade_minus_one_as_string()` to cover string "-1" scenario.

**Etapa 2 (April 2026) — Infrastructure & Configuration:**
- Created `.env.template` with environment variables for flexible multi-environment setup
- Updated `.gitignore` to exclude `.env` (local configurations)
- Refactored `run_tests.sh`, `run_behat.sh`, `stop_behat.sh`:
  - Added helper functions: `log()`, `warn()`, `err()` for consistent logging
  - Added `.env` file reading with validation and error handling
  - Replaced hardcoded values with environment variables (`$CORE_NAME`, `$DOCKER_VERSION`, `$USER`)
  - Made container names, directories, and URLs dynamic based on configuration
  - Added Behat dataroot permission fixes (ensures `/home/moodle/moodledata` exists and has correct ownership)
- Scripts now work across different developer environments without modification

**Etapa 3 (May 2026) — Behat Infrastructure Completion:**
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

### Docker Environment & Configuration

#### Configuration Management (New in April 2026)

The scripts `run_tests.sh`, `run_behat.sh`, and `stop_behat.sh` now use environment variables from a `.env` file for flexibility across different environments and developers.

**Setup:**
1. Create `.env` file from template:
   ```bash
   cp .env.template .env
   ```

2. Edit `.env` with your environment:
   ```bash
   CORE_NAME=unasuscp              # System name suffix
   DOCKER_VERSION=php56-nginx      # Docker image version
   URL_NAME=local-unasuscp.moodle.ufsc.br
   ```

**Variables used:**
- `CORE_NAME` — System identifier (appended to container/site names)
- `DOCKER_VERSION` — Docker directory version (e.g., `php56-nginx`)
- `URL_NAME` — Behat test URL
- `$USER` — Current system user (replaces hardcoded `/home/rsc/`)

**Script updates (Etapa 2 infrastructure):**
- `run_tests.sh` — Reads `.env`, uses dynamic paths
- `run_behat.sh` — Reads both `../../../../.env` (root) and `.env` (plugin), handles Behat dataroot permissions
- `stop_behat.sh` — Reads `.env`, supports dynamic container cleanup

This design allows each developer to have local configurations without modifying scripts.

#### Repository Containers

This repository (`www/local-unasuscp`) is mounted by the container **`moodle-$CORE_NAME`** (derived from `CORE_NAME` in `.env`).

By default with `.env.template`:
```bash
CONTAINER_NAME="moodle-local-unasuscp"
DOCKER_COMPOSE_DIR="/home/$USER/workspace/docker/php56-nginx"
MOODLE_LOCAL_SITE="www/local-unasuscp"
```

There is a second container (`moodle-local-report-unasuscp`) that mounts `www/report-unasuscp` (a git worktree on `master` branch) — do not confuse the two.

#### Composer & Legacy Stack

Composer notes for this legacy stack:
- Root Moodle `composer.json` should use `phpunit/dbunit` (lowercase).
- Composer 1 deprecation/upgrade notices may still appear in raw init output due to legacy Moodle constraints.

## Moodle Plugin Standards

- Plugin type: `report` (stored in `/report/unasus/`)
- Component: `report_unasus`
- Language strings: `lang/en/report_unasus.php` (translations for UI)
- Version: Tracked in `version.php` with timestamp format YYYYMMDDXX
- Requires Moodle 2.8.6+ and specific local plugin versions
