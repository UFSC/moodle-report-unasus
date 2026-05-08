<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/report/unasus/locallib.php');
require_once($CFG->dirroot . '/report/unasus/sistematcc.php');

/**
 * Pinning das caches estáticas adicionadas em REVIEW_10136.
 *
 * Cobre:
 *  - report_unasus_get_tcc_definition() (locallib.php) — cache estática por input
 *  - report_unasus_SistemaTccClient::get_tcc_definition() — cache estática por url|key|id
 *
 * A cache `report_unasus_get_lti_type_config()` exige acesso a $DB (consulta a
 * mdl_lti_types) e fica coberta indiretamente por unasus_tcc.feature — ver NOTES.md.
 *
 * @group report_unasus
 */
class unasus_memoization_testcase extends advanced_testcase {

    protected function tearDown() {
        // Restaura o estado normal do client (sem mock) para não afetar outros testes
        // que rodem no mesmo processo PHP.
        report_unasus_SistemaTccClient::set_mock_responses(null);
    }

    // -----------------------------------------------------------------------
    // report_unasus_get_tcc_definition (locallib.php)
    //
    // A cache é static por processo e indexada pela string completa de entrada.
    // Como inputs distintos (mesmo que se sobreponham por valor) usam chaves
    // diferentes, e a função é determinística, basta pinar o comportamento por
    // input para garantir que a cache não introduza colisões.
    // -----------------------------------------------------------------------

    public function test_get_tcc_definition_memoizes_by_input_string() {
        // Input único para evitar colisão com cache de outros testes no mesmo processo.
        $input = 'memo_test_a=val1;memo_test_b=val2';

        $first  = report_unasus_get_tcc_definition($input);
        $second = report_unasus_get_tcc_definition($input);

        $expected = array(
            'memo_test_a' => 'val1',
            'memo_test_b' => 'val2',
        );

        $this->assertEquals($expected, $first);
        // Pin de idempotência: a cache não pode introduzir mutações entre chamadas
        $this->assertEquals($first, $second);
    }

    public function test_get_tcc_definition_distinct_inputs_distinct_results() {
        $input_a = 'memo_dist_x=10;memo_dist_y=20';
        $input_b = 'memo_dist_x=99;memo_dist_z=30';

        $result_a = report_unasus_get_tcc_definition($input_a);
        $result_b = report_unasus_get_tcc_definition($input_b);

        // Inputs distintos → chaves distintas → conteúdos não devem se sobrepor
        $this->assertEquals('10', $result_a['memo_dist_x']);
        $this->assertEquals('99', $result_b['memo_dist_x']);
        $this->assertArrayHasKey('memo_dist_y', $result_a);
        $this->assertArrayNotHasKey('memo_dist_y', $result_b);
        $this->assertArrayHasKey('memo_dist_z', $result_b);
        $this->assertArrayNotHasKey('memo_dist_z', $result_a);
    }

    public function test_get_tcc_definition_handles_malformed_input() {
        // Pares sem '=' devem ser silenciosamente ignorados (não derrubam a função).
        // Pares com chave vazia são aceitos (limitação conhecida do parser).
        $input = 'memo_mal_ok=value;sem_igual;memo_mal_other=x';

        $result = report_unasus_get_tcc_definition($input);

        $this->assertEquals('value', $result['memo_mal_ok']);
        $this->assertEquals('x', $result['memo_mal_other']);
        $this->assertArrayNotHasKey('sem_igual', $result);
        $this->assertCount(2, $result);
    }

    // -----------------------------------------------------------------------
    // report_unasus_SistemaTccClient::get_tcc_definition
    //
    // A cache é static dentro do método e indexada por url|consumer_key|id.
    // Usamos consumer_keys únicos por teste para evitar colisões com chamadas
    // anteriores no mesmo processo.
    // -----------------------------------------------------------------------

    public function test_sistema_tcc_client_caches_get_tcc_definition() {
        // Verificamos a cache trocando o mock entre chamadas: se a segunda chamada
        // retornar o valor da primeira, é porque a cache hit ocorreu (post() não
        // foi invocado novamente).
        report_unasus_SistemaTccClient::set_mock_responses(array(
            '/tcc_definition_service' => '{"id":1,"name":"first"}',
        ));
        $client = new report_unasus_SistemaTccClient(
            'http://memo-cache.example.org',
            'memo_cache_key_unique'
        );

        $first = $client->get_tcc_definition(7777);
        $this->assertEquals(1, $first->id);
        $this->assertEquals('first', $first->name);

        // Trocamos o mock — se a cache não funcionasse, a próxima chamada retornaria 'second'.
        report_unasus_SistemaTccClient::set_mock_responses(array(
            '/tcc_definition_service' => '{"id":2,"name":"second"}',
        ));

        $cached = $client->get_tcc_definition(7777);
        $this->assertEquals(1, $cached->id);
        $this->assertEquals('first', $cached->name);

        // ID diferente → cache miss → busca novo mock
        $other = $client->get_tcc_definition(8888);
        $this->assertEquals(2, $other->id);
        $this->assertEquals('second', $other->name);
    }

    public function test_sistema_tcc_client_distinct_keys_distinct_calls() {
        // Cache key inclui url e consumer_key — clients distintos não compartilham cache.
        report_unasus_SistemaTccClient::set_mock_responses(array(
            '/tcc_definition_service' => '{"id":100}',
        ));

        $client_a = new report_unasus_SistemaTccClient(
            'http://memo-key-a.example.org',
            'memo_key_a_unique'
        );
        $client_b = new report_unasus_SistemaTccClient(
            'http://memo-key-b.example.org',
            'memo_key_b_unique'
        );

        $result_a = $client_a->get_tcc_definition(1);
        $this->assertEquals(100, $result_a->id);

        // Trocamos mock antes do segundo cliente — como url e consumer_key diferem,
        // a cache key é outra e o post() é invocado novamente.
        report_unasus_SistemaTccClient::set_mock_responses(array(
            '/tcc_definition_service' => '{"id":200}',
        ));

        $result_b = $client_b->get_tcc_definition(1);
        $this->assertEquals(200, $result_b->id);

        // E o primeiro cliente continua retornando seu valor cacheado
        $result_a_again = $client_a->get_tcc_definition(1);
        $this->assertEquals(100, $result_a_again->id);
    }
}
