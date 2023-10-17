<?php

declare(strict_types=1);

namespace Sabre\DAV;

class PropPatchTest extends \PHPUnit\Framework\TestCase
{
    protected $propPatch;

    public function setup(): void
    {
        $this->propPatch = new PropPatch([
            '{DAV:}displayname' => 'foo',
        ]);
        self::assertEquals(['{DAV:}displayname' => 'foo'], $this->propPatch->getMutations());
    }

    public function testHandleSingleSuccess()
    {
        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function ($value) use (&$hasRan) {
            $hasRan = true;
            self::assertEquals('foo', $value);

            return true;
        });

        self::assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 200], $result);

        self::assertTrue($hasRan);
    }

    public function testHandleSingleFail()
    {
        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function ($value) use (&$hasRan) {
            $hasRan = true;
            self::assertEquals('foo', $value);

            return false;
        });

        self::assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 403], $result);

        self::assertTrue($hasRan);
    }

    public function testHandleSingleCustomResult()
    {
        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function ($value) use (&$hasRan) {
            $hasRan = true;
            self::assertEquals('foo', $value);

            return 201;
        });

        self::assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 201], $result);

        self::assertTrue($hasRan);
    }

    public function testHandleSingleDeleteSuccess()
    {
        $hasRan = false;

        $this->propPatch = new PropPatch(['{DAV:}displayname' => null]);
        $this->propPatch->handle('{DAV:}displayname', function ($value) use (&$hasRan) {
            $hasRan = true;
            self::assertNull($value);

            return true;
        });

        self::assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 204], $result);

        self::assertTrue($hasRan);
    }

    public function testHandleNothing()
    {
        $hasRan = false;

        $this->propPatch->handle('{DAV:}foobar', function ($value) use (&$hasRan) {
            $hasRan = true;
        });

        self::assertFalse($hasRan);
    }

    /**
     * @depends testHandleSingleSuccess
     */
    public function testHandleRemaining()
    {
        $hasRan = false;

        $this->propPatch->handleRemaining(function ($mutations) use (&$hasRan) {
            $hasRan = true;
            self::assertEquals(['{DAV:}displayname' => 'foo'], $mutations);

            return true;
        });

        self::assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 200], $result);

        self::assertTrue($hasRan);
    }

    public function testHandleRemainingNothingToDo()
    {
        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function () {});
        $this->propPatch->handleRemaining(function ($mutations) use (&$hasRan) {
            $hasRan = true;
        });

        self::assertFalse($hasRan);
    }

    public function testSetResultCode()
    {
        $this->propPatch->setResultCode('{DAV:}displayname', 201);
        self::assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 201], $result);
    }

    public function testSetResultCodeFail()
    {
        $this->propPatch->setResultCode('{DAV:}displayname', 402);
        self::assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 402], $result);
    }

    public function testSetRemainingResultCode()
    {
        $this->propPatch->setRemainingResultCode(204);
        self::assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 204], $result);
    }

    public function testCommitNoHandler()
    {
        self::assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        self::assertEquals(['{DAV:}displayname' => 403], $result);
    }

    public function testHandlerNotCalled()
    {
        $hasRan = false;

        $this->propPatch->setResultCode('{DAV:}displayname', 402);
        $this->propPatch->handle('{DAV:}displayname', function ($value) use (&$hasRan) {
            $hasRan = true;
        });

        $this->propPatch->commit();

        // The handler is not supposed to have ran
        self::assertFalse($hasRan);
    }

    public function testDependencyFail()
    {
        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
        ]);

        $calledA = false;
        $calledB = false;

        $propPatch->handle('{DAV:}a', function () use (&$calledA) {
            $calledA = true;

            return false;
        });
        $propPatch->handle('{DAV:}b', function () use (&$calledB) {
            $calledB = true;

            return false;
        });

        $result = $propPatch->commit();
        self::assertTrue($calledA);
        self::assertFalse($calledB);

        self::assertFalse($result);

        self::assertEquals([
            '{DAV:}a' => 403,
            '{DAV:}b' => 424,
        ], $propPatch->getResult());
    }

    public function testHandleSingleBrokenResult()
    {
        $this->expectException('UnexpectedValueException');
        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
        ]);

        $propPatch->handle('{DAV:}a', function () {
            return [];
        });
        $propPatch->commit();
    }

    public function testHandleMultiValueSuccess()
    {
        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $calledA = false;

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function ($properties) use (&$calledA) {
            $calledA = true;
            self::assertEquals([
                '{DAV:}a' => 'foo',
                '{DAV:}b' => 'bar',
                '{DAV:}c' => null,
            ], $properties);

            return true;
        });
        $result = $propPatch->commit();
        self::assertTrue($calledA);
        self::assertTrue($result);

        self::assertEquals([
            '{DAV:}a' => 200,
            '{DAV:}b' => 200,
            '{DAV:}c' => 204,
        ], $propPatch->getResult());
    }

    public function testHandleMultiValueFail()
    {
        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $calledA = false;

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function ($properties) use (&$calledA) {
            $calledA = true;
            self::assertEquals([
                '{DAV:}a' => 'foo',
                '{DAV:}b' => 'bar',
                '{DAV:}c' => null,
            ], $properties);

            return false;
        });
        $result = $propPatch->commit();
        self::assertTrue($calledA);
        self::assertFalse($result);

        self::assertEquals([
            '{DAV:}a' => 403,
            '{DAV:}b' => 403,
            '{DAV:}c' => 403,
        ], $propPatch->getResult());
    }

    public function testHandleMultiValueCustomResult()
    {
        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $calledA = false;

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function ($properties) use (&$calledA) {
            $calledA = true;
            self::assertEquals([
                '{DAV:}a' => 'foo',
                '{DAV:}b' => 'bar',
                '{DAV:}c' => null,
            ], $properties);

            return [
                '{DAV:}a' => 201,
                '{DAV:}b' => 204,
            ];
        });
        $result = $propPatch->commit();
        self::assertTrue($calledA);
        self::assertFalse($result);

        self::assertEquals([
            '{DAV:}a' => 201,
            '{DAV:}b' => 204,
            '{DAV:}c' => 500,
        ], $propPatch->getResult());
    }

    public function testHandleMultiValueBroken()
    {
        $this->expectException('UnexpectedValueException');
        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function ($properties) {
            return 'hi';
        });
        $propPatch->commit();
    }
}
