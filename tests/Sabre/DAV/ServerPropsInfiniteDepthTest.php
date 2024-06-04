<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerPropsInfiniteDepthTest extends AbstractServerTestCase
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
        mkdir(\Sabre\TestUtil::SABRE_TEMPDIR.'/col/col');
        file_put_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'col/col/test.txt', 'Test contents');
        $this->server->addPlugin(new Locks\Plugin(new Locks\Backend\File(\Sabre\TestUtil::SABRE_TEMPDIR.'/.locksdb')));
        $this->server->enablePropfindDepthInfinity = true;
    }

    public function teardown(): void
    {
        parent::tearDown();
        if (file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'../.locksdb')) {
            unlink(\Sabre\TestUtil::SABRE_TEMPDIR.'../.locksdb');
        }
    }

    private function sendRequest($body)
    {
        $request = new HTTP\Request('PROPFIND', '/', ['Depth' => 'infinity']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
    }

    public function testPropFindEmptyBody()
    {
        $this->sendRequest('');

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Incorrect status received. Full response body: '.$bodyAsString);

        self::assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type' => ['application/xml; charset=utf-8'],
            'DAV' => ['1, 3, extended-mkcol, 2'],
            'Vary' => ['Brief,Prefer'],
        ],
            $this->response->getHeaders()
        );

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", 'xmlns\\1="urn:DAV"', $bodyAsString);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        self::assertEquals('/', (string) $data, 'href element should have been /');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        // 8 resources are to be returned: /, col, col/col, col/col/test.txt, dir, dir/child.txt, test.txt and test2.txt
        self::assertEquals(8, count($data));
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

        $body = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->getStatus(), $body);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", 'xmlns\\1="urn:DAV"', $body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry');
        self::assertEquals(16, count($data), 'We expected sixteen \'d:lockentry\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope');
        self::assertEquals(16, count($data), 'We expected sixteen \'d:lockscope\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype');
        self::assertEquals(16, count($data), 'We expected sixteen \'d:locktype\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:shared');
        self::assertEquals(8, count($data), 'We expected eight \'d:shared\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:exclusive');
        self::assertEquals(8, count($data), 'We expected eight \'d:exclusive\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype/d:write');
        self::assertEquals(16, count($data), 'We expected sixteen \'d:write\' tags');
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
        self::assertEquals(8, count($data), 'We expected eight \'d:lockdiscovery\' tags');
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
        self::assertEquals(8, count($val), $body);
        self::assertEquals('HTTP/1.1 404 Not Found', (string) $val[0]);
    }

    public function testFilesThatAreSiblingsOfDirectoriesShouldBeReportedAsFiles()
    {
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:resourcetype />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);
        $body = $this->getSanitizedBody();
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');
        $pathTests = [
            '/d:multistatus',
            '/d:multistatus/d:response',
            '/d:multistatus/d:response/d:href',
            '/d:multistatus/d:response/d:propstat',
            '/d:multistatus/d:response/d:propstat/d:status',
            '/d:multistatus/d:response/d:propstat/d:prop',
            '/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype',
        ];

        $hrefPaths = [];

        foreach ($pathTests as $test) {
            self::assertTrue(true == count($xml->xpath($test)), 'We expected the '.$test.' element to appear in the response, we got: '.$body);

            if ('/d:multistatus/d:response/d:href' === $test) {
                foreach ($xml->xpath($test) as $thing) {
                    /* @var \SimpleXMLElement $thing */
                    $hrefPaths[] = strip_tags($thing->asXML());
                }
            } elseif ('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype' === $test) {
                $count = 0;
                foreach ($xml->xpath($test) as $thing) {
                    /* @var \SimpleXMLElement $thing */
                    if ('.txt' !== substr($hrefPaths[$count], -4)) {
                        self::assertEquals('<d:resourcetype><d:collection/></d:resourcetype>', $thing->asXML(), 'Path '.$hrefPaths[$count].' is not reported as a directory');
                    } else {
                        self::assertEquals('<d:resourcetype/>', $thing->asXML(), 'Path '.$hrefPaths[$count].' is not reported as a file');
                    }

                    ++$count;
                }
            }
        }

        $val = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        self::assertEquals(8, count($val), $body);
        self::assertEquals('HTTP/1.1 200 OK', (string) $val[0]);
    }
}
