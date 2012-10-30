<?php

namespace Sabre\CardDAV;

class VersionTest extends \PHPUnit_Framework_TestCase {

    function testString() {

        $v = Version::VERSION;
        $this->assertEquals(-1, version_compare('0.1',$v));

        $s = Version::STABILITY;
        $this->assertTrue($s == 'alpha' || $s == 'beta' || $s =='stable');

    }

}
