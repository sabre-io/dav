<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerPropsTest extends AbstractServer
{
    protected function getRootNode()
    {
        return new FSExt\Directory(\Sabre\TestUtil::SABRE_TEMPDIR);
    }

    public function setup(): void
    {
        if (file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'../.sabredav')) {
            unlink(\Sabre\TestUtil::SABRE_TEMPDIR.'../.sabredav');
        }
        parent::setUp();
        file_put_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/test2.txt', 'Test contents2');
        mkdir(\Sabre\TestUtil::SABRE_TEMPDIR.'/col');
        file_put_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'col/test.txt', 'Test contents');
        $this->server->addPlugin(new Locks\Plugin(new Locks\Backend\File(\Sabre\TestUtil::SABRE_TEMPDIR.'/.locksdb')));
    }

    public function teardown(): void
    {
        parent::tearDown();
        if (file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'../.locksdb')) {
            unlink(\Sabre\TestUtil::SABRE_TEMPDIR.'../.locksdb');
        }
    }

    private function sendRequest($body, $path = '/', $headers = ['Depth' => '0'])
    {
        $request = new HTTP\Request('PROPFIND', $path, $headers, $body);

        $this->server->httpRequest = $request;
        $this->server->exec();
    }

    public function testPropFindEmptyBody()
    {
        $this->sendRequest('');
        self::assertEquals(207, $this->response->status);

        self::assertEquals([
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type' => ['application/xml; charset=utf-8'],
                'DAV' => ['1, 3, extended-mkcol, 2'],
                'Vary' => ['Brief,Prefer'],
            ],
            $this->response->getHeaders()
         );

        $xml = $this->getSanitizedBodyAsXml();
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        self::assertEquals('/', (string) $data, 'href element should have been /');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        self::assertEquals(1, count($data));
    }

    public function testPropFindEmptyBodyDepth1Custom()
    {
        // Add custom property to nodes.
        $this->server->on('propFind', function (PropFind $propFind, INode $node) {
            $propFind->set('{DAV:}ishidden', '1');
        });

        $this->sendRequest('', '/', ['Depth' => 1]);
        self::assertEquals(207, $this->response->status);

        self::assertEquals([
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type' => ['application/xml; charset=utf-8'],
                'DAV' => ['1, 3, extended-mkcol, 2'],
                'Vary' => ['Brief,Prefer'],
            ],
            $this->response->getHeaders()
         );

        $xml = $this->getSanitizedBodyAsXml();
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:ishidden');
        self::assertEquals(5, count($data), 'Response should contain 5 elements');

        foreach ($data as $prop) {
            self::assertEquals('1', $prop[0]);
        }
    }

    public function testPropFindEmptyBodyFile()
    {
        $this->sendRequest('', '/test2.txt', []);
        self::assertEquals(207, $this->response->status);

        self::assertEquals([
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type' => ['application/xml; charset=utf-8'],
                'DAV' => ['1, 3, extended-mkcol, 2'],
                'Vary' => ['Brief,Prefer'],
            ],
            $this->response->getHeaders()
         );

        $xml = $this->getSanitizedBodyAsXml();
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        self::assertEquals('/test2.txt', (string) $data, 'href element should have been /test2.txt');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength');
        self::assertEquals(1, count($data));
    }

    public function testSupportedLocks()
    {
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supportedlock />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $xml = $this->getSanitizedBodyAsXml();
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry');
        self::assertEquals(2, count($data), 'We expected two \'d:lockentry\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope');
        self::assertEquals(2, count($data), 'We expected two \'d:lockscope\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype');
        self::assertEquals(2, count($data), 'We expected two \'d:locktype\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:shared');
        self::assertEquals(1, count($data), 'We expected a \'d:shared\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:exclusive');
        self::assertEquals(1, count($data), 'We expected a \'d:exclusive\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype/d:write');
        self::assertEquals(2, count($data), 'We expected two \'d:write\' tags');
    }

    public function testLockDiscovery()
    {
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:lockdiscovery />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $xml = $this->getSanitizedBodyAsXml();
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:lockdiscovery');
        self::assertEquals(1, count($data), 'We expected a \'d:lockdiscovery\' tag');
    }

    public function testUnknownProperty()
    {
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:macaroni />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);
        $body = $this->getSanitizedBody();
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');
        $pathTests = [
            '/d:multistatus',
            '/d:multistatus/d:response',
            '/d:multistatus/d:response/d:propstat',
            '/d:multistatus/d:response/d:propstat/d:status',
            '/d:multistatus/d:response/d:propstat/d:prop',
            '/d:multistatus/d:response/d:propstat/d:prop/d:macaroni',
        ];
        foreach ($pathTests as $test) {
            self::assertTrue(true == count($xml->xpath($test)), 'We expected the '.$test.' element to appear in the response, we got: '.$body);
        }

        $val = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        self::assertEquals(1, count($val), $body);
        self::assertEquals('HTTP/1.1 404 Not Found', (string) $val[0]);
    }

    public function testParsePropPatchRequest()
    {
        $body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/NS/test">
  <d:set><d:prop><s:someprop>somevalue</s:someprop></d:prop></d:set>
  <d:remove><d:prop><s:someprop2 /></d:prop></d:remove>
  <d:set><d:prop><s:someprop3>removeme</s:someprop3></d:prop></d:set>
  <d:remove><d:prop><s:someprop3 /></d:prop></d:remove>
</d:propertyupdate>';

        $result = $this->server->xml->parse($body);
        self::assertEquals([
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
            '{http://sabredav.org/NS/test}someprop2' => null,
            '{http://sabredav.org/NS/test}someprop3' => null,
        ], $result->properties);
    }
}
