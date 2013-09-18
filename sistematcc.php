<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'client.php';

class SistemaTccClient extends Redmine\Client {

    var $api_key = 'consumer_key';
    var $url = 'http://localhost:3000/';

    function __construct() {
        parent::__construct($this->url, $this->api_key);
    }
    
    function _construct($url, $api_key){
        $this->url = $url;
        $this->api_key = $url;
        
        parent::__construct($this->url, $this->api_key);
    }
    
    public function post($path, $data) {
        /* 
         * Solução  para enviar via post array do php
         * http://php.net/manual/pt_BR/function.http-build-query.php
         */
        $data = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($data));
        return parent::post($path, $data);
    }
    
}