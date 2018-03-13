<?php declare (strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;

class IDirectoryTest extends \PHPUnit_Framework_TestCase {

    function testResourceType() {

        $tree = [
            new DirectoryMock('directory')
        ];

        $server = new DAV\Server($tree, null, null, function(){});
        $plugin = new Plugin();
        $server->addPlugin($plugin);

        $props = $server->getProperties('directory', ['{DAV:}resourcetype']);
        $this->assertTrue($props['{DAV:}resourcetype']->is('{' . Plugin::NS_CARDDAV . '}directory'));

    }

}

class DirectoryMock extends DAV\SimpleCollection implements IDirectory {



}
