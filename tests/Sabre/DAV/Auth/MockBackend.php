<?php

class Sabre_DAV_Auth_MockBackend implements Sabre_DAV_Auth_IBackend {

    protected $currentUser;

    /**
     * @param Sabre_DAV_Server $server
     * @param string $realm
     * @throws Sabre_DAV_Exception_NotAuthenticated
     */
    function authenticate(Sabre_DAV_Server $server, $realm) {

        if ($realm=='failme') throw new Sabre_DAV_Exception_NotAuthenticated('deliberate fail');

        $this->currentUser = 'admin';

    }

    function setCurrentUser($user) {

        $this->currentUser = $user;

    }

    function getCurrentUser() {

        return $this->currentUser;

    }

}
