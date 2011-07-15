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

    public function testUpdateAddressBookInvalidProp() {

        $result = $this->backend->updateAddressBook(1, array(
            '{DAV:}displayname' => 'updated',
            '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => 'updated',
            '{DAV:}foo' => 'bar',
        ));

        $this->assertFalse($result);

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

    public function testUpdateAddressBookNoProps() {

        $result = $this->backend->updateAddressBook(1, array());

        $this->assertFalse($result);

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

    public function testUpdateAddressBookSuccess() {

        $result = $this->backend->updateAddressBook(1, array(
            '{DAV:}displayname' => 'updated',
            '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => 'updated',
        ));

        $this->assertTrue($result);

        $result = $this->backend->getAddressBooksForUser('principals/user1');

        $expected = array(
            array(
                'id' => 1,
                'uri' => 'book1',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'updated',
                '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => 'updated',
                '{http://calendarserver.org/ns/}getctag' => 2,
            )
        );

        $this->assertEquals($expected, $result);
        

    }

    public function testGetCards() {

        $result = $this->backend->getCards(1);

        $expected = array(
            array(
                'id' => 1,
                'uri' => 'card1',
                'carddata' => 'card1',
                'lastmodified' => 0,
            )
        );

        $this->assertEquals($expected, $result);

    }    


}

