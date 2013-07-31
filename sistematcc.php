<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'client.php';

class SistemaTccBase extends Redmine\Client {

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
    
}