<?php

namespace Sabre\DAV;

class TestPlugin extends ServerPlugin {

    public $beforeMethod;

    function getFeatures() {

        return ['drinking'];

    }

    function getHTTPMethods($uri) {

        return ['BEER','WINE'];

    }

    function initialize(Server $server) {

        $server->on('beforeMethod', [$this,'beforeMethod']);

    }

    function beforeMethod($method) {

        $this->beforeMethod = $method;
        return true;

    }

}
