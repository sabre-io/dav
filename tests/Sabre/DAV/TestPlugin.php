<?php

namespace Sabre\DAV;

class TestPlugin extends ServerPlugin {

    public $beforeMethod;

    function getFeatures() {

        return array('drinking');

    }

    function getHTTPMethods($uri) {

        return array('BEER','WINE');

    }

    function initialize(Server $server) {

        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'));

    }

    function beforeMethod($method) {

        $this->beforeMethod = $method;
        return true;

    }

}
