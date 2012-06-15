<?php

namespace Sabre\CalDAV;

require_once 'Sabre/CalDAV/TestUtil.php';

class ServerTest extends \PHPUnit_Framework_TestCase {

    /**
     * The CalDAV server is a simple script that just composes a
     * Sabre\DAV\Server. All we really have to do is check if the setup
     * is done correctly.
     */
    function testSetup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $pdo = TestUtil::getSQLiteDB();
        $server = new Server($pdo);

        $authPlugin = $server->getPlugin('auth');
        $this->assertTrue($authPlugin instanceof \Sabre\DAV\Auth\Plugin);

        $caldavPlugin = $server->getPlugin('caldav');
        $this->assertTrue($caldavPlugin instanceof Plugin);

        $node = $server->tree->getNodeForPath('');
        $this->assertTrue($node instanceof \Sabre\DAV\SimpleCollection);

        $this->assertEquals('root', $node->getName());

    }

}
