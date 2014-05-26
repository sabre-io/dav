<?php

namespace Sabre\DAV\PropertyStorage;

class PluginTest extends \Sabre\DAVServerTest {

    protected $backend;

    function setUp() {

        parent::setUp();
        $this->backend = new Backend\Mock();
        $this->server->addPlugin(
            new Plugin(
                $this->backend
            )
        );

    }

    function testSetProperty() {

        $this->server->updateProperties('', ['{DAV:}displayname' => 'hi']);
        $this->assertEquals([
            '' => [
                '{DAV:}displayname' => 'hi',
            ]
        ], $this->backend->data);

    }

    /**
     * @depends testSetProperty
     */
    function testGetProperty() {

        $this->testSetProperty();
        $result = $this->server->getProperties('', ['{DAV:}displayname']);

        $this->assertEquals([
            '{DAV:}displayname' => 'hi',
        ], $result);

    }

    /**
     * @depends testSetProperty
     */
    function testDelete() {

        $this->testSetProperty();
        $this->server->emit('afterUnbind', ['']);
        $this->assertEquals([],$this->backend->data);


    }

}
