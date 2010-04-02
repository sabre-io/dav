<?php

class Sabre_DAV_Auth_MockBackend extends Sabre_DAV_Auth_Backend_Abstract {

    protected $currentUser;

    function authenticate(Sabre_DAV_Server $server, $realm) {

        if ($realm=='failme') throw new Sabre_DAV_Exception_NotAuthenticated('deliberate fail'); 

        $this->currentUser = array(
            'userId' => 'admin',
        );
        return true;

    }

    function getCurrentUser() {

        return $this->currentUser;

    }

    function getUsers() {

        return array(
            array(
                'userId' => 'admin',
            ),
            array(
                'userId' => 'user1',
            ),
        );

    }

}
