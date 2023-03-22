<?php

defined('MOODLE_INTERNAL') || die;


// Carrega o zend framework
if (file_exists("Zend/Loader/Autoloader.php")) {
    include 'Zend/Loader/Autoloader.php';
    Zend_Loader_Autoloader::autoload('Zend_Loader');
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

    public function getZendInstalled() {
        return $this->ZendInstalled;
    }

    /**
     * @param string $external_url Endereço do sistema de TCC
     * @param string $consumer_key Consumer Key utilizado pela aplicação para realizar a autenticação
     */
    function __construct($external_url, $consumer_key) {
        $this->ZendInstalled = file_exists("Zend/Loader/Autoloader.php");
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