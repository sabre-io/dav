<?php

declare(strict_types=1);

namespace Sabre\DAV\PropertyStorage;

class PluginTest extends \Sabre\AbstractDAVServerTestCase
{
    protected $backend;
    protected $plugin;

    protected $setupFiles = true;

    public function setup(): void
    {
        parent::setUp();
        $this->backend = new Backend\Mock();
        $this->plugin = new Plugin(
            $this->backend
        );

        $this->server->addPlugin($this->plugin);
    }

    public function testGetInfo()
    {
        self::assertArrayHasKey(
            'name',
            $this->plugin->getPluginInfo()
        );
    }

    public function testSetProperty()
    {
        $this->server->updateProperties('', ['{DAV:}displayname' => 'hi']);
        self::assertEquals([
            '' => [
                '{DAV:}displayname' => 'hi',
            ],
        ], $this->backend->data);
    }

    /**
     * @depends testSetProperty
     */
    public function testGetProperty()
    {
        $this->testSetProperty();
        $result = $this->server->getProperties('', ['{DAV:}displayname']);

        self::assertEquals([
            '{DAV:}displayname' => 'hi',
        ], $result);
    }

    /**
     * @depends testSetProperty
     */
    public function testDeleteProperty()
    {
        $this->testSetProperty();
        $this->server->emit('afterUnbind', ['']);
        self::assertEquals([], $this->backend->data);
    }

    public function testMove()
    {
        $this->server->tree->getNodeForPath('files')->createFile('source');
        $this->server->updateProperties('files/source', ['{DAV:}displayname' => 'hi']);

        $request = new \Sabre\HTTP\Request('MOVE', '/files/source', ['Destination' => '/files/dest']);
        self::assertHTTPStatus(201, $request);

        $result = $this->server->getProperties('/files/dest', ['{DAV:}displayname']);

        self::assertEquals([
            '{DAV:}displayname' => 'hi',
        ], $result);

        $this->server->tree->getNodeForPath('files')->createFile('source');
        $result = $this->server->getProperties('/files/source', ['{DAV:}displayname']);

        self::assertEquals([], $result);
    }

    /**
     * @depends testDeleteProperty
     */
    public function testSetPropertyInFilteredPath()
    {
        $this->plugin->pathFilter = function ($path) {
            return false;
        };

        $this->server->updateProperties('', ['{DAV:}displayname' => 'hi']);
        self::assertEquals([], $this->backend->data);
    }

    /**
     * @depends testSetPropertyInFilteredPath
     */
    public function testGetPropertyInFilteredPath()
    {
        $this->testSetPropertyInFilteredPath();
        $result = $this->server->getProperties('', ['{DAV:}displayname']);

        self::assertEquals([], $result);
    }
}
