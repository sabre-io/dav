<?php

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class Sabre_DAV_ServerPropsTest extends Sabre_DAV_AbstractServer {

    protected function getRootNode() {

        return new Sabre_DAV_FSExt_Directory(SABRE_TEMPDIR);

    }

    function setUp() {

        if (file_exists(SABRE_TEMPDIR.'../.sabredav')) unlink(SABRE_TEMPDIR.'../.sabredav');
        parent::setUp();
        file_put_contents(SABRE_TEMPDIR . '/test2.txt', 'Test contents2');
        mkdir(SABRE_TEMPDIR . '/col');
        file_put_contents(SABRE_TEMPDIR . 'col/test.txt', 'Test contents');
        $this->server->addPlugin(new Sabre_DAV_Locks_Plugin(new Sabre_DAV_Locks_Backend_File(SABRE_TEMPDIR . '/.locksdb')));

    }

    function tearDown() {

        parent::tearDown();
        if (file_exists(SABRE_TEMPDIR.'../.locksdb')) unlink(SABRE_TEMPDIR.'../.locksdb');

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

    /**
     * Provide values for the following test.
     */
    public function propfindMaxDepthProvider() {

        return array(
            // infinity request: we expect that the server returns resources deep to the less value between the request and the maximum allowed
            array('infinity', 'infinity', 6),   // the complete tree is returned
            array('infinity', 0, 0),
            array('infinity', 1, 1),
            array('infinity', -3, Sabre_DAV_Server::DEFAULT_DEPTH),   // server configured with an invalid value, falls back to the default
            array('infinity', 2, 2),
            // limited depth request (depth = 4): we expect that the server returns resources deep to the less value between the request and the maximum allowed
            array(4, 'infinity', 4),
            array(4, 0, 0),
            array(4, 1, 1),
            array(4, -3, Sabre_DAV_Server::DEFAULT_DEPTH),   // server configured with an invalid value, falls back to the default
            array(4, 9, 4),
            // negative depth request: request contains an invalid depth, so the server falls back to the default depth, regardless of its configuration
            array(-2, 'infinity', Sabre_DAV_Server::DEFAULT_DEPTH),
            array(-2, 3, Sabre_DAV_Server::DEFAULT_DEPTH),
            // 0 depth request: we expect that the server returns only the root path (which has depth = 0), regardless of its configuration
            array(0, 'infinity', 0),
            array(0, 2, 0),
            array(0, null, 0)   // server non configured, falls back to the default
        );
    }

    /**
     * Test a PROPFIND request with different depths.
     *
     * @param $requestDepth the depth requested by the client
     * @param $maxDepth the maximum allowed depth as configured on the server
     * @param $expectedDepth the expected depth of the response
     *
     * @covers Sabre_DAV_Server::httpPropfind
     * @dataProvider propfindMaxDepthProvider
     */
    function testDeepPropfind($requestDepth, $maxDepth, $expectedDepth) {

        // prepare the FS tree, adding nested resources; each directory contains only a subdirectory and a file
        $depthTestRoot = '/depthTest';
        $depthTestPath = SABRE_TEMPDIR . "{$depthTestRoot}";

        mkdir($depthTestPath);
        file_put_contents("{$depthTestPath}/depth1.txt", '');

        mkdir("{$depthTestPath}/depth1");
        file_put_contents("{$depthTestPath}/depth1/depth2.txt", '');

        mkdir("{$depthTestPath}/depth1/depth2");
        file_put_contents("{$depthTestPath}/depth1/depth2/depth3.txt", '');

        mkdir("{$depthTestPath}/depth1/depth2/depth3");
        file_put_contents("{$depthTestPath}/depth1/depth2/depth3/depth4.txt", '');

        mkdir("{$depthTestPath}/depth1/depth2/depth3/depth4");
        file_put_contents("{$depthTestPath}/depth1/depth2/depth3/depth4/depth5.txt", '');

        mkdir("{$depthTestPath}/depth1/depth2/depth3/depth4/depth5");
        file_put_contents("{$depthTestPath}/depth1/depth2/depth3/depth4/depth5/depth6.txt", '');

        // execute the request
        $serverVars = array(
            'REQUEST_URI' => $depthTestRoot,
            'PATH_INFO'   => $depthTestRoot,
            'REQUEST_METHOD' => 'PROPFIND',
            'HTTP_DEPTH' => (string) $requestDepth
        );

        $request = new Sabre_HTTP_Request($serverVars);
        $this->server->setMaxPropfindDepth($maxDepth);
        $this->server->httpRequest = $request;
        $this->server->exec();

        // extract the resource path returned by the server
        $resources = array_map(function($item) { return (string) $item; }, simplexml_load_string($this->response->body)->xpath('/d:multistatus/d:response/d:href'));

        // calculate the max reached depth
        $actualDepth = 0;
        foreach ($resources as $res)
        {
            // remove the root path
            $res = str_replace($depthTestRoot, null, $res);

            // remove the trailing slash
            $res = preg_replace('/\/$/', null, $res);

            // remove the starting slash
            $res = preg_replace('/^\//', null, $res);

            // calculate the depth
            $depth = empty($res) ? 0 : count(explode('/', $res));

            if ($depth > $actualDepth)
            {
                $actualDepth = $depth;
            }
        }

        $this->assertEquals($expectedDepth, $actualDepth);
    }

    public function testPropFindEmptyBody() {

        $this->sendRequest("");

        $this->assertEquals('HTTP/1.1 207 Multi-Status',$this->response->status);

        $this->assertEquals(array(
                'Content-Type' => 'application/xml; charset=utf-8',
                'DAV' => '1, 3, extended-mkcol, 2',
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
        $this->assertEquals(1,count($val),$body);
        $this->assertEquals('HTTP/1.1 404 Not Found',(string)$val[0]);

    }

    /**
     * @covers Sabre_DAV_Server::parsePropPatchRequest
     */
    public function testParsePropPatchRequest() {

        $body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/NS/test">
  <d:set><d:prop><s:someprop>somevalue</s:someprop></d:prop></d:set>
  <d:remove><d:prop><s:someprop2 /></d:prop></d:remove>
  <d:set><d:prop><s:someprop3>removeme</s:someprop3></d:prop></d:set>
  <d:remove><d:prop><s:someprop3 /></d:prop></d:remove>
</d:propertyupdate>';

        $result = $this->server->parsePropPatchRequest($body);
        $this->assertEquals(array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
            '{http://sabredav.org/NS/test}someprop2' => null,
            '{http://sabredav.org/NS/test}someprop3' => null,
            ), $result);

    }

    /**
     * @covers Sabre_DAV_Server::updateProperties
     */
    public function testUpdateProperties() {

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/test2.txt',$props);

        $this->assertEquals(array(
            '200' => array('{http://sabredav.org/NS/test}someprop' => null),
            'href' => '/test2.txt',
        ), $result);

    }

    /**
     * @covers Sabre_DAV_Server::updateProperties
     * @depends testUpdateProperties
     */
    public function testUpdatePropertiesProtected() {

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
            '{DAV:}getcontentlength' => 50,
        );

        $result = $this->server->updateProperties('/test2.txt',$props);

        $this->assertEquals(array(
            '424' => array('{http://sabredav.org/NS/test}someprop' => null),
            '403' => array('{DAV:}getcontentlength' => null),
            'href' => '/test2.txt',
        ), $result);

    }

    /**
     * @covers Sabre_DAV_Server::updateProperties
     * @depends testUpdateProperties
     */
    public function testUpdatePropertiesFail1() {

        $dir = new Sabre_DAV_PropTestDirMock('updatepropsfalse');
        $objectTree = new Sabre_DAV_ObjectTree($dir);
        $this->server->tree = $objectTree;

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/',$props);

        $this->assertEquals(array(
            '403' => array('{http://sabredav.org/NS/test}someprop' => null),
            'href' => '/',
        ), $result);

    }

    /**
     * @covers Sabre_DAV_Server::updateProperties
     * @depends testUpdateProperties
     */
    public function testUpdatePropertiesFail2() {

        $dir = new Sabre_DAV_PropTestDirMock('updatepropsarray');
        $objectTree = new Sabre_DAV_ObjectTree($dir);
        $this->server->tree = $objectTree;

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/',$props);

        $this->assertEquals(array(
            '402' => array('{http://sabredav.org/NS/test}someprop' => null),
            'href' => '/',
        ), $result);

    }

    /**
     * @covers Sabre_DAV_Server::updateProperties
     * @depends testUpdateProperties
     * @expectedException Sabre_DAV_Exception
     */
    public function testUpdatePropertiesFail3() {

        $dir = new Sabre_DAV_PropTestDirMock('updatepropsobj');
        $objectTree = new Sabre_DAV_ObjectTree($dir);
        $this->server->tree = $objectTree;

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/',$props);

    }

    /**
     * @depends testParsePropPatchRequest
     * @depends testUpdateProperties
     * @covers Sabre_DAV_Server::httpPropPatch
     */
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

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');
        $xml->registerXPathNamespace('bla','http://www.rooftopsolutions.nl/testnamespace');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop');
        $this->assertEquals(1,count($data),'We expected one \'d:prop\' element. Response body: ' . $body);

        $data = $xml->xpath('//bla:someprop');
        $this->assertEquals(1,count($data),'We expected one \'s:someprop\' element. Response body: ' . $body);

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(1,count($data),'We expected one \'s:status\' element. Response body: ' . $body);

        $this->assertEquals('HTTP/1.1 200 OK',(string)$data[0]);

    }

    /**
     * @depends testPropPatch
     */
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
        $xml->registerXPathNamespace('bla','http://www.rooftopsolutions.nl/testnamespace');

        $xpath='//bla:someprop';
        $result = $xml->xpath($xpath);
        $this->assertEquals(1,count($result),'We couldn\'t find our new property in the response. Full response body:' . "\n" . $body);
        $this->assertEquals('somevalue',(string)$result[0],'We couldn\'t find our new property in the response. Full response body:' . "\n" . $body);

    }

}

class Sabre_DAV_PropTestDirMock extends Sabre_DAV_SimpleCollection implements Sabre_DAV_IProperties {

    public $type;

    function __construct($type) {

        $this->type =$type;
        parent::__construct('root');

    }

    function updateProperties($updateProperties) {

        switch($this->type) {
            case 'updatepropsfalse' : return false;
            case 'updatepropsarray' :
                $r = array(402 => array());
                foreach($updateProperties as $k=>$v) $r[402][$k] = null;
                return $r;
            case 'updatepropsobj' :
                return new STDClass();
        }

    }

    function getProperties($requestedPropeties) {

        return array();

    }

}
