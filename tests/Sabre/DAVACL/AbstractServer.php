<?php

require_once 'Sabre/DAV/AbstractServer.php';
require_once 'Sabre/DAVACL/BackendMock.php';
require_once 'Sabre/DAVACL/AuthBackendMock.php';

abstract class Sabre_DAVACL_AbstractServer extends Sabre_DAV_AbstractServer {

    protected $aclBackend;
    protected $authBackend;

    function setUp() {

        $this->aclBackend = new Sabre_DAVACL_BackendMock();
        $this->authBackend = new Sabre_DAVACL_AuthBackendMock();

        parent::setUp();
        $aclPlugin = new Sabre_DAVACL_Plugin($this->aclBackend);
        $this->server->addPlugin($aclPlugin);
        $authPlugin = new Sabre_DAV_Auth_Plugin($this->authBackend,'SabreDAV unittest');
        $this->server->addPlugin($authPlugin);

    }

    protected function getRootNode() {

        $root = new Sabre_DAV_SimpleDirectory('root');
        $principals = new Sabre_DAVACL_PrincipalsCollection($this->authBackend);
        $root->addChild($principals);

        $root->addChild(new Sabre_DAV_FS_Directory($this->tempDir));

        return $root;

    }

    protected function sendRequest($method,$url,$body = null) {

        $serverVars = array(
            'REQUEST_URI'    => $url,
            'REQUEST_METHOD' => $method,
        );

        $request = new Sabre_HTTP_Request($serverVars);
        if ($body) $request->setBody($body);

        $this->server->httpRequest = ($request);
        $this->server->exec();

    }

}

?>
