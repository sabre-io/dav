<?php

class Sabre_DAVACL_ACLMethodTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testCallback() {

        $acl = new Sabre_DAVACL_Plugin();
        $server = new Sabre_DAV_Server();
        $server->addPlugin($acl);

        $acl->unknownMethod('ACL','test'); 

    }

    /**
     * @expectedException Sabre_DAV_Exception_MethodNotAllowed 
     */
    function testNotSupportedByNode() {

        $tree = array(
            new Sabre_DAV_SimpleDirectory('test'),
        );
        $acl = new Sabre_DAVACL_Plugin();
        $server = new Sabre_DAV_Server($tree);
        $server->httpRequest = new Sabre_HTTP_Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test'); 

    }

    function testSuccessSimple() {

        $tree = array(
            new Sabre_DAVACL_MockACLNode('test',array()),
        );
        $acl = new Sabre_DAVACL_Plugin();
        $server = new Sabre_DAV_Server($tree);
        $server->httpRequest = new Sabre_HTTP_Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $this->assertNull($acl->httpACL('test')); 

    }

    function testSuccessNewACL() {

        $oldACL = array(
            array(
                'principal' => 'principals/test',
                'privilege' => '{DAV:}read',
            )
        );
        $tree = array(
            new Sabre_DAVACL_MockACLNode('test',$oldACL),
        );
        $acl = new Sabre_DAVACL_Plugin();
        $server = new Sabre_DAV_Server($tree);
        $server->httpRequest = new Sabre_HTTP_Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read /></d:privilege></d:grant>
        <d:principal><d:href>/principals/test</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $this->assertNull($acl->httpACL('test')); 

    }
}
