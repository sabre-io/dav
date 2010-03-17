<?php

class Sabre_DAV_ExceptionTest extends PHPUnit_Framework_TestCase {

    function testStatus() {

        $e = new Sabre_DAV_Exception();
        $this->assertEquals(500,$e->getHTTPCode());

    } 

}
