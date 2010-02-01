<?php

class Sabre_DAVACL_AuthBackendMock extends Sabre_DAV_Auth_Backend_Abstract {

    protected $users = array(
        array(
            'userId' => 'testuser1',
        ),
    );

    function getDigestHash($userId) {

        return null;

    }

    function getUsers() {

        return $this->users;

    }

}
