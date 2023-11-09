<?php

declare(strict_types=1);

namespace Sabre\DAV;

use PHPUnit\Framework\TestCase;
use Sabre\HTTP;
use Sabre\HTTP\ResponseMock;
use Sabre\TestUtil;

abstract class AbstractServerTestCase extends TestCase
{
    /**
     * @var ResponseMock
     */
    protected $response;
    protected $request;
    /**
     * @var Server
     */
    protected $server;
    protected $tempDir = TestUtil::SABRE_TEMPDIR;

    public function setup(): void
    {
        $this->response = new ResponseMock();
        $this->server = new Server($this->getRootNode());
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->httpResponse = $this->response;
        $this->server->debugExceptions = true;
        $this->deleteTree(TestUtil::SABRE_TEMPDIR, false);
        file_put_contents(TestUtil::SABRE_TEMPDIR.'/test.txt', 'Test contents');
        mkdir(TestUtil::SABRE_TEMPDIR.'/dir');
        file_put_contents(TestUtil::SABRE_TEMPDIR.'/dir/child.txt', 'Child contents');
    }

    public function teardown(): void
    {
        $this->deleteTree(TestUtil::SABRE_TEMPDIR, false);
    }

    protected function getRootNode()
    {
        return new FS\Directory(TestUtil::SABRE_TEMPDIR);
    }

    protected function getSanitizedBody()
    {
        return preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", 'xmlns\\1="urn:DAV"', $this->response->getBodyAsString());
    }

    protected function getSanitizedBodyAsXml()
    {
        return simplexml_load_string($this->getSanitizedBody());
    }

    private function deleteTree($path, $deleteRoot = true)
    {
        foreach (scandir($path) as $node) {
            if ('.' == $node || '.svn' == $node || '..' == $node) {
                continue;
            }
            $myPath = $path.'/'.$node;
            if (is_file($myPath)) {
                unlink($myPath);
            } else {
                $this->deleteTree($myPath);
            }
        }
        if ($deleteRoot) {
            rmdir($path);
        }
    }
}
