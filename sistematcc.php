<?php

global $CFG; // Garante acesso às configurações do Moodle

defined('MOODLE_INTERNAL') || die;

$zendpath = $CFG->libdir . '/zend/Zend/Loader/Autoloader.php';

// Carrega o zend framework
if (file_exists($zendpath)) {
    require_once($zendpath);
    Zend_Loader_Autoloader::getInstance(); // No ZF1, usa-se getInstance() em vez de autoload()
}

/**
 * Classe para realizar requisições para o webservice do Sistema de TCC
 */
class report_unasus_SistemaTccClient {

    /** @var string $url */
    private $url;

    /** @var string $consumer_key */
    private $consumer_key;

    /** @var \Zend_Http_Client $client */
    private $client;

    private $ZendInstalled;

    /**
     * Static mock responses for Behat tests.
     * When non-null, HTTP calls are bypassed and these values are returned instead.
     * Keys are endpoint paths (e.g. '/tcc_definition_service', '/reportingservice_tcc').
     * Set to null to restore normal production behaviour.
     *
     * @var array|null
     */
    public static $mock_responses = null;

    public function getZendInstalled() {
        // When static mock mode is active (same-process, e.g. PHPUnit), pretend
        // Zend is installed so the call flow reaches post().
        if (self::$mock_responses !== null) {
            return true;
        }
        // Also check the config-table mock (cross-process: Behat browser tests).
        // Cache result in a static variable to avoid repeated DB calls on each invocation.
        if (function_exists('get_config')) {
            static $is_mock_active = null;
            if ($is_mock_active === null) {
                $is_mock_active = get_config('report_unasus', 'behat_tcc_mock_tcc_definition_service') !== false;
            }
            if ($is_mock_active) {
                return true;
            }
        }
        return $this->ZendInstalled;
    }

    /**
     * @param string $external_url Endereço do sistema de TCC
     * @param string $consumer_key Consumer Key utilizado pela aplicação para realizar a autenticação
     */
    function __construct($external_url, $consumer_key) {
        global $CFG; // Garante acesso às configurações do Moodle
        $zendpath = $CFG->libdir . '/zend/Zend/Loader/Autoloader.php';
        $this->ZendInstalled = file_exists($zendpath);
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
        $this->client = $this->ZendInstalled ? new Zend_Http_Client($this->url) : null;
    }

    /**
     * @param int $tcc_definition_id Id do Tcc Definition
     * @return mixed
     */
    public function get_tcc_definition($tcc_definition_id) {

        $params = array(
            'consumer_key' => $this->consumer_key,
            'tcc_definition_id' => $tcc_definition_id
        );

        $json = $this->post('/tcc_definition_service', $params);
        $object = json_decode($json);

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

    public function get_report_data_tcc($user_ids) {

        $params = array(
            'consumer_key' => $this->consumer_key,
            'user_ids' => $user_ids
        );

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
        if (function_exists('get_config')) {
            $config_key = 'behat_tcc_mock_' . ltrim($path, '/');
            $mock = get_config('report_unasus', $config_key);
            if ($mock !== false) {
                return $mock;
            }
        }

        /*
         * Solução  para enviar via post array do php
         * http://php.net/manual/pt_BR/function.http-build-query.php
         */
        $new_param = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($param, null, '&'));

        $this->client->setUri("{$this->url}{$path}");
        $this->client->setRawData($new_param);

        try {
            $response = $this->client->request('POST');
        } catch (Zend_Http_Client_Adapter_Exception $exception) {
            return false;
        }

        return $response->isSuccessful() ? $response->getBody() : false;
    }

}