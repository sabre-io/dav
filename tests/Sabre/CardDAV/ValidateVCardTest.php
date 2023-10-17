<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;

class ValidateVCardTest extends \PHPUnit\Framework\TestCase
{
    protected $server;
    protected $cardBackend;

    public function setup(): void
    {
        $addressbooks = [
            [
                'id' => 'addressbook1',
                'principaluri' => 'principals/admin',
                'uri' => 'addressbook1',
            ],
        ];

        $this->cardBackend = new Backend\Mock($addressbooks, []);
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = [
            new AddressBookRoot($principalBackend, $this->cardBackend),
        ];

        $this->server = new DAV\Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;

        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

        $response = new HTTP\ResponseMock();
        $this->server->httpResponse = $response;
    }

    public function request(HTTP\Request $request, $expectedStatus = null)
    {
        $this->server->httpRequest = $request;
        $this->server->exec();

        if ($expectedStatus) {
            $realStatus = $this->server->httpResponse->getStatus();

            $msg = '';
            if ($realStatus !== $expectedStatus) {
                $msg = 'Response body: '.$this->server->httpResponse->getBodyAsString();
            }
            self::assertEquals(
                $expectedStatus,
                $realStatus,
                $msg
            );
        }

        return $this->server->httpResponse;
    }

    public function testCreateFile()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ]);

        $response = $this->request($request);

        self::assertEquals(415, $response->status);
    }

    public function testCreateFileValid()
    {
        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );

        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
UID:foo
FN:Firstname LastName
N:LastName;FirstName;;;
END:VCARD
VCF;
        $request->setBody($vcard);

        $response = $this->request($request, 201);

        // The custom Ew header should not be set
        self::assertNull(
            $response->getHeader('X-Sabre-Ew-Gross')
        );
        // Valid, non-auto-fixed responses should contain an ETag.
        self::assertTrue(
            null !== $response->getHeader('ETag'),
            'We did not receive an etag'
        );

        $expected = [
            'uri' => 'blabla.vcf',
            'carddata' => $vcard,
            'size' => strlen($vcard),
            'etag' => '"'.md5($vcard).'"',
        ];

        self::assertEquals($expected, $this->cardBackend->getCard('addressbook1', 'blabla.vcf'));
    }

    /**
     * This test creates an intentionally broken vCard that vobject is able
     * to automatically repair.
     *
     * @depends testCreateFileValid
     */
    public function testCreateVCardAutoFix()
    {
        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );

        // The error in this vcard is that there's not enough semi-colons in N
        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
UID:foo
FN:Firstname LastName
N:LastName;FirstName;;
END:VCARD
VCF;

        $request->setBody($vcard);

        $response = $this->request($request, 201);

        // Auto-fixed vcards should NOT return an etag
        self::assertNull(
            $response->getHeader('ETag')
        );

        // We should have gotten an Ew header
        self::assertNotNull(
            $response->getHeader('X-Sabre-Ew-Gross')
        );

        $expectedVCard = <<<VCF
BEGIN:VCARD\r
VERSION:4.0\r
UID:foo\r
FN:Firstname LastName\r
N:LastName;FirstName;;;\r
END:VCARD\r

VCF;

        $expected = [
            'uri' => 'blabla.vcf',
            'carddata' => $expectedVCard,
            'size' => strlen($expectedVCard),
            'etag' => '"'.md5($expectedVCard).'"',
        ];

        self::assertEquals($expected, $this->cardBackend->getCard('addressbook1', 'blabla.vcf'));
    }

    /**
     * This test creates an intentionally broken vCard that vobject is able
     * to automatically repair.
     *
     * However, we're supplying a heading asking the server to treat the
     * request as strict, so the server should still let the request fail.
     *
     * @depends testCreateFileValid
     */
    public function testCreateVCardStrictFail()
    {
        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [
                'Prefer' => 'handling=strict',
            ]
        );

        // The error in this vcard is that there's not enough semi-colons in N
        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
UID:foo
FN:Firstname LastName
N:LastName;FirstName;;
END:VCARD
VCF;

        $request->setBody($vcard);
        $this->request($request, 415);
    }

    public function testCreateFileNoUID()
    {
        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );
        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
FN:Firstname LastName
N:LastName;FirstName;;;
END:VCARD
VCF;
        $request->setBody($vcard);

        $response = $this->request($request, 201);

        $foo = $this->cardBackend->getCard('addressbook1', 'blabla.vcf');
        self::assertTrue(
            false !== strpos($foo['carddata'], 'UID'),
            print_r($foo, true)
        );
    }

    public function testCreateFileJson()
    {
        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );
        $request->setBody('[ "vcard" , [ [ "VERSION", {}, "text", "4.0"], [ "UID" , {}, "text", "foo" ], [ "FN", {}, "text", "FirstName LastName"] ] ]');

        $response = $this->request($request);

        self::assertEquals(201, $response->status, 'Incorrect status returned! Full response body: '.$response->getBodyAsString());

        $foo = $this->cardBackend->getCard('addressbook1', 'blabla.vcf');
        self::assertEquals("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:foo\r\nFN:FirstName LastName\r\nEND:VCARD\r\n", $foo['carddata']);
    }

    public function testCreateFileVCalendar()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ]);
        $request->setBody("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        self::assertEquals(415, $response->status, 'Incorrect status returned! Full response body: '.$response->getBodyAsString());
    }

    public function testUpdateFile()
    {
        $this->cardBackend->createCard('addressbook1', 'blabla.vcf', 'foo');
        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );

        $response = $this->request($request, 415);
    }

    public function testUpdateFileParsableBody()
    {
        $this->cardBackend->createCard('addressbook1', 'blabla.vcf', 'foo');
        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );

        $body = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:foo\r\nFN:FirstName LastName\r\nEND:VCARD\r\n";
        $request->setBody($body);

        $response = $this->request($request, 204);

        $expected = [
            'uri' => 'blabla.vcf',
            'carddata' => $body,
            'size' => strlen($body),
            'etag' => '"'.md5($body).'"',
        ];

        self::assertEquals($expected, $this->cardBackend->getCard('addressbook1', 'blabla.vcf'));
    }
}
