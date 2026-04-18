- `run_tests.sh`: PHPUnit 4.8 no Moodle 3.0 não descobre `*_test.php` ao executar apenas diretório; executar cada arquivo explicitamente evita `No tests executed!`.
- `tests/unasus_datastructures_test.php` roda corretamente quando chamado diretamente (5 testes, 39 asserções).
- Composer warnings no `init.php`: corrigir `phpunit/dbUnit` para `phpunit/dbunit` no composer do Moodle raiz e sincronizar lock; avisos de upgrade do Composer 1 podem ser filtrados no script sem ocultar erros.

## Behat — report/unasus (TODOS OS 85 PASSOS PASSANDO)
- Behat usa prefixo `bht_`; queries SQL sem `{}` falham silenciosamente (tabelas erradas).
- `set_config()` deve ser usado em vez de `$DB->insert_record('config',...)` para atualizar `$CFG` imediatamente.
- Step `And I pause` causa timeout de JS no Behat — remover em produção.
- `module.js` `fixed_columns()` usava `SELECTORS` indefinido → guard `if (typeof SELECTORS === 'undefined') return;`
- `local/relationship/lib.php`: `$relationship->tags` deve ter guard `isset()` antes de `insert_record`.
- `behat_unasus.php`: usar `set_config()` e armazenar `$this->relationships[$name] = $id` ao criar relationships para rastrear IDs entre steps.
- `unasus.feature`: usar switchids (`category` → contextid, `relationship` → relationshipid) em vez de IDs hardcoded.