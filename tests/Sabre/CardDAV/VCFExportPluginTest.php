<?php

require_once 'Sabre/CardDAV/TestUtil.php';
require_once 'Sabre/DAV/Auth/MockBackend.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class Sabre_CardDAV_VCFExportPlugin extends PHPUnit_Framework_TestCase {

    function testInit() {

        $p = new Sabre_CardDAV_VCFExportPlugin();
        $s = new Sabre_DAV_Server();
        $s->addPlugin($p);

    }

    function testBeforeMethod() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = Sabre_CardDAV_TestUtil::getBackend();
        $pbackend = new Sabre_DAVACL_MockPrincipalBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Sabre_CardDAV_AddressBook($pbackend,$cbackend,$props),
        );

        $p = new Sabre_CardDAV_VCFExportPlugin();

        $s = new Sabre_DAV_Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Sabre_CardDAV_Plugin());

        $h = new Sabre_HTTP_Request(array(
            'QUERY_STRING' => 'export',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new Sabre_HTTP_ResponseMock();

        $this->assertFalse($p->beforeMethod('GET','UUID-123467?export'));

        $this->assertEquals('HTTP/1.1 200 OK',$s->httpResponse->status);
        $this->assertEquals(array(
            'Content-Type' => 'text/directory',
        ), $s->httpResponse->headers);

        $obj = Sabre_VObject_Reader::read($s->httpResponse->body);

        $this->assertEquals(7,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertEquals(1,count($obj->ORG));
        $this->assertTrue(strpos((string)$obj->PRODID, Sabre_DAV_Version::VERSION)!==false);
        $this->assertEquals(1,count($obj->FN));
        $this->assertEquals(1,count($obj->N));

    }
    function testBeforeMethodNoVersion() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = Sabre_CardDAV_TestUtil::getBackend();
        $pbackend = new Sabre_DAVACL_MockPrincipalBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Sabre_CardDAV_AddressBook($pbackend,$cbackend,$props),
        );

        $p = new Sabre_CardDAV_VCFExportPlugin();

        $s = new Sabre_DAV_Server($tree);

        $s->addPlugin($p);
        $s->addPlugin(new Sabre_CardDAV_Plugin());

        $h = new Sabre_HTTP_Request(array(
            'QUERY_STRING' => 'export',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new Sabre_HTTP_ResponseMock();

        Sabre_DAV_Server::$exposeVersion = false;
        $this->assertFalse($p->beforeMethod('GET','UUID-123467?export'));
        Sabre_DAV_Server::$exposeVersion = true; 

        $this->assertEquals('HTTP/1.1 200 OK',$s->httpResponse->status);
        $this->assertEquals(array(
            'Content-Type' => 'text/directory',
        ), $s->httpResponse->headers);

        $obj = Sabre_VObject_Reader::read($s->httpResponse->body);

        $this->assertEquals(7,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertEquals(1,count($obj->ORG));
        $this->assertTrue(strpos((string)$obj->PRODID, Sabre_DAV_Version::VERSION)!==false);
        $this->assertEquals(1,count($obj->FN));
        $this->assertEquals(1,count($obj->N));

    }

    function testBeforeMethodNoGET() {

        $p = new Sabre_CardDAV_VCFExportPlugin();

        $s = new Sabre_DAV_Server();
        $s->addPlugin($p);

        $this->assertNull($p->beforeMethod('POST','UUID-123467?export'));

    }

    function testBeforeMethodNoExport() {

        $p = new Sabre_CardDAV_VCFExportPlugin();

        $s = new Sabre_DAV_Server();
        $s->addPlugin($p);

        $this->assertNull($p->beforeMethod('GET','UUID-123467'));

    }

    /**
     * @expectedException Sabre_DAVACL_Exception_NeedPrivileges
     */
    function testACLIntegrationBlocked() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = Sabre_CardDAV_TestUtil::getBackend();
        $pbackend = new Sabre_DAVACL_MockPrincipalBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Sabre_CardDAV_AddressBook($pbackend,$cbackend,$props),
        );

        $p = new Sabre_CardDAV_VCFExportPlugin();

        $s = new Sabre_DAV_Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Sabre_CardDAV_Plugin());
        $s->addPlugin(new Sabre_DAVACL_Plugin());

        $h = new Sabre_HTTP_Request(array(
            'QUERY_STRING' => 'export',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new Sabre_HTTP_ResponseMock();

        $p->beforeMethod('GET','UUID-123467?export');

    }

    function testACLIntegrationNotBlocked() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = Sabre_CardDAV_TestUtil::getBackend();
        $pbackend = new Sabre_DAVACL_MockPrincipalBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Sabre_CardDAV_AddressBook($pbackend,$cbackend,$props),
            new Sabre_DAVACL_PrincipalCollection($pbackend),
        );

        $p = new Sabre_CardDAV_VCFExportPlugin();

        $s = new Sabre_DAV_Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Sabre_CardDAV_Plugin());
        $s->addPlugin(new Sabre_DAVACL_Plugin());
        $s->addPlugin(new Sabre_DAV_Auth_Plugin(new Sabre_DAV_Auth_MockBackend(),'SabreDAV'));

        // Forcing login
        $s->getPlugin('acl')->adminPrincipals = array('principals/admin');

        $h = new Sabre_HTTP_Request(array(
            'QUERY_STRING' => 'export',
            'REQUEST_URI' => '/UUID-123467',
            'REQUEST_METHOD' => 'GET',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new Sabre_HTTP_ResponseMock();

        $s->exec();

        $this->assertEquals('HTTP/1.1 200 OK',$s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $this->assertEquals(array(
            'Content-Type' => 'text/directory',
        ), $s->httpResponse->headers);

        $obj = Sabre_VObject_Reader::read($s->httpResponse->body);

        $this->assertEquals(7,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertEquals(1,count($obj->ORG));
        $this->assertEquals(1,count($obj->FN));
        $this->assertEquals(1,count($obj->N));

    }
}
