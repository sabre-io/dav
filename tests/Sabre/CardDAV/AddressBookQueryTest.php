<?php

namespace Sabre\CardDAV;

use Sabre\HTTP;
use Sabre\DAV;

require_once 'Sabre/CardDAV/AbstractPluginTest.php';
require_once 'Sabre/HTTP/ResponseMock.php';

class AddressBookQueryTest extends AbstractPluginTest {

    function testQuery() {

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
            'HTTP_DEPTH' => '1',
        ));

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid" />
    </c:filter>
</c:addressbook-query>'
            );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Incorrect status code. Full response body:' . $response->body);

        // using the client for parsing
        $client = new DAV\Client(array('baseUri'=>'/'));

        $result = $client->parseMultiStatus($response->body);

        $this->assertEquals(array(
            '/addressbooks/user1/book1/card1' => array(
                200 => array(
                    '{DAV:}getetag' => '"' . md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD") . '"',
                ),
             ),
            '/addressbooks/user1/book1/card2' => array(
                404 => array(
                    '{DAV:}getetag' => null,
                ),
            )
        ), $result);


    }

    function testQueryDepth0() {

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1/card1',
            'HTTP_DEPTH' => '0',
        ));

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid" />
    </c:filter>
</c:addressbook-query>'
            );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Incorrect status code. Full response body:' . $response->body);

        // using the client for parsing
        $client = new DAV\Client(array('baseUri'=>'/'));

        $result = $client->parseMultiStatus($response->body);

        $this->assertEquals(array(
            '/addressbooks/user1/book1/card1' => array(
                200 => array(
                    '{DAV:}getetag' => '"' . md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD") . '"',
                ),
             ),
        ), $result);


    }

    function testQueryNoMatch() {

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
            'HTTP_DEPTH' => '1',
        ));

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="email" />
    </c:filter>
</c:addressbook-query>'
            );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Incorrect status code. Full response body:' . $response->body);

        // using the client for parsing
        $client = new DAV\Client(array('baseUri'=>'/'));

        $result = $client->parseMultiStatus($response->body);

        $this->assertEquals(array(), $result);

    }

    function testQueryLimit() {

        $request = new HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI' => '/addressbooks/user1/book1',
            'HTTP_DEPTH' => '1',
        ));

        $request->setBody(
'<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid" />
    </c:filter>
    <c:limit><c:nresults>1</c:nresults></c:limit>
</c:addressbook-query>'
            );

        $response = new HTTP\ResponseMock();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;

        $this->server->exec();

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $response->status, 'Incorrect status code. Full response body:' . $response->body);

        // using the client for parsing
        $client = new DAV\Client(array('baseUri'=>'/'));

        $result = $client->parseMultiStatus($response->body);

        $this->assertEquals(array(
            '/addressbooks/user1/book1/card1' => array(
                200 => array(
                    '{DAV:}getetag' => '"' . md5("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD"). '"',
                ),
             ),
        ), $result);


    }

}
