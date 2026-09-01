<?php

global $CFG; // Garante acesso às configurações do Moodle

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php'); // classe curl do core

/**
 * Classe para realizar requisições para o webservice do Sistema de TCC
 */
class report_unasus_SistemaTccClient {

    /** @var string $url */
    private $url;

    /** @var string $consumer_key */
    private $consumer_key;


    /**
     * Static mock responses for same-process tests (PHPUnit).
     * When non-null, HTTP calls are bypassed and these values are returned instead.
     * Keys are endpoint paths (e.g. '/tcc_definition_service', '/reportingservice_tcc').
     * Mutate via {@see self::set_mock_responses()} — do not access directly.
     *
     * @var array|null
     */
    private static $mock_responses = null;

    /**
     * Test-only setter for {@see self::$mock_responses}.
     * Pass null to restore normal production behaviour.
     *
     * @param array|null $responses Map of endpoint path => mocked response body.
     */
    public static function set_mock_responses($responses) {
        self::$mock_responses = $responses;
    }

    /**
     * @param string $external_url Endereço do sistema de TCC
     * @param string $consumer_key Consumer Key utilizado pela aplicação para realizar a autenticação
     */
    function __construct($external_url, $consumer_key) {
        $new_url = "";
        if (!empty($external_url)) {
            // Faz o parse na URL para poder montá-la corretamente em seguida
            $url = parse_url($external_url);

            $new_url = "{$url['scheme']}://{$url['host']}";
            if (!empty($url['port'])) {
                $new_url .= ":{$url['port']}";
            }
        }
        $this->url = $new_url;
        $this->consumer_key = $consumer_key;
    }

    /**
     * @param int $tcc_definition_id Id do Tcc Definition
     * @return mixed
     */
    public function get_tcc_definition($tcc_definition_id) {
        // per-request cache keyed by url+consumer_key+id to avoid duplicate WS calls
        static $cache = array();
        $cache_key = $this->url . '|' . $this->consumer_key . '|' . $tcc_definition_id;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $params = array(
            'consumer_key' => $this->consumer_key,
            'tcc_definition_id' => $tcc_definition_id
        );

        $json = $this->post('/tcc_definition_service', $params);
        $object = json_decode($json);

        $cache[$cache_key] = $object;
        return $object;
    }

    /**
     * @param array[int] $user_ids
     * @return mixed
     */
    public function get_report_data($user_ids) {

        $params = array(
            'consumer_key' => $this->consumer_key,
            'user_ids' => $user_ids
        );

        $json = $this->post('/reportingservice', $params);
        $object = json_decode($json);

        return $object;
    }

    /**
     * Dados de TCC dos alunos informados.
     *
     * @param array[int] $user_ids
     * @param int|null $tcc_definition_id Restringe a resposta a uma tcc_definition. O webservice
     *        trata 0/vazio/não-numérico como "sem filtro" (comportamento legado), então só enviamos
     *        o parâmetro quando há um id real — enviar 0 devolveria todos os TCCs do aluno em
     *        silêncio. Webservices anteriores ao filtro ignoram o parâmetro e respondem sem filtrar.
     * @return mixed
     */
    public function get_report_data_tcc($user_ids, $tcc_definition_id = null) {

        $params = array(
            'consumer_key' => $this->consumer_key,
            'user_ids' => $user_ids
        );

        if (!empty($tcc_definition_id) && is_numeric($tcc_definition_id)) {
            $params['tcc_definition_id'] = (int) $tcc_definition_id;
        }

        $json = $this->post('/reportingservice_tcc', $params);
        $object = json_decode($json);

        return $object;
    }

    /**
     * Realiza as requisições via POST
     *
     * @param string $path Caminho para a requisição (deve iniciar com /)
     * @param array $param Parâmetros que serão enviados (chave-valor)
     * @return bool|string
     */
    private function post($path, $param) {
        // Return static mock response (same-process mocking, e.g. PHPUnit).
        if (self::$mock_responses !== null) {
            return isset(self::$mock_responses[$path]) ? self::$mock_responses[$path] : false;
        }
        // Return config-table mock response (cross-process mocking for Behat browser tests).
        // The Behat context stores mock JSON in mdl_config_plugins via set_config() so that
        // the web server PHP process can read it when rendering the report page.
        // Cache lookups per-process keyed by config_key so reports calling post() many times
        // in the same request (e.g. one LTI per student) don't repeat the DB read.
        if (function_exists('get_config')) {
            static $config_mock_cache = array();
            $config_key = 'behat_tcc_mock_' . ltrim($path, '/');
            if (!array_key_exists($config_key, $config_mock_cache)) {
                $config_mock_cache[$config_key] = get_config('report_unasus', $config_key);
            }
            $mock = $config_mock_cache[$config_key];
            if ($mock !== false) {
                return $mock;
            }
        }

        // Sem URL nao ha' o que chamar. Antes isso seguia adiante e falhava adiante,
        // sem sinal nenhum; o baseurl vazio na atividade LTI e' uma causa comum.
        if (empty($this->url)) {
            debugging('report_unasus: URL do Sistema de TCC vazia (baseurl da atividade LTI nao configurado).',
                DEBUG_DEVELOPER);
            return false;
        }

        /*
         * Solução  para enviar via post array do php
         * http://php.net/manual/pt_BR/function.http-build-query.php
         *
         * O corpo continua sendo montado a mao, e nao entregue como array ao curl, porque o
         * webservice espera os indices no formato `user_ids[]` e nao `user_ids[0]`. Entregar o
         * array ao curl do core mudaria o que vai na rede.
         */
        $new_param = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($param, '', '&'));

        $curl = new curl();
        $curl->setHeader('Content-Type: application/x-www-form-urlencoded');
        $curl->setopt(array(
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ));

        $response = $curl->post("{$this->url}{$path}", $new_param);

        // Falha de transporte (DNS, recusa de conexao, timeout, bloqueio do curlsecurity).
        if ($curl->get_errno()) {
            debugging("report_unasus: falha ao chamar {$path} no Sistema de TCC: " . $curl->error,
                DEBUG_DEVELOPER);
            return false;
        }

        $info = $curl->get_info();
        $httpcode = isset($info['http_code']) ? (int) $info['http_code'] : 0;

        if ($httpcode < 200 || $httpcode >= 300) {
            debugging("report_unasus: Sistema de TCC respondeu HTTP {$httpcode} em {$path}.",
                DEBUG_DEVELOPER);
            return false;
        }

        return $response;
    }

}