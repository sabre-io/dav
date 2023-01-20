<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV;

abstract class AbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return AbstractBackend
     */
    abstract public function getBackend();

    public function testSetup()
    {
        $backend = $this->getBackend();
        $this->assertInstanceOf('Sabre\\DAV\\Locks\\Backend\\AbstractBackend', $backend);
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

        $this->assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);

        $this->assertEquals(1, count($locks));
        $this->assertEquals('Sinterklaas', $locks[0]->owner);
        $this->assertEquals('someuri', $locks[0]->uri);
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

        $this->assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri/child', false);

        $this->assertEquals(1, count($locks));
        $this->assertEquals('Sinterklaas', $locks[0]->owner);
        $this->assertEquals('someuri', $locks[0]->uri);
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

        $this->assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri/child', false);

        $this->assertEquals(0, count($locks));
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

        $this->assertTrue($backend->lock('someuri/child', $lock));

        $locks = $backend->getLocks('someuri/child', false);
        $this->assertEquals(1, count($locks));

        $locks = $backend->getLocks('someuri', false);
        $this->assertEquals(0, count($locks));

        $locks = $backend->getLocks('someuri', true);
        $this->assertEquals(1, count($locks));
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

        $this->assertTrue($backend->lock('someuri', $lock));
        /* Second time */

        $lock->owner = 'Santa Clause';
        $this->assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);

        $this->assertEquals(1, count($locks));

        $this->assertEquals('Santa Clause', $locks[0]->owner);
        $this->assertEquals('someuri', $locks[0]->uri);
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

        $this->assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        $this->assertEquals(1, count($locks));

        $this->assertTrue($backend->unlock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        $this->assertEquals(0, count($locks));
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

        $this->assertTrue($backend->lock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        $this->assertEquals(1, count($locks));

        $lock->token = 'SOME-OTHER-TOKEN';
        $this->assertFalse($backend->unlock('someuri', $lock));

        $locks = $backend->getLocks('someuri', false);
        $this->assertEquals(1, count($locks));
    }
}
