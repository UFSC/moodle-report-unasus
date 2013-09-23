<?php

// Carrega o zend framework
include 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::autoload('Zend_Loader');

class SistemaTccClient {

    private $url;
    private $consumer_key;
    private $client;

    /**
     * @param $external_url Endereço do sistema de TCC
     * @param $consumer_key Consumer Key utilizado pela aplicação para realizar a autenticação
     */
    function __construct($external_url, $consumer_key) {

        $url = parse_url($external_url);

        $new_url = "{$url['scheme']}://{$url['host']}";
        if (!empty($url['port'])) {
            $new_url .= ":{$url['port']}";
        }

        $this->url = $new_url;
        $this->consumer_key = $consumer_key;
        $this->client = new Zend_Http_Client($this->url);
    }

    /**
     * @param $tcc_definition_id
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
     * @param $user_ids
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

    private function post($path, $data) {
        /*
         * Solução  para enviar via post array do php
         * http://php.net/manual/pt_BR/function.http-build-query.php
         */
        $data = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($data));

        $this->client->setUri("{$this->url}{$path}");
        $this->client->setRawData($data);

        try {
            $response = $this->client->request('POST');
        } catch (Zend_Http_Client_Adapter_Exception $exception) {
            return false;
        }

        return $response->isSuccessful() ? $response->getBody() : false;
    }

}