<?php declare (strict_types=1);

namespace Sabre\CardDAV;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class ValidateVCardTest extends \PHPUnit_Framework_TestCase {

    /** @var DAV\Server */
    protected $server;
    protected $cardBackend;

    function setUp() {

        $addressbooks = [
            [
                'id'           => 'addressbook1',
                'principaluri' => 'principals/admin',
                'uri'          => 'addressbook1',
            ]
        ];

        $this->cardBackend = new Backend\Mock($addressbooks, []);
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = [
            new AddressBookRoot($principalBackend, $this->cardBackend),
        ];

        $this->server = new DAV\Server($tree, null, null, function(){});

        $this->server->debugExceptions = true;

        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

    }

    function request(ServerRequest $request, $expectedStatus = null): ResponseInterface
    {
        $result = $this->server->handle($request);
        if ($expectedStatus) {

            $realStatus = $result->getStatusCode();

            $msg = '';
            if ($realStatus !== $expectedStatus) {
                $msg = 'Response body: ' .$result->getBody()->getContents();
            }
            $this->assertEquals(
                $expectedStatus,
                $realStatus,
                $msg
            );
        }
        return $result;

    }

    function testCreateFile() {

        $request = new ServerRequest('PUT', '/addressbooks/admin/addressbook1/blabla.vcf');

        $response = $this->request($request);


        $this->assertEquals(415, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testCreateFileValid() {



        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
UID:foo
FN:Firstname LastName
N:LastName;FirstName;;;
END:VCARD
VCF;
        $request = new ServerRequest(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [],
            $vcard
        );

        $response = $this->request($request, 201);

        // The custom Ew header should not be set
        $this->assertEmpty(
            $response->getHeaderLine('X-Sabre-Ew-Gross')
        );
        // Valid, non-auto-fixed responses should contain an ETag.
        $this->assertTrue(
            $response->getHeaderLine('ETag') !== null,
            'We did not receive an etag'
        );


        $expected = [
            'uri'      => 'blabla.vcf',
            'carddata' => $vcard,
            'size'     => strlen($vcard),
            'etag'     => '"' . md5($vcard) . '"',
        ];

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1', 'blabla.vcf'));

    }

    /**
     * This test creates an intentionally broken vCard that vobject is able
     * to automatically repair.
     *
     * @depends testCreateFileValid
     */
    function testCreateVCardAutoFix() {



        // The error in this vcard is that there's not enough semi-colons in N
        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
UID:foo
FN:Firstname LastName
N:LastName;FirstName;;
END:VCARD
VCF;

        $request = new ServerRequest(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [],
            $vcard
        );

        $response = $this->request($request, 201);

        // Auto-fixed vcards should NOT return an etag
        $this->assertEmpty(
            $response->getHeader('ETag')
        );

        // We should have gotten an Ew header
        $this->assertNotEmpty(
            $response->getHeaderLine('X-Sabre-Ew-Gross')
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
            'uri'      => 'blabla.vcf',
            'carddata' => $expectedVCard,
            'size'     => strlen($expectedVCard),
            'etag'     => '"' . md5($expectedVCard) . '"',
        ];

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1', 'blabla.vcf'));

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
    function testCreateVCardStrictFail() {



        // The error in this vcard is that there's not enough semi-colons in N
        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
UID:foo
FN:Firstname LastName
N:LastName;FirstName;;
END:VCARD
VCF;
        $request = new ServerRequest(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [
                'Prefer' => 'handling=strict',
            ],
            $vcard
        );
        $this->request($request, 415);

    }

    function testCreateFileNoUID() {


        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
FN:Firstname LastName
N:LastName;FirstName;;;
END:VCARD
VCF;

        $request = new ServerRequest(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [],
            $vcard
        );

        $response = $this->request($request, 201);

        $foo = $this->cardBackend->getCard('addressbook1', 'blabla.vcf');
        $this->assertTrue(
            strpos($foo['carddata'], 'UID') !== false,
            print_r($foo, true)
        );
    }

    function testCreateFileJson() {

        $request = new ServerRequest(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [],
            '[ "vcard" , [ [ "VERSION", {}, "text", "4.0"], [ "UID" , {}, "text", "foo" ], [ "FN", {}, "text", "FirstName LastName"] ] ]');

        $response = $this->request($request);

        $this->assertEquals(201, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

        $foo = $this->cardBackend->getCard('addressbook1', 'blabla.vcf');
        $this->assertEquals("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:foo\r\nFN:FirstName LastName\r\nEND:VCARD\r\n", $foo['carddata']);

    }

    function testCreateFileVCalendar() {

        $request = new ServerRequest('PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [],
            "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n"
        );

        $response = $this->request($request);

        $this->assertEquals(415, $response->getStatusCode(), 'Incorrect status returned! Full response body: ' . $response->getBody()->getContents());

    }

    function testUpdateFile() {

        $this->cardBackend->createCard('addressbook1', 'blabla.vcf', 'foo');
        $request = new ServerRequest(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );

        $this->request($request, 415);

    }

    function testUpdateFileParsableBody() {

        $this->cardBackend->createCard('addressbook1', 'blabla.vcf', 'foo');
        $body = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:foo\r\nFN:FirstName LastName\r\nEND:VCARD\r\n";
        $request = new ServerRequest(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf',
            [],
            $body
        );


        $this->request($request, 204);

        $expected = [
            'uri'      => 'blabla.vcf',
            'carddata' => $body,
            'size'     => strlen($body),
            'etag'     => '"' . md5($body) . '"',
        ];

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1', 'blabla.vcf'));

    }
}
