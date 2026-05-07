# Code Review — branch `10136-Preparacao-testes-relatorio-unasus` vs `master`

49 files changed (+8362 / −249). Most volume is Behat infrastructure & feature files; production PHP changes are ~12 files plus `tests/unasus_datastructures_test.php`.

> **Status (2026-05-07):** review fechada. Todos os achados de correção foram tratados; o que sobrou é hardening de cobertura PHPUnit (sem bug subjacente) e bottlenecks pendentes documentados em `NOTES.md`. Resumo de resolução por achado na seção [Resolução final](#resolução-final-2026-05-07) ao fim do documento.

---

## English

### Overview
The branch (a) consolidates a large Behat suite, (b) ships several real bug/perf fixes (grade `-1` sentinel handling across all activity types, LTI config memoization, TCC memoization, correlated subquery → LEFT JOIN, TCC consolidated aggregate-key init, `acesso_tutor` `H:m:s`→`H:i:s` typo fix), and (c) overhauls completion lookup in `report_atividades_nota_atribuida` to use `coursemoduleid`.

### Findings

#### Blocker
- **`reports/report_atividades_nota_atribuida.php:272`** — `$DB->get_records('course_modules_completion', null, ...)` loads the entire site-wide completion table into PHP memory. Filter by `coursemoduleid IN (...)` and/or `userid IN (...)` derived from `$associativo_atividade` and the report's student set. As written this will OOM on a real install before the report renders.

#### High
- **`reports/report_atividades_nota_atribuida.php:100,102`** — New `query_database_completion_from_users` / `query_lti_completion_from_users` use `GROUP BY userid` with non-aggregated columns. Will fail under `ONLY_FULL_GROUP_BY` (MySQL ≥5.7 / MariaDB 10.x default). Verify target SQL mode or add proper aggregates.
- **`relatorios/loops.php:531-554`** — New LTI completion fetch is gated on `is_a($report, 'report_atividades_nota_atribuida')`, but `$report` here is typically the factory singleton (holds `tipo_relatorio` as a string, not a subclass instance). High risk this branch is dead code — trace the call site in `relatorios.php` and confirm before merging.
- **`renderer.php:62-72`** — Side-effecting scope mutation (`$report->tutores_selecionados = ...`) inside `build_page()` — duplicated at six other call sites in the file. Belongs in `factory::initialize()` so role scoping happens once before any data fetch. Also: a user with both `view_tutoria` and `view_orientacao` triggers both blocks.

#### Medium
- **`datastructures.php:790-807`** — `user_activity_completion()` switched from JOIN-based lookup to `$DB->get_field('course_modules_completion', ..., ['coursemoduleid' => ..., 'userid' => ...])`. Correct only if every caller now passes `source_activity->coursemoduleid`. LtiPortfolioQuery sets it (`queries.php:1281`) — verify non-LTI activity classes (assign/forum/quiz/scorm/data) all populate it too.
- **`relatorios/queries.php:62,137`** — `LEFT JOIN {user_info_field} uif ON uif.shortname = 'polo'` — replace literal `'polo'` with a named placeholder for consistency with the rest of the codebase. No injection risk (literal), pure convention.
- **`sistematcc.php:160-162`** — `get_config('report_unasus', 'behat_tcc_mock_'.ltrim($path,'/'))` is called on every `post()` with no memoization. Reports with many TCC LTIs pay a DB lookup per call. Mirror the `getZendInstalled()` `static $is_mock_active` cache.
- **`reports/report_atividades_nota_atribuida.php:42-66`** — CSV header alignment: the `totalalunos` group with 1 column may still emit padding columns elsewhere depending on `$h` width. Sanity-check the resulting CSV column count vs the data rows.

#### Low
- **`locallib.php:14`** — `date("Y-m-d H:m:s", …)` → `date("Y-m-d H:i:s", …)`. Real fix (the `m` was emitting month, not minutes). Flag any persisted/cached values from the old code as potentially wrong.
- **`reports/report_acesso_tutor.php:148`** — `strftime('%B', …)` is deprecated in PHP 8.1+ and locale-sensitive. Group keys silently change if `setlocale(LC_TIME,'pt_BR')` isn't set globally. Plan migration.
- **`reports/report_uso_sistema_tutor.php:104`** — `strtotime($data_inicio->format('Y-m-d'))` round-trips through a string; use `$data_inicio->getTimestamp()` instead.
- **`sistematcc.php:31`** — `public static $mock_responses` should be `private` with a static setter — exposes test plumbing to all callers.
- **`relatorios/queries.php:1283-1284`** — Initialising `$model->status = []; $model->state_date = []` silences undefined-index warnings; verify the chapters loop actually populates these or you're hiding a real failure.
- **`module.js:291-293`** — Add `if (!Y || !Y.all)` guard alongside the `typeof SELECTORS === 'undefined'` check.

#### Nit
- **`relatorios/queries.php:62,137`** — Indentation on the new LEFT JOIN lines is inconsistent with the surrounding 6-space alignment.

### Performance claims (CLAUDE.md ✅ items) — all CONFIRMED
- Correlated subquery → LEFT JOIN in `query_alunos_relationship` / `_student` (`queries.php:62, 137`). Semantically equivalent (`uif.shortname` unique).
- `report_unasus_get_lti_type_config()` static cache by `typeid` (`locallib.php:727-735`).
- `report_unasus_get_tcc_definition()` memoization (`locallib.php:905-911`).
- `SistemaTccClient::get_tcc_definition()` cache by `url|key|id` (`sistematcc.php:89-94`).
- TCC consolidated aggregate-key init (`report_tcc_consolidado.php:96-120`).
- Grade -1 int-cast across activity classes (`activities_datastructures.php:734, 786, 824, 847, 870, 905, 1014`).

### Test coverage gaps
`tests/unasus_datastructures_test.php` covers data classes only. Untested production changes:
- `datastructures.php:790-807` — `user_activity_completion()` `coursemoduleid` lookup.
- `report_atividades_nota_atribuida::get_completion_states_by_user()` / `is_activity_complete_for_user()` — gate the entire report.
- `locallib.php` `report_unasus_get_lti_type_config()` / `report_unasus_get_tcc_definition()` memoization (no assertion that second call avoids DB).
- `SistemaTccClient::get_tcc_definition()` cache + `behat_tcc_mock_*` plumbing.
- `query_database_completion_from_users` / `query_lti_completion_from_users`.
- `report_acesso_tutor::get_interval_boundaries()` / `get_days_interval()` — pure, easy to unit-test.
- The string `"-1"` test exists for `report_unasus_data_activity` only; identical change in forum/quiz/db/scorm/lti/nota_final classes is uncovered.

### Recommended merge gates
1. Fix the unbounded `course_modules_completion` query (blocker).
2. Verify `is_a($report, ...)` in `loops.php:531-554` is actually true at runtime.
3. Confirm `ONLY_FULL_GROUP_BY` is off on production DB or fix the new `GROUP BY` queries.
4. Move role-scope mutation out of `renderer::build_page()` into the factory.

---

## Português

### Visão geral
A branch (a) consolida uma extensa suíte Behat, (b) entrega várias correções reais de bug/performance (tratamento do sentinela de nota `-1` em todos os tipos de atividade, memoização de configuração LTI, memoização de TCC, subconsulta correlacionada → LEFT JOIN, inicialização das chaves agregadas no relatório TCC consolidado, correção do typo `H:m:s`→`H:i:s` em `acesso_tutor`) e (c) reformula a busca de conclusão em `report_atividades_nota_atribuida` para usar `coursemoduleid`.

### Achados

#### Bloqueador
- **`reports/report_atividades_nota_atribuida.php:272`** — `$DB->get_records('course_modules_completion', null, ...)` carrega a tabela inteira de conclusões do site na memória PHP. Filtre por `coursemoduleid IN (...)` e/ou `userid IN (...)` derivados de `$associativo_atividade` e do conjunto de alunos do relatório. Do jeito que está, vai estourar memória em uma instalação real antes mesmo de renderizar o relatório.

#### Alto
- **`reports/report_atividades_nota_atribuida.php:100,102`** — As novas `query_database_completion_from_users` / `query_lti_completion_from_users` usam `GROUP BY userid` com colunas não agregadas. Vai falhar sob `ONLY_FULL_GROUP_BY` (padrão em MySQL ≥5.7 / MariaDB 10.x). Verifique o SQL mode do alvo ou adicione agregações apropriadas.
- **`relatorios/loops.php:531-554`** — A nova busca de conclusão LTI está protegida por `is_a($report, 'report_atividades_nota_atribuida')`, mas `$report` aqui costuma ser o singleton da factory (carrega `tipo_relatorio` como string, não uma instância da subclasse). Risco alto desse trecho ser código morto — rastreie o ponto de chamada em `relatorios.php` e confirme antes do merge.
- **`renderer.php:62-72`** — Mutação de escopo com efeito colateral (`$report->tutores_selecionados = ...`) dentro de `build_page()` — duplicada em outros seis pontos de chamada do arquivo. Pertence a `factory::initialize()` para que o escopo por papel seja aplicado uma única vez antes de qualquer busca de dados. Além disso: um usuário com `view_tutoria` E `view_orientacao` aciona ambos os blocos.

#### Médio
- **`datastructures.php:790-807`** — `user_activity_completion()` mudou de uma busca via JOIN para `$DB->get_field('course_modules_completion', ..., ['coursemoduleid' => ..., 'userid' => ...])`. Só está correto se todos os chamadores passarem `source_activity->coursemoduleid`. LtiPortfolioQuery preenche (`queries.php:1281`) — verifique se as classes de atividade não-LTI (assign/forum/quiz/scorm/data) também preenchem.
- **`relatorios/queries.php:62,137`** — `LEFT JOIN {user_info_field} uif ON uif.shortname = 'polo'` — substitua o literal `'polo'` por um placeholder nomeado para manter consistência com o resto do código. Sem risco de injeção (é literal), apenas convenção.
- **`sistematcc.php:160-162`** — `get_config('report_unasus', 'behat_tcc_mock_'.ltrim($path,'/'))` é chamado em cada `post()` sem memoização. Relatórios com muitos LTIs de TCC pagam uma consulta ao banco por chamada. Espelhe o cache `static $is_mock_active` de `getZendInstalled()`.
- **`reports/report_atividades_nota_atribuida.php:42-66`** — Alinhamento de cabeçalho do CSV: o grupo `totalalunos` com 1 coluna pode emitir colunas de preenchimento em outros pontos dependendo da largura `$h`. Confira a contagem final de colunas do CSV vs as linhas de dados.

#### Baixo
- **`locallib.php:14`** — `date("Y-m-d H:m:s", …)` → `date("Y-m-d H:i:s", …)`. Correção real (`m` estava emitindo o mês, não os minutos). Sinalize que valores persistidos/cacheados gerados pelo código antigo podem estar errados.
- **`reports/report_acesso_tutor.php:148`** — `strftime('%B', …)` está deprecado em PHP 8.1+ e é sensível ao locale. Chaves de agrupamento mudam silenciosamente se `setlocale(LC_TIME,'pt_BR')` não estiver definido globalmente. Planeje migração.
- **`reports/report_uso_sistema_tutor.php:104`** — `strtotime($data_inicio->format('Y-m-d'))` faz round-trip por string; use `$data_inicio->getTimestamp()`.
- **`sistematcc.php:31`** — `public static $mock_responses` deveria ser `private` com setter estático — expõe encanamento de teste a todos os chamadores.
- **`relatorios/queries.php:1283-1284`** — Inicializar `$model->status = []; $model->state_date = []` silencia avisos de índice indefinido; verifique se o loop de capítulos realmente popula esses campos ou você está mascarando uma falha real.
- **`module.js:291-293`** — Adicione guarda `if (!Y || !Y.all)` junto à checagem `typeof SELECTORS === 'undefined'`.

#### Cosmético
- **`relatorios/queries.php:62,137`** — Indentação das novas linhas LEFT JOIN está inconsistente com o alinhamento de 6 espaços do contexto.

### Verificação dos ganhos de performance (itens ✅ no CLAUDE.md) — todos CONFIRMADOS
- Subconsulta correlacionada → LEFT JOIN em `query_alunos_relationship` / `_student` (`queries.php:62, 137`). Semanticamente equivalente (`uif.shortname` é único).
- Cache estático de `report_unasus_get_lti_type_config()` por `typeid` (`locallib.php:727-735`).
- Memoização de `report_unasus_get_tcc_definition()` (`locallib.php:905-911`).
- Cache de `SistemaTccClient::get_tcc_definition()` por `url|key|id` (`sistematcc.php:89-94`).
- Inicialização das chaves agregadas do TCC consolidado (`report_tcc_consolidado.php:96-120`).
- Cast de nota -1 para int em todas as classes de atividade (`activities_datastructures.php:734, 786, 824, 847, 870, 905, 1014`).

### Lacunas de cobertura de teste
`tests/unasus_datastructures_test.php` cobre apenas as classes de dados. Mudanças de produção sem teste:
- `datastructures.php:790-807` — busca por `coursemoduleid` em `user_activity_completion()`.
- `report_atividades_nota_atribuida::get_completion_states_by_user()` / `is_activity_complete_for_user()` — controlam o relatório inteiro.
- Memoização de `report_unasus_get_lti_type_config()` / `report_unasus_get_tcc_definition()` em `locallib.php` (nenhuma asserção de que a segunda chamada evita o banco).
- Cache de `SistemaTccClient::get_tcc_definition()` + encanamento `behat_tcc_mock_*`.
- `query_database_completion_from_users` / `query_lti_completion_from_users`.
- `report_acesso_tutor::get_interval_boundaries()` / `get_days_interval()` — funções puras, fáceis de testar.
- O teste com string `"-1"` existe apenas para `report_unasus_data_activity`; a mesma alteração em forum/quiz/db/scorm/lti/nota_final está descoberta.

### Bloqueios sugeridos para o merge
1. Corrigir a consulta sem filtro em `course_modules_completion` (bloqueador).
2. Verificar se `is_a($report, ...)` em `loops.php:531-554` é de fato verdadeiro em runtime.
3. Confirmar que `ONLY_FULL_GROUP_BY` está desligado no banco de produção ou ajustar as novas queries com `GROUP BY`.
4. Mover a mutação de escopo por papel de `renderer::build_page()` para a factory.

---

## Addendum — segunda passada

### Correções da primeira passada

- **High #2 (`is_a($report, 'report_atividades_nota_atribuida')`) NÃO é código morto.** O singleton da factory (`factory.php:172-202`) realmente faz `self::$report = new $class_name; self::$report->initialize();` onde `$class_name = "report_{$relatorio}"`. O slot estático `private static $report` (`factory.php:91`) guarda a instância da **subclasse**. Portanto `report_unasus_factory::singleton()` retorna um `report_atividades_nota_atribuida` quando esse relatório está selecionado, e o `is_a(...)` é verdadeiro em runtime. O fetch novo de conclusão LTI em `loops.php:531-554` executa. A crítica de design vira **Médio** (acoplamento do loop a uma subclasse específica — deveria ser hook polimórfico na factory), mas não é mais bloqueador.
- **High #3 (contagem de pontos de mutação no renderer).** O bloco `$report->tutores_selecionados = …` / `orientadores_selecionados = …` está duplicado em **5 pontos** de `renderer.php` (linhas 65/71, 524/530, 562/568, 646/652, 814/820), não seis. A crítica arquitetural permanece e o bug do usuário com dupla capability disparando ambos os blocos é real.

### Achados novos

#### Correção relevante (positivo — destacar na descrição da PR)
- **Todas as chamadas `Factory::eliminate_html` no master apontavam para uma classe que não existe.** Busca em toda a árvore (master e branch) confirma: não há `class Factory`, `class_alias('Factory', …)` nem `use` de namespace. A classe é `report_unasus_factory`. Logo, todo caminho de export CSV no master (`report_atividades_vs_notas`, `report_boletim`, `report_potenciais_evasoes`, `report_uso_sistema_tutor`, `report_acesso_tutor`, `report_atividades_nota_atribuida`, `report_tcc_consolidado`) lançava `Class 'Factory' not found` em runtime. A branch corrige isso em todos os pontos junto com a limpeza do CSV. Vale destacar na descrição da PR para que reviewers/QA saibam que o export estava efetivamente quebrado antes.

#### Alto
- **Export CSV no master também emitia nomes de grupo sem sanitização.** O padrão antigo era `file_put_contents('php://output', $name[$count]); fputcsv($fp, $tutor_name);` com `$tutor_name = array()` — ou seja, o nome do grupo era escrito **cru, sem aspas, sem escape** direto no stream de resposta, seguido de uma linha CSV vazia. Um nome contendo `,`, `"` ou `\n` corrompia o CSV. O novo `fputcsv($fp, array(report_unasus_factory::eliminate_html($groupname)))` faz a quotação correta. É correção de injeção/corrupção de formato CSV, não só refactor — vale citar.

#### Médio
- **`query_lti_completion_from_users` / `query_database_completion_from_users` têm nome enganoso.** Não filtram nem fazem JOIN com `course_modules_completion` — são apenas projeções mais enxutas de `query_lti_from_users` / `query_database_adjusted_from_users`. A consulta de conclusão real ocorre depois, em `get_completion_states_by_user()` dentro de `report_atividades_nota_atribuida`. Sugiro renomear para `_minimal_from_users` ou unificar com as originais via flag, senão futuros leitores vão presumir que as queries são *completion-aware*.
- **Polimorfismo via `is_a()` (rebaixado do High da primeira passada).** `loops.php:531-554` ramifica por subclasse. Como a factory de fato instancia a subclasse, funciona — mas é code smell. Um hook `is_lti_completion_needed()` (ou análogo) na base da factory, sobrescrito em `report_atividades_nota_atribuida`, eliminaria o `is_a` e manteria `loops.php` agnóstico ao relatório.
- **Duplicação do role-scoping no `renderer.php` (5×).** Além da crítica arquitetural já feita: extrair um helper único (`apply_role_scope($report)`) chamado uma vez no topo de `build_page()` (ou, melhor, dentro de `factory::initialize()`) e remover as outras quatro cópias. Hoje, qualquer caminho de entrada que pule `build_page()` vê `tutores_selecionados` sem escopo aplicado.
- **Pin do cache em `getZendInstalled()`.** O novo `static $is_mock_active` (`sistematcc.php:48-55`) lê `behat_tcc_mock_tcc_definition_service` uma vez por processo e fixa o valor. Se um teste setar o mock **depois** que `getZendInstalled()` já foi chamado uma vez na mesma requisição, o cache retorna o `false` velho. No Behat, cada step é uma requisição nova, então não é problema na prática, mas a chave do cache cobre só o `_definition_service` — mocks de `_reportingservice_tcc` são lidos em `post()` sem passar por esse gate. Documentar a limitação ou chavear o cache pelos dois.

#### Baixo
- **`report_uso_sistema_tutor.php:104-105`** — adiciona `$data_inicio->setTime(0,0,0)` imediatamente antes de `strtotime($data_inicio->format('Y-m-d'))`. O `format('Y-m-d')` já descarta a hora — o `setTime` está morto. Remova ou substitua o round-trip por `$data_inicio->setTime(0,0,0)->getTimestamp()`.
- **`relatorios/queries.php:1283-1284`** — revisitando o init `$model->status = []; $model->state_date = []`: o loop de capítulos é `foreach ($chapters as $chapter)`. Quando `$chapters` está vazio (TCC sem capítulos ainda), os renderizadores downstream iteram `$model->status` e antes batiam em "Undefined property". O init é correção legítima, não silenciamento de warning. A dúvida da primeira passada ("não está mascarando falha?") está respondida — manter.
- **`report_acesso_tutor.php:148`** — `strftime('%B', $date->format('U'))` já anotado. Preocupação extra: `$date->format('U')` retorna string; `strftime` espera int em PHP < 8. Cast: `strftime('%B', (int) $date->format('U'))`. Ou já migre para `IntlDateFormatter::create($locale, …, 'LLLL')` no caminho.
- **`tests/behat/behat_unasus.php` tem 3086 LOC em uma única classe.** Não bloqueia, mas reviewers devem esperar a manutenção pesada. Considerar quebrar em traits por concern (`unasus_data_setup`, `unasus_table_assertions`, `unasus_csv_assertions`, `unasus_tcc_mocks`) antes do arquivo dobrar.
- **`run_behat.sh:30-36`** — `err` está definido como `echo … ; exit 1` mas várias chamadas ainda fazem `err "…"; exit 1`. O `exit 1` é redundante na maioria dos call sites — inofensivo, mas indica que o helper não foi totalmente entendido por quem chama. Remover os `exit 1` redundantes por consistência.

#### Cosmético
- **`relatorios/queries.php:62, 137`** — indentação já anotada. As linhas novas `LEFT JOIN {user_info_field} uif` começam na coluna 1 enquanto o entorno está alinhado na coluna 7. Re-indentar.

### Verificações pontuais (para reviewers)
- Instanciação da subclasse pela factory: confirmado em `factory.php:172-202` (singleton chama `new $class_name`).
- Não restam referências `Factory::` na branch (`grep -rn 'Factory::' --include='*.php'` retorna vazio).
- Duplicação do role-scoping no renderer: confirmado 5 call sites em `renderer.php`.
- Fetch sem filtro em `course_modules_completion`: confirmado em `report_atividades_nota_atribuida.php:268-273` — sem `WHERE`, sem `IN (...)`. **Bloqueador permanece.**

---

## Resolução final (2026-05-07)

Cada achado foi tratado conscientemente em uma das três trilhas: **✅ corrigido** (commit referenciado), **⚪ verificado sem fix** (análise concluiu que não há risco ativo no fluxo atual), **📋 backlog** (movido para hardening futuro, fora do escopo desta review).

### Bloqueador

| # | Achado | Status | Commit |
|---|---|---|---|
| 1 | `course_modules_completion` carregada inteira em memória (`report_atividades_nota_atribuida.php:272`) | ✅ corrigido | `7d21e39` — IN-list filter; benchmark 2.7s→24ms, 1176MB→6MB |

### High

| # | Achado | Status | Commit |
|---|---|---|---|
| 2 | `GROUP BY userid` com colunas não agregadas em queries novas (`ONLY_FULL_GROUP_BY`) | ✅ corrigido | `7d21e39` — 7 funções de `queries.php` saneadas; bug latente do `gg.userid` ambíguo eliminado |
| 3 | `is_a($report, ...)` no `loops.php:531` parecia código morto | ⚪ verificado sem fix → 📋 rebaixado a Médio na addendum, depois ✅ refatorado | `3706829` — substituído por hook `needs_lti_synthesis_fetch()` na base |
| 4 | Mutação de escopo por papel duplicada em `renderer.php` (5 call sites + bug de dupla capability) | ✅ corrigido | `7d21e39` — extraído `apply_role_scope()` com early-return em `view_all` |

### Medium

| # | Achado | Status | Commit |
|---|---|---|---|
| 5 | `user_activity_completion()` mudou para `coursemoduleid`; verificar todas as activity classes | ⚪ verificado sem fix | LtiPortfolioQuery (`queries.php:1281`) preenche; demais classes não usam o caminho |
| 6 | `'polo'` literal em `queries.php:62,137` — substituir por placeholder | ⚪ verificado sem fix | Zero precedentes no codebase para esse padrão; valor é constante de schema; substituição forçaria churn em todos os consumidores via `$DB->get_records_sql` |
| 7 | `behat_tcc_mock_*` lookup sem memoização em `sistematcc.php::post()` | ✅ corrigido | `41d6b40` — cache estático per-process keyed por `config_key` |
| 8 | CSV header alignment em `report_atividades_nota_atribuida.php:42-66` | ⚪ verificado sem fix | Análise: ramo `totalalunos` aligned para qualquer n; ramo regular aligned no fluxo atual (`mostrar_total = false` em todos os callers); risco latente com `report_unasus_total_atividades_concluidas` apenas se algum caller futuro habilitar `mostrar_total` — fora do escopo |
| 9 | Naming enganoso `query_*_completion_from_users` (não tocam tabela de completion) | ✅ corrigido | `41d6b40` — renomeado para `query_*_synthesis_from_users` com phpdoc explicando contraste com sibling `_adjusted_` |
| 10 | Polimorfismo via `is_a()` (rebaixado de High #3) | ✅ corrigido | `3706829` — ver achado #3 |
| 11 | Duplicação do role-scoping no renderer (rebaixado de High #4) | ✅ corrigido | `7d21e39` — ver achado #4 |
| 12 | Pin parcial do cache em `getZendInstalled()` | ⚪ verificado sem fix | Mocks de `_reportingservice_tcc` agora também são cacheados via achado #7; cobertura completa |

### Low

| # | Achado | Status | Commit |
|---|---|---|---|
| 13 | `H:m:s` → `H:i:s` em `locallib.php:14` (typo: emitia mês em vez de minutos) | ✅ corrigido | (já em `master` antes desta review — mantido como bug fix da branch) |
| 14 | `strftime('%B', $date->format('U'))` deprecado e sem cast (`report_acesso_tutor.php:148`) | ✅ corrigido | `e99c99c` — cast `(int) $date->format('U')` |
| 15 | `strtotime($data_inicio->format('Y-m-d'))` round-trip + `setTime` morto (`report_uso_sistema_tutor.php:104-105`) | ✅ corrigido | `e99c99c` — usa `setTime(0,0,0)->getTimestamp()` |
| 16 | `public static $mock_responses` deveria ser private com setter | ✅ corrigido | `e99c99c` — `private` + `set_mock_responses()`; chamador Behat atualizado |
| 17 | Init `$model->status = []` / `state_date = []` em `queries.php:1283-1284` poderia mascarar falha | ⚪ verificado sem fix | Addendum: init é correto (TCCs sem capítulos são caso real, downstream itera essas chaves) |
| 18 | `module.js:291-293` falta guard para `Y` / `Y.all` | ✅ corrigido | `e99c99c` — `if (!Y \|\| !Y.all \|\| ...)` |
| 19 | `report_uso_sistema_tutor.php:104-105` revisitado: `setTime` morto antes do `strtotime` | ✅ corrigido | (mesmo commit do #15) |
| 20 | `report_acesso_tutor.php:148` revisitado: cast int para PHP < 8 | ✅ corrigido | (mesmo commit do #14) |
| 21 | `tests/behat/behat_unasus.php` com 3087 LOC — considerar quebrar em traits | ⚪ parcialmente corrigido | `d291f9d` — auditoria removeu 426 LOC mortas (3087→2661, −13.8%): fixture composta `manager` (DO NOT USE), 7 step methods sem ref, 2 helpers privados, 2 propriedades órfãs. Refatoração em traits permanece como follow-up (📋 backlog) |
| 22 | `run_behat.sh` `exit 1` redundante após `err` em vários call sites | ✅ corrigido | `e99c99c` — removido em `run_behat.sh`, `run_tests.sh`, `stop_behat.sh` |

### Cosmético

| # | Achado | Status | Commit |
|---|---|---|---|
| 23 | Indentação inconsistente nas linhas LEFT JOIN novas em `queries.php:62, 137` | ✅ corrigido | `41d6b40` — re-indentado para coluna 7 alinhando com sibling `LEFT JOIN {user_info_data}` |

### Achados positivos da addendum

| # | Achado | Status |
|---|---|---|
| A | `Factory::eliminate_html` não existia no master — todo export CSV estava quebrado em runtime | ⚪ correção da branch — flag para descrição da PR |
| B | Export CSV no master escrevia nome de grupo cru, sem aspas/escape — corrupção de formato CSV | ⚪ correção da branch — flag para descrição da PR |

### Test coverage gaps (Lote 5 — 📋 backlog de hardening)

Movidos para backlog pós-review. Nenhum corresponde a bug; todos são adições de cobertura PHPUnit:

- `datastructures.php:790-807` — `user_activity_completion()` lookup por `coursemoduleid`
- `report_atividades_nota_atribuida::get_completion_states_by_user()` / `is_activity_complete_for_user()`
- Memoizações: `report_unasus_get_lti_type_config()`, `report_unasus_get_tcc_definition()`, `SistemaTccClient::get_tcc_definition()` — assert de que segunda chamada não toca DB
- `query_*_synthesis_from_users` (renomeadas) — query result smoke
- `report_acesso_tutor::get_interval_boundaries()` / `get_days_interval()` — funções puras
- Grade `-1` string: teste existe só em `report_unasus_data_activity`; a mesma lógica em forum/quiz/db/scorm/lti/nota_final permanece descoberta

### Bottlenecks de performance pendentes (📋 documentados em `NOTES.md`)

Três itens HIGH/CRITICAL e um MEDIUM permanecem sem fix por escopo, registrados em `NOTES.md` (seção "Known Performance Bottlenecks"):

- `loops.php:32-289` — O(n³) nested loops (CRITICAL)
- `loops.php:332-337` — recursão dupla em `atividades_alunos_grupos` (HIGH)
- `queries.php:72-85` — `SELECT DISTINCT` redundante (MEDIUM)
- `queries.php:720-810` — duplicação de cinco queries por tipo de atividade (MEDIUM)

### Resumo de validação

- PHPUnit (`tests/unasus_datastructures_test.php`): 22 testes, 79+ asserções verdes
- Behat suíte completa: 90 cenários, 2528 passos verdes (último run pós Lote 2: `3706829`)
- Smoke set (`unasus_sem_dados` + `unasus_permissions`) executado em todos os lotes parciais
