<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Sabre\HTTP;

abstract class AbstractServer extends \PHPUnit_Framework_TestCase {

    protected $request;
    /**
     * @var Server
     */
    protected $server;
    protected $tempDir = SABRE_TEMPDIR;

    function setUp() {

        $this->server = new Server(
            $this->getRootNode(),
            function() { return new \GuzzleHttp\Psr7\Response(); },
            new ServerRequest('GET', ''),
            function(ResponseInterface $response) { }
        );

        $this->server->debugExceptions = true;
        $this->deleteTree(SABRE_TEMPDIR, false);
        file_put_contents(SABRE_TEMPDIR . '/test.txt', 'Test contents');
        mkdir(SABRE_TEMPDIR . '/dir');
        file_put_contents(SABRE_TEMPDIR . '/dir/child.txt', 'Child contents');


    }

    function tearDown() {

        $this->deleteTree(SABRE_TEMPDIR, false);

    }

    protected function getRootNode() {

        return new FS\Directory(SABRE_TEMPDIR);

    }

    private function deleteTree($path, $deleteRoot = true) {

        foreach (scandir($path) as $node) {

            if ($node == '.' || $node == '.svn' || $node == '..') continue;
            $myPath = $path . '/' . $node;
            if (is_file($myPath)) {
                unlink($myPath);
            } else {
                $this->deleteTree($myPath);
            }

        }
        if ($deleteRoot) rmdir($path);

    }

}
