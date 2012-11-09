<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;

class ApacheTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $backend = new Apache();

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testNoHeader() {

        $server = new DAV\Server();
        $backend = new Apache();
        $backend->authenticate($server,'Realm');

    }

    function testRemoteUser() {

        $backend = new Apache();

        $server = new DAV\Server();
        $request = new HTTP\Request(array(
            'REMOTE_USER' => 'username',
        ));
        $server->httpRequest = $request;

        $this->assertTrue($backend->authenticate($server, 'Realm'));

        $userInfo = 'username';

        $this->assertEquals($userInfo, $backend->getCurrentUser());

    }

}
