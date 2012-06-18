<?php

namespace Sabre\DAV\Auth;

use Sabre\DAV;

class MockBackend implements IBackend {

    protected $currentUser;

    /**
     * @param Sabre\DAV\Server $server
     * @param string $realm
     * @throws Sabre\DAV\Exception\NotAuthenticated
     */
    function authenticate(DAV\Server $server, $realm) {

        if ($realm=='failme') throw new DAV\Exception\NotAuthenticated('deliberate fail');

        $this->currentUser = 'admin';

    }

    function setCurrentUser($user) {

        $this->currentUser = $user;

    }

    function getCurrentUser() {

        return $this->currentUser;

    }

}
