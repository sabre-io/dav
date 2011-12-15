<?php

class Sabre_DAV_Exception_PaymentRequiredTest extends PHPUnit_Framework_TestCase {

    function testGetHTTPCode() {

        $ex = new Sabre_DAV_Exception_PaymentRequired();
        $this->assertEquals(402, $ex->getHTTPCode());

    }

}
