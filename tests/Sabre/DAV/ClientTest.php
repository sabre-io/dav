<?php

namespace Sabre\DAV;

require_once 'Sabre/DAV/ClientMock.php';

class ClientTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $client = new ClientMock(array(
            'baseUri' => '/',
        ));
        $this->assertInstanceOf('Sabre\DAV\ClientMock', $client);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testConstructNoBaseUri() {

        $client = new ClientMock(array());

    }

}
