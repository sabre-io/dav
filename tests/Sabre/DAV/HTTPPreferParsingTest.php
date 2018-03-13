<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\HTTP;

class HTTPPreferParsingTest extends \Sabre\DAVServerTest {

    function assertParseResult($input, $expected) {

        $httpRequest = new ServerRequest('GET', '/foo', [
            'Prefer' => $input,
        ]);

        $server = new Server(null, null, null, function(){});
        $server->handle($httpRequest);
        $this->assertEquals(
            $expected,
            $server->getHTTPPrefer()
        );

    }

    function testParseSimple() {

        $this->assertParseResult(
            'return-asynch',
            [
                'respond-async' => true,
                'return'        => null,
                'handling'      => null,
                'wait'          => null,
            ]
        );

    }

    function testParseValue() {

        $this->assertParseResult(
            'wait=10',
            [
                'respond-async' => false,
                'return'        => null,
                'handling'      => null,
                'wait'          => '10',
            ]
        );

    }

    function testParseMultiple() {

        $this->assertParseResult(
            'return-minimal, strict,lenient',
            [
                'respond-async' => false,
                'return'        => 'minimal',
                'handling'      => 'lenient',
                'wait'          => null,
            ]
        );

    }

    function testParseWeirdValue() {

        $this->assertParseResult(
            'BOOOH',
            [
                'respond-async' => false,
                'return'        => null,
                'handling'      => null,
                'wait'          => null,
                'boooh'         => true,
            ]
        );
    }

    function testBrief() {

        $httpRequest = new ServerRequest('GET', '/foo', [
            'Brief' => 't',
        ]);

        $server = new Server(null, null, null, function(){});
        $server->handle($httpRequest);

        $this->assertEquals([
            'respond-async' => false,
            'return'        => 'minimal',
            'handling'      => null,
            'wait'          => null,
        ], $server->getHTTPPrefer());

    }

    /**
     * propfindMinimal
     *
     * @return void
     */
    function testpropfindMinimal() {

        $request = new ServerRequest('PROPFIND', '/', [
            'Prefer' => 'return-minimal',
        ], <<<BLA
<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
    <d:prop>
        <d:something />
        <d:resourcetype />
    </d:prop>
</d:propfind>
BLA
        );

        $response = $this->request($request);

        $body = $response->getBody()->getContents();

        $this->assertEquals(207, $response->getStatusCode(), $body);

        $this->assertTrue(strpos($body, 'resourcetype') !== false, $body);
        $this->assertTrue(strpos($body, 'something') === false, $body);

    }

    function testproppatchMinimal() {

        $request = new ServerRequest('PROPPATCH', '/', ['Prefer' => 'return-minimal'], <<<BLA
<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:">
    <d:set>
        <d:prop>
            <d:something>nope!</d:something>
        </d:prop>
    </d:set>
</d:propertyupdate>
BLA
        );

        $this->server->on('propPatch', function($path, PropPatch $propPatch) {

            $propPatch->handle('{DAV:}something', function($props) {
                return true;
            });

        });

        $response = $this->request($request);
        $this->assertEmpty($response->getBody()->getContents());
        $this->assertEquals(204, $response->getStatusCode());

    }

    function testproppatchMinimalError() {

        $request = new ServerRequest('PROPPATCH', '/', ['Prefer' => 'return-minimal'], <<<BLA
<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:">
    <d:set>
        <d:prop>
            <d:something>nope!</d:something>
        </d:prop>
    </d:set>
</d:propertyupdate>
BLA
        );

        $response = $this->request($request);

        $responseBody = $response->getBody()->getContents();

        $this->assertEquals(207, $response->getStatusCode());
        $this->assertTrue(strpos($responseBody, 'something') !== false);
        $this->assertTrue(strpos($responseBody, '403 Forbidden') !== false, $responseBody);

    }
}
