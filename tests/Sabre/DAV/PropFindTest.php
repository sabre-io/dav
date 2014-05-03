<?php

namespace Sabre\DAV;

class PropFindTest extends \PHPUnit_Framework_TestCase {

    function testHandle() {

        $propFind = new PropFind('foo', ['{DAV:}displayname']); 
        $propFind->handle('{DAV:}displayname', 'foobar');

        $this->assertEquals([200 => ['{DAV:}displayname' => 'foobar']], $propFind->getResultForMultiStatus());

    } 

}
