<?php

class Sabre_DAV_Auth_MockBackend extends Sabre_DAV_Auth_Backend_Abstract {

    function authenticate(Sabre_DAV_Server $server, $realm) {

        if ($realm=='failme') return false;

        // An empty array == success too
        return array();

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
