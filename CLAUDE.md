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
- By default, PHPUnit init is reused and only reruns when needed (first run, missing phpunit.xml, or Moodle build marker changed).
- `--reset` forces full reset (`util.php --drop`) and fresh init.
- The script discovers tests by `*_test.php` and runs them file-by-file to avoid `No tests executed!` in older PHPUnit discovery behavior.

Test file: `tests/unasus_datastructures_test.php`

Tests verify:
- Activity deadline detection
- Data submission tracking (is_submission_due)
- Grade requirements and deadlines
- Activity submission logic

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

### Recent Changes Context

Recent commits focus on report ordering and activity presentation:
- Activity reordering for synthetic reports
- Support for hidden Moodle courses in reports
- Fine-tuning report presentation order

### Docker Environment

This repository (`www/local-unasuscp`) is mounted by the container **`moodle-local-unasuscp`** (defined in `docker-compose.yml`).

The `run_tests.sh` script must use:
```bash
CONTAINER_NAME="moodle-local-unasuscp"
DOCKER_COMPOSE_DIR="/home/rsc/workspace/docker/php56-nginx"
MOODLE_LOCAL_SITE="www/local-unasuscp"
```

There is a second container (`moodle-local-report-unasuscp`) that mounts `www/report-unasuscp` (a git worktree on `master` branch) — do not confuse the two.

Composer notes for this legacy stack:
- Root Moodle `composer.json` should use `phpunit/dbunit` (lowercase).
- Composer 1 deprecation/upgrade notices may still appear in raw init output due to legacy Moodle constraints.

## Moodle Plugin Standards

- Plugin type: `report` (stored in `/report/unasus/`)
- Component: `report_unasus`
- Language strings: `lang/en/report_unasus.php` (translations for UI)
- Version: Tracked in `version.php` with timestamp format YYYYMMDDXX
- Requires Moodle 2.8.6+ and specific local plugin versions
