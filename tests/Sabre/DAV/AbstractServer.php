<?php

require_once 'Sabre/HTTP/ResponseMock.php';

abstract class Sabre_DAV_AbstractServer extends PHPUnit_Framework_TestCase {

    protected $response;
    protected $request;
    protected $server;
    protected $tempDir = 'temp/';

    function setUp() {

        $this->response = new Sabre_HTTP_ResponseMock();
        $tree = new Sabre_DAV_ObjectTree($this->getRootNode());
        $this->server = new Sabre_DAV_Server($tree);
        $this->server->httpResponse = $this->response;
        file_put_contents($this->tempDir . '/test.txt', 'Test contents');

    }

    function tearDown() {

        $this->deleteTree($this->tempDir,false);

    }

    protected function getRootNode() {

        return new Sabre_DAV_FS_Directory($this->tempDir);

    }

    private function deleteTree($path,$deleteRoot = true) {

        foreach(scandir($path) as $node) {

            if ($node[0]=='.') continue;
            $myPath = $path.'/'. $node;
            if (is_file($myPath)) {
                unlink($myPath);
            } else {
                $this->deleteTree($myPath);
            }

        }
        if ($deleteRoot) rmdir($path);

    }

}

?>
