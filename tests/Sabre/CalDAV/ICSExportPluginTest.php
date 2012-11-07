<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\VObject;
use Sabre\DAVACL;

require_once 'Sabre/CalDAV/TestUtil.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class ICSExportPluginTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $p = new ICSExportPlugin();
        $s = new DAV\Server();
        $s->addPlugin($p);

    }

    function testBeforeMethod() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = TestUtil::getBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = new HTTP\Request(array(
            'QUERY_STRING' => 'export',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $this->assertFalse($p->beforeMethod('GET','UUID-123467?export'));

        $this->assertEquals('HTTP/1.1 200 OK',$s->httpResponse->status);
        $this->assertEquals(array(
            'Content-Type' => 'text/calendar',
        ), $s->httpResponse->headers);

        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(5,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->CALSCALE));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertTrue(strpos((string)$obj->PRODID, DAV\Version::VERSION)!==false);
        $this->assertEquals(1,count($obj->VTIMEZONE));
        $this->assertEquals(1,count($obj->VEVENT));

    }
    function testBeforeMethodNoVersion() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = TestUtil::getBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);

        $s->addPlugin($p);
        $s->addPlugin(new Plugin());

        $h = new HTTP\Request(array(
            'QUERY_STRING' => 'export',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        DAV\Server::$exposeVersion = false;
        $this->assertFalse($p->beforeMethod('GET','UUID-123467?export'));
        DAV\Server::$exposeVersion = true;

        $this->assertEquals('HTTP/1.1 200 OK',$s->httpResponse->status);
        $this->assertEquals(array(
            'Content-Type' => 'text/calendar',
        ), $s->httpResponse->headers);

        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(5,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->CALSCALE));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertFalse(strpos((string)$obj->PRODID, DAV\Version::VERSION)!==false);
        $this->assertEquals(1,count($obj->VTIMEZONE));
        $this->assertEquals(1,count($obj->VEVENT));

    }

    function testBeforeMethodNoGET() {

        $p = new ICSExportPlugin();

        $s = new DAV\Server();
        $s->addPlugin($p);

        $this->assertNull($p->beforeMethod('POST','UUID-123467?export'));

    }

    function testBeforeMethodNoExport() {

        $p = new ICSExportPlugin();

        $s = new DAV\Server();
        $s->addPlugin($p);

        $this->assertNull($p->beforeMethod('GET','UUID-123467'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NeedPrivileges
     */
    function testACLIntegrationBlocked() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = TestUtil::getBackend();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());
        $s->addPlugin(new DAVACL\Plugin());

        $h = new HTTP\Request(array(
            'QUERY_STRING' => 'export',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $p->beforeMethod('GET','UUID-123467?export');

    }

    function testACLIntegrationNotBlocked() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $cbackend = TestUtil::getBackend();
        $pbackend = new DAVACL\PrincipalBackend\Mock();

        $props = array(
            'uri'=>'UUID-123467',
            'principaluri' => 'admin',
            'id' => 1,
        );
        $tree = array(
            new Calendar($cbackend,$props),
            new DAVACL\PrincipalCollection($pbackend),
        );

        $p = new ICSExportPlugin();

        $s = new DAV\Server($tree);
        $s->addPlugin($p);
        $s->addPlugin(new Plugin());
        $s->addPlugin(new DAVACL\Plugin());
        $s->addPlugin(new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'SabreDAV'));

        // Forcing login
        $s->getPlugin('acl')->adminPrincipals = array('principals/admin');

        $h = new HTTP\Request(array(
            'QUERY_STRING' => 'export',
            'REQUEST_URI' => '/UUID-123467',
            'REQUEST_METHOD' => 'GET',
        ));

        $s->httpRequest = $h;
        $s->httpResponse = new HTTP\ResponseMock();

        $s->exec();

        $this->assertEquals('HTTP/1.1 200 OK',$s->httpResponse->status,'Invalid status received. Response body: '. $s->httpResponse->body);
        $this->assertEquals(array(
            'Content-Type' => 'text/calendar',
        ), $s->httpResponse->headers);

        $obj = VObject\Reader::read($s->httpResponse->body);

        $this->assertEquals(5,count($obj->children()));
        $this->assertEquals(1,count($obj->VERSION));
        $this->assertEquals(1,count($obj->CALSCALE));
        $this->assertEquals(1,count($obj->PRODID));
        $this->assertEquals(1,count($obj->VTIMEZONE));
        $this->assertEquals(1,count($obj->VEVENT));

    }
}
