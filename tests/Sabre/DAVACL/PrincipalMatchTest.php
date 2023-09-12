<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\HTTP\Request;

class PrincipalMatchTest extends \Sabre\DAVServerTest
{
    public $setupACL = true;
    public $autoLogin = 'user1';

    public function testPrincipalMatch()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<principal-match xmlns="DAV:">
    <self />
</principal-match>
XML;

        $request = new Request('REPORT', '/principals', ['Content-Type' => 'application/xml']);
        $request->setBody($xml);

        $response = $this->request($request, 207);

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
    <d:status>HTTP/1.1 200 OK</d:status>
    <d:href>/principals/user1</d:href>
</d:multistatus>
XML;

        self::assertXmlStringEqualsXmlString(
            $expected,
            $response->getBodyAsString()
        );
    }

    public function testPrincipalMatchProp()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<principal-match xmlns="DAV:">
    <self />
    <prop>
      <resourcetype />
    </prop>
</principal-match>
XML;

        $request = new Request('REPORT', '/principals', ['Content-Type' => 'application/xml']);
        $request->setBody($xml);

        $response = $this->request($request, 207);

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
    <d:status>HTTP/1.1 200 OK</d:status>
    <d:href>/principals/user1/</d:href>
    <d:propstat>
        <d:prop>
            <d:resourcetype><d:principal/></d:resourcetype>
        </d:prop>
        <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
</d:multistatus>
XML;

        self::assertXmlStringEqualsXmlString(
            $expected,
            $response->getBodyAsString()
        );
    }

    public function testPrincipalMatchPrincipalProperty()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<principal-match xmlns="DAV:">
    <principal-property>
        <principal-URL />
    </principal-property>
    <prop>
      <resourcetype />
    </prop>
</principal-match>
XML;

        $request = new Request('REPORT', '/principals', ['Content-Type' => 'application/xml']);
        $request->setBody($xml);

        $response = $this->request($request, 207);

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
    <d:status>HTTP/1.1 200 OK</d:status>
    <d:href>/principals/user1/</d:href>
    <d:propstat>
        <d:prop>
            <d:resourcetype><d:principal/></d:resourcetype>
        </d:prop>
        <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
</d:multistatus>
XML;

        self::assertXmlStringEqualsXmlString(
            $expected,
            $response->getBodyAsString()
        );
    }
}
