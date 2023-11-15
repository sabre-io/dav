<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

use PHPUnit\Framework\TestCase;
use Sabre\DAV;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @abstract
     *
     * @return AbstractBackend
     */
    abstract public function getBackend();

    public function testSetup()
    {
        $backend = $this->getBackend();
        self::assertInstanceOf('Sabre\\DAV\\Locks\\Backend\\AbstractBackend', $backend);
    }

    /**
     * @depends testSetup
     */
    public function testGetLocks()
    {
        $backend = $this->getBackend();

        $lock = new DAV\Locks\LockInfo();
        $lock->owner = 'Sinterklaas';
        $lock->timeout = 60;
        $lock->created = time();
        $lock->token = 'MY-UNIQUE-TOKEN';
        $lock->uri = 'someuri';

        self::assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);

        self::assertEquals(1, count($locks));
        self::assertEquals('Sinterklaas', $locks[0]->owner);
        self::assertEquals('someuri', $locks[0]->uri);
    }

    /**
     * @depends testGetLocks
     */
    public function testGetLocksParent()
    {
        $backend = $this->getBackend();

        $lock = new DAV\Locks\LockInfo();
        $lock->owner = 'Sinterklaas';
        $lock->timeout = 60;
        $lock->created = time();
        $lock->depth = DAV\Server::DEPTH_INFINITY;
        $lock->token = 'MY-UNIQUE-TOKEN';

        self::assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri/child', false);

        self::assertEquals(1, count($locks));
        self::assertEquals('Sinterklaas', $locks[0]->owner);
        self::assertEquals('someuri', $locks[0]->uri);
    }

    /**
     * @depends testGetLocks
     */
    public function testGetLocksParentDepth0()
    {
        $backend = $this->getBackend();

        $lock = new DAV\Locks\LockInfo();
        $lock->owner = 'Sinterklaas';
        $lock->timeout = 60;
        $lock->created = time();
        $lock->depth = 0;
        $lock->token = 'MY-UNIQUE-TOKEN';

        self::assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri/child', false);

        self::assertEquals(0, count($locks));
    }

    public function testGetLocksChildren()
    {
        $backend = $this->getBackend();

        $lock = new DAV\Locks\LockInfo();
        $lock->owner = 'Sinterklaas';
        $lock->timeout = 60;
        $lock->created = time();
        $lock->depth = 0;
        $lock->token = 'MY-UNIQUE-TOKEN';

        self::assertTrue($backend->lock('someuri/child', $lock));

        $locks = $backend->getLocks('someuri/child', false);
        self::assertEquals(1, count($locks));

        $locks = $backend->getLocks('someuri', false);
        self::assertEquals(0, count($locks));

        $locks = $backend->getLocks('someuri', true);
        self::assertEquals(1, count($locks));
    }

    /**
     * @depends testGetLocks
     */
    public function testLockRefresh()
    {
        $backend = $this->getBackend();

        $lock = new DAV\Locks\LockInfo();
        $lock->owner = 'Sinterklaas';
        $lock->timeout = 60;
        $lock->created = time();
        $lock->token = 'MY-UNIQUE-TOKEN';

        self::assertTrue($backend->lock('someuri', $lock));
        /* Second time */

        $lock->owner = 'Santa Clause';
        self::assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);

        self::assertEquals(1, count($locks));

        self::assertEquals('Santa Clause', $locks[0]->owner);
        self::assertEquals('someuri', $locks[0]->uri);
    }

    /**
     * @depends testGetLocks
     */
    public function testUnlock()
    {
        $backend = $this->getBackend();

        $lock = new DAV\Locks\LockInfo();
        $lock->owner = 'Sinterklaas';
        $lock->timeout = 60;
        $lock->created = time();
        $lock->token = 'MY-UNIQUE-TOKEN';

        self::assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        self::assertEquals(1, count($locks));

        self::assertTrue($backend->unlock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        self::assertEquals(0, count($locks));
    }

    /**
     * @depends testUnlock
     */
    public function testUnlockUnknownToken()
    {
        $backend = $this->getBackend();

        $lock = new DAV\Locks\LockInfo();
        $lock->owner = 'Sinterklaas';
        $lock->timeout = 60;
        $lock->created = time();
        $lock->token = 'MY-UNIQUE-TOKEN';

        self::assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        self::assertEquals(1, count($locks));

        $lock->token = 'SOME-OTHER-TOKEN';
        self::assertFalse($backend->unlock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        self::assertEquals(1, count($locks));
    }
}
