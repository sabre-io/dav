<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Mock;

class PluginTest extends \Sabre\DAVServerTest {

    protected $setupSharing = true;

    function setUpTree() {

        $this->tree[] = new Mock\ShareableNode();

    }

    function testProperties() {

        $result = $this->server->getPropertiesForPath(
            'shareable',
            ['{DAV:}share-mode']
        );

        $expected = [];

        $this->assertEquals(
            $expected,
            $result
        );

    }

}
