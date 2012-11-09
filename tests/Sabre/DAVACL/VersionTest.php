<?php

namespace Sabre\DAVACL;

class VersionTest extends \PHPUnit_Framework_TestCase {

    function testString() {

        $v = Version::VERSION;
        $this->assertEquals(-1, version_compare('1.0.0',$v));

        $s = Version::STABILITY;
        $this->assertTrue($s == 'alpha' || $s == 'beta' || $s =='stable');

    }

}
