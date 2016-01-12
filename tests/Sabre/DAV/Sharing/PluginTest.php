<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Mock;
use Sabre\DAV\Xml\Property;

class PluginTest extends \Sabre\DAVServerTest {

    protected $setupSharing = true;

    function setUpTree() {

        $this->tree[] = new Mock\SharedNode(
            'shareable',
            Plugin::ACCESS_READWRITE
        );

    }

    function testProperties() {

        $result = $this->server->getPropertiesForPath(
            'shareable',
            ['{DAV:}share-access']
        );

        $expected = [
            [
                200 => [
                    '{DAV:}share-access' => new Property\ShareAccess(Plugin::ACCESS_READWRITE)
                ],
                404 => [],
                'href' => 'shareable',
            ]
        ];

        $this->assertEquals(
            $expected,
            $result
        );

    }

}
