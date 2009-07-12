<?php

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class Sabre_DAV_ServerPropsTest extends Sabre_DAV_AbstractServer {

    protected function getRootNode() {

        return new Sabre_DAV_FSExt_Directory($this->tempDir);

    }

    function setUp() {

        parent::setUp();
        file_put_contents($this->tempDir . '/test2.txt', 'Test contents2');
        mkdir($this->tempDir . '/col');
        file_put_contents($this->tempDir . 'col/test.txt', 'Test contents');
        $this->server->addPlugin(new Sabre_DAV_Locks_Plugin());

    }

    private function sendRequest($body) {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'PROPFIND',
            'HTTP_DEPTH'          => '0',
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody($body);

        $this->server->httpRequest = ($request);
        $this->server->exec();

    }

    public function testPropFindEmptyBody() {
       
        $this->sendRequest("");

        $this->assertEquals('HTTP/1.1 207 Multi-Status',$this->response->status);

        $this->assertEquals(array(
                'Content-Type' => 'application/xml; charset=utf-8',
            ),
            $this->response->headers
         );

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        $this->assertEquals('/',(string)$data,'href element should have been /');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        $this->assertEquals(1,count($data));

    }

    function testSupportedLocks() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supportedlock />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);
        
        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry');
        $this->assertEquals(2,count($data),'We expected two \'d:lockentry\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope');
        $this->assertEquals(2,count($data),'We expected two \'d:lockscope\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype');
        $this->assertEquals(2,count($data),'We expected two \'d:locktype\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:shared');
        $this->assertEquals(1,count($data),'We expected a \'d:shared\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:exclusive');
        $this->assertEquals(1,count($data),'We expected a \'d:exclusive\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype/d:write');
        $this->assertEquals(2,count($data),'We expected two \'d:write\' tags');
    }

    function testLockDiscovery() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:lockdiscovery />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);
        
        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:lockdiscovery');
        $this->assertEquals(1,count($data),'We expected a \'d:lockdiscovery\' tag');

    }

    function testUnknownProperty() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:macaroni />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);
        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');
        $pathTests = array(
            '/d:multistatus',
            '/d:multistatus/d:response',
            '/d:multistatus/d:response/d:propstat',
            '/d:multistatus/d:response/d:propstat/d:status',
            '/d:multistatus/d:response/d:propstat/d:prop',
            '/d:multistatus/d:response/d:propstat/d:prop/d:macaroni',
        );
        foreach($pathTests as $test) {
            $this->assertTrue(count($xml->xpath($test))==true,'We expected the ' . $test . ' element to appear in the response, we got: ' . $body);
        }

        $val = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(2,count($val),$body);
        $this->assertEquals('HTTP/1.1 404 Not Found',(string)$val[1]);

    }

    public function testPropPatch() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'PROPPATCH',
        );

        $body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://www.rooftopsolutions.nl/testnamespace">
  <d:set><d:prop><s:someprop>somevalue</s:someprop></d:prop></d:set>
</d:propertyupdate>';

        $request = new Sabre_HTTP_Request($serverVars);
        $request->setBody($body);

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
                'Content-Type' => 'application/xml; charset=utf-8',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 207 Multi-Status',$this->response->status,'We got the wrong status. Full XML response: ' . $this->response->body);

    }

    public function testPropPatchAndFetch() {

        $this->testPropPatch();
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:s="http://www.rooftopsolutions.nl/testnamespace">
  <d:prop>
    <s:someprop />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');
        $xml->registerXPathNamespace('s','http://www.rooftopsolutions.nl/testnamespace');
 
        $xpath='//d:prop/s:someprop';
        $result = $xml->xpath($xpath);
        $this->assertEquals('somevalue',(string)$result[0]);


    }

}

?>
