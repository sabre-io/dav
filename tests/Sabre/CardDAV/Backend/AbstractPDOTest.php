<?php

abstract class Sabre_CardDAV_Backend_AbstractPDOTest extends PHPUnit_Framework_TestCase {

    protected $backend;

    abstract function getPDO();

    public function setUp() {

        $backend = new Sabre_CardDAV_Backend_PDO($this->getPDO());
        $this->backend = $backend;

    }    

    public function testGetAddressBooksForUser() {

        $result = $this->backend->getAddressBooksForUser('principals/user1');

        $expected = array(
            array(
                'id' => 1,
                'uri' => 'book1',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'book1',
                '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => 'addressbook 1',
                '{http://calendarserver.org/ns/}getctag' => 1,
            )
        );

        $this->assertEquals($expected, $result);

    }

}

