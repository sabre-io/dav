<?php

namespace Sabre\DAV;

class PropPatchTest extends \PHPUnit_Framework_TestCase {

    protected $propPatch;

    public function setUp() {

        $this->propPatch = new PropPatch([
            '{DAV:}displayname' => 'foo',
        ]);

    }

    public function testHandleSuccess() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function($value) use (&$hasRan) {
            $hasRan = true;
            $this->assertEquals('foo', $value);
            return true;
        });

        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 200], $result);

        $this->assertTrue($hasRan);

    }
    public function testHandleNothing() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}foobar', function($value) use (&$hasRan) {
            $hasRan = true;
        });

        $this->assertFalse($hasRan);

    }

    public function testHandleRemaining() {

        $hasRan = false;

        $this->propPatch->handleRemaining(function($mutations) use (&$hasRan) {
            $hasRan = true;
            $this->assertEquals(['{DAV:}displayname' => 'foo'], $mutations);
            return true;
        });

        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 200], $result);

        $this->assertTrue($hasRan);

    }
    public function testHandleRemainingNothingToDo() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function() {} );
        $this->propPatch->handleRemaining(function($mutations) use (&$hasRan) {
            $hasRan = true;
        });

        $this->assertFalse($hasRan);

    }

    public function testSetResultCode() {

        $this->propPatch->setResultCode('{DAV:}displayname', 201);
        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 201], $result);

    }

    public function testSetResultCodeFail() {

        $this->propPatch->setResultCode('{DAV:}displayname', 402);
        $this->assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 402], $result);

    }

    public function testSetRemainingResultCode() {

        $this->propPatch->setRemainingResultCode(204);
        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 204], $result);

    }

    public function testCommitNoHandler() {

        $this->assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 403], $result);

    }

    public function testHandlerNotCalled() {

        $hasRan = false;

        $this->propPatch->setResultCode('{DAV:}displayname', 402);
        $this->propPatch->handle('{DAV:}displayname', function($value) use (&$hasRan) {
            $hasRan = true;
        });

        $this->propPatch->commit();

        // The handler is not supposed to have ran
        $this->assertFalse($hasRan); 

    }

}
