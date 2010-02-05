<?php

class Sabre_DAVACL_AuthBackendMock extends Sabre_DAV_Auth_Backend_Abstract {

    protected $users = array(
        array(
            'userId' => 'testuser1',
        ),
    );

    function authenticate(Sabre_DAV_Server $server, $realm) {

        return $this->users[0]; 

    }

    function getUsers() {

        return $this->users;

    }

}
