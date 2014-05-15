<?php

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;

abstract class AbstractPDOTest extends \PHPUnit_Framework_TestCase {

    /**
     * Should return an instance of \PDO with the current tables initialized,
     * and some test records.
     */
    abstract function getPDO();

    function getBackend() {

        return new PDO($this->getPDO());

    }

    function testPropFind() {

        $backend = $this->getBackend();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals('Directory', $propFind->get('{DAV:}displayname'));

    }

    function testPropFindNothingToDo() {

        $backend = $this->getBackend();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $propFind->set('{DAV:}displayname', 'foo');
        $backend->propFind('dir', $propFind);

        $this->assertEquals('foo', $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testPropPatchUpdate() {

        $backend = $this->getBackend();

        $propPatch = new PropPatch(['{DAV:}displayname' => 'bar']);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals('bar', $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testPropPatchRemove() {

        $backend = $this->getBackend();

        $propPatch = new PropPatch(['{DAV:}displayname' => null]);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testDelete() {

        $backend = $this->getBackend();
        $backend->delete('dir');

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }

}
