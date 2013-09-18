<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'client.php';

class SistemaTccClient extends Redmine\Client {

    var $url = '';
    
    function _construct($url){
        $this->url = $url;

        parent::__construct($this->url, '');
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