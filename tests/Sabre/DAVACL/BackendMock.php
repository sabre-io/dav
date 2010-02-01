<?php

class Sabre_DAVACL_BackendMock extends Sabre_DAVACL_Backend_Abstract {

    protected $acl = array();

    function getPrivilegesForUser($uri,$userId) {

        echo $uri, $userId, "\n";
        print_r($this->acl);

        return isset($this->acl[$uri][$userId])?$this->acl[$uri][$userId]:array(); 

    }

    function getACL($uri) {

        return isset($this->acl[$uri])?$this->acl[$uri]:array();

    }

    function setACL($uri,array $privileges) {

        $this->acl[$uri] = $privileges;

    }

}
