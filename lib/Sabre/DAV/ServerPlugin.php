<?php

abstract class Sabre_DAV_ServerPlugin {

    abstract public function initialize(Sabre_DAV_Server $server);
    
    public function getFeatures() {

        return array();

    }

    public function getHTTPMethods() {

        return array();

    }

}

?>
