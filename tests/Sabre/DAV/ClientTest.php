<?php

namespace Sabre\DAV;

require_once 'Sabre/DAV/ClientMock.php';

class ClientTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $client = new ClientMock(array(
            'baseUri' => '/',
        ));
        $this->assertInstanceOf('Sabre\DAV\ClientMock', $client);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testConstructNoBaseUri() {

        $client = new ClientMock(array());

    }

    function testRequest() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $body,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

        $this->assertEquals(array(
            'statusCode' => 200,
            'headers' => array(
                'content-type' => 'text/plain',
            ),
            'body' => 'Hello there!'
        ), $result);


    }

    function testStreamRequest() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'testing streams');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_INFILE => $body,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_PUT => true,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

        $this->assertEquals(array(
            'statusCode' => 200,
            'headers' => array(
                'content-type' => 'text/plain',
            ),
            'body' => 'Hello there!'
        ), $result);

    }

    function testRequestProxy() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
            'proxy' => 'http://localhost:8000/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_PROXY => 'http://localhost:8000/',
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $body,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

        $this->assertEquals(array(
            'statusCode' => 200,
            'headers' => array(
                'content-type' => 'text/plain',
            ),
            'body' => 'Hello there!'
        ), $result);

    }

    function testRequestCAInfo() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $client->addTrustedCertificates('bla');

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_CAINFO => 'bla',
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $body,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

    }

    function testRequestSslPeer() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $client->setVerifyPeer(true);

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $body,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

    }

    function testRequestAuth() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
            'userName' => 'user',
            'password' => 'password',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
            CURLOPT_USERPWD => 'user:password',
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $body,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

        $this->assertEquals(array(
            'statusCode' => 200,
            'headers' => array(
                'content-type' => 'text/plain',
            ),
            'body' => 'Hello there!'
        ), $result);

    }

    function testRequestAuthBasic() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
            'userName' => 'user',
            'password' => 'password',
            'authType' => Client::AUTH_BASIC,
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => 'user:password',
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $body,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

        $this->assertEquals(array(
            'statusCode' => 200,
            'headers' => array(
                'content-type' => 'text/plain',
            ),
            'body' => 'Hello there!'
        ), $result);

    }

    function testRequestAuthDigest() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
            'userName' => 'user',
            'password' => 'password',
            'authType' => Client::AUTH_DIGEST,
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('POST', 'baz', $body, array('Content-Type' => 'text/plain'));

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => 'user:password',
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $body,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

        $this->assertEquals(array(
            'statusCode' => 200,
            'headers' => array(
                'content-type' => 'text/plain',
            ),
            'body' => 'Hello there!'
        ), $result);

    }

    /**
     * @expectedException \Sabre\HTTP\ClientException
     */
    function testRequestError() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            CURLE_COULDNT_CONNECT,
            "Could not connect, or something"
        );

        $client->request('POST', 'baz', 'sillybody', array('Content-Type' => 'text/plain'));

    }

    function testUnsupportedHTTPError() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 580 blabla",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 42,
                'http_code' => "580"
            ),
            0,
            ""
        );

        $response = $client->request('POST', 'baz', 'sillybody', array('Content-Type' => 'text/plain'));
        $this->assertEquals(580, $response['statusCode']);

    }

    function testGetAbsoluteUrl() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/',
        ));

        $this->assertEquals(
            'http://example.org/foo/bar',
            $client->getAbsoluteUrl('bar')
        );

        $this->assertEquals(
            'http://example.org/bar',
            $client->getAbsoluteUrl('/bar')
        );

        $this->assertEquals(
            'http://example.com/bar',
            $client->getAbsoluteUrl('http://example.com/bar')
        );

    }

    function testOptions() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "DAV: feature1, feature2",
            "",
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 40,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $result = $client->options();
        $this->assertEquals(
            array('feature1', 'feature2'),
            $result
        );

    }

    function testOptionsNoDav() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "",
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 20,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $result = $client->options();
        $this->assertEquals(
            array(),
            $result
        );

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testPropFindNoXML() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "",
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 20,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $client->propfind('', array('{DAV:}foo','{DAV:}bar'));

    }

    function testPropFind() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "",
            "<?xml version=\"1.0\"?>",
            "<d:multistatus xmlns:d=\"DAV:\">",
            "  <d:response>",
            "    <d:href>/foo/bar/</d:href>",
            "    <d:propstat>",
            "      <d:prop>",
            "         <d:foo>hello</d:foo>",
            "      </d:prop>",
            "      <d:status>HTTP/1.1 200 OK</d:status>",
            "    </d:propstat>",
            "    <d:propstat>",
            "      <d:prop>",
            "         <d:bar />",
            "      </d:prop>",
            "      <d:status>HTTP/1.1 404 Not Found</d:status>",
            "    </d:propstat>",
            "  </d:response>",
            "</d:multistatus>",
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 19,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $result = $client->propfind('', array('{DAV:}foo','{DAV:}bar'));

        $this->assertEquals(array(
            '{DAV:}foo' => 'hello',
        ), $result);

        $requestBody = array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<d:propfind xmlns:d="DAV:">',
            '  <d:prop>',
            '    <d:foo/>',
            '    <d:bar/>',
            '  </d:prop>',
            '</d:propfind>',
            ''
        );
        $requestBody = implode("\n", $requestBody);

        $this->assertEquals($requestBody, $client->curlSettings[CURLOPT_POSTFIELDS]);

    }

    /**
     * This was reported in Issue 235.
     *
     * If no '200 Ok' properties are returned, the client will throw an
     * E_NOTICE.
     */
    function testPropFindNo200s() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "",
            "<?xml version=\"1.0\"?>",
            "<d:multistatus xmlns:d=\"DAV:\">",
            "  <d:response>",
            "    <d:href>/foo/bar/</d:href>",
            "    <d:propstat>",
            "      <d:prop>",
            "         <d:bar />",
            "      </d:prop>",
            "      <d:status>HTTP/1.1 404 Not Found</d:status>",
            "    </d:propstat>",
            "  </d:response>",
            "</d:multistatus>",
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 19,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $result = $client->propfind('', array('{DAV:}foo','{DAV:}bar'));

        $this->assertEquals(array(
        ), $result);

    }

    function testPropFindDepth1CustomProp() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "",
            "<?xml version=\"1.0\"?>",
            "<d:multistatus xmlns:d=\"DAV:\" xmlns:x=\"urn:custom\">",
            "  <d:response>",
            "    <d:href>/foo/bar/</d:href>",
            "    <d:propstat>",
            "      <d:prop>",
            "         <d:foo>hello</d:foo>",
            "         <x:bar>world</x:bar>",
            "      </d:prop>",
            "      <d:status>HTTP/1.1 200 OK</d:status>",
            "    </d:propstat>",
            "  </d:response>",
            "</d:multistatus>",
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 19,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $result = $client->propfind('', array('{DAV:}foo','{urn:custom}bar'),1);

        $this->assertEquals(array(
            "/foo/bar/" => array(
                '{DAV:}foo' => 'hello',
                '{urn:custom}bar' => 'world',
            ),
        ), $result);

        $requestBody = array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<d:propfind xmlns:d="DAV:">',
            '  <d:prop xmlns:x="urn:custom">',
            '    <d:foo/>',
            '    <x:bar xmlns:x="urn:custom"/>',
            '  </d:prop>',
            '</d:propfind>',
            ''
        );
        $requestBody = implode("\n", $requestBody);

        $this->assertEquals($requestBody, $client->curlSettings[CURLOPT_POSTFIELDS]);

    }

    function testPropPatch() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "",
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 20,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $client->proppatch('', array(
            '{DAV:}foo' => 'newvalue',
            '{urn:custom}foo' => 'newvalue2',
            '{DAV:}bar' => null,
            '{urn:custom}bar' => null,
        ));

        $requestBody = array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<d:propertyupdate xmlns:d="DAV:" xmlns:x="urn:custom">',
            '  <d:set>',
            '    <d:prop>',
            '      <d:foo>newvalue</d:foo>',
            '    </d:prop>',
            '  </d:set>',
            '  <d:set>',
            '    <d:prop>',
            '      <x:foo xmlns:x="urn:custom">newvalue2</x:foo>',
            '    </d:prop>',
            '  </d:set>',
            '  <d:remove>',
            '    <d:prop>',
            '      <d:bar/>',
            '    </d:prop>',
            '  </d:remove>',
            '  <d:remove>',
            '    <d:prop>',
            '      <x:bar xmlns:x="urn:custom"/>',
            '    </d:prop>',
            '  </d:remove>',
            '</d:propertyupdate>',
            ''
        );
        $requestBody = implode("\n", $requestBody);

        $this->assertEquals($requestBody, $client->curlSettings[CURLOPT_POSTFIELDS]);

    }

    function testHEADRequest() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $result = $client->request('HEAD', 'baz');

        $this->assertEquals('http://example.org/foo/bar/baz', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
            CURLOPT_NOBODY => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array(),
            CURLOPT_POSTREDIR => 3,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_PUT => false,
        ), $client->curlSettings);

    }

    function testPUTRequest() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('PUT', 'bar', $body);

        $this->assertEquals('http://example.org/foo/bar/bar', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array(),
            CURLOPT_INFILE => $body,
            CURLOPT_PUT => true,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

    }

    function testEncoding() {

        $client = new ClientMock(array(
            'baseUri' => 'http://example.org/foo/bar/',
            'encoding' => Client::ENCODING_ALL,
        ));

        $responseBlob = array(
            "HTTP/1.1 200 OK",
            "Content-Type: text/plain",
            "",
            "Hello there!"
        );

        $client->response = array(
            implode("\r\n", $responseBlob),
            array(
                'header_size' => 45,
                'http_code' => 200,
            ),
            0,
            ""
        );

        $body = fopen('php://memory','r+');
        fwrite($body, 'foo');
        rewind($body);

        $result = $client->request('PUT', 'bar', $body);

        $this->assertEquals('http://example.org/foo/bar/bar', $client->url);
        $this->assertEquals(array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array(),
            CURLOPT_ENCODING => 'identity,deflate,gzip',
            CURLOPT_INFILE => $body,
            CURLOPT_PUT => true,
            CURLOPT_POSTREDIR => 3,
        ), $client->curlSettings);

    }
}
