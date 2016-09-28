<?php

namespace Sabre\DAV\Auth\Backend;

class IMAPTest extends \PHPUnit_Framework_TestCase {

    function testGoodPassword() {

        $imap_host = 'localhost:9993';
        $imap_flags = '';
        $imap = new IMAPMock($imap_host, $imap_flags);
        $this->assertTrue($imap->validateUserPass('username', 'password'));

    }

    function testBadPassword() {

        $imap_host = 'localhost:9993';
        $imap_flags = '';
        $imap = new IMAPMock($imap_host, $imap_flags);
        $this->assertFalse($imap->validateUserPass('username', 'badpassword'));

    }

}


class IMAPMock extends IMAP {

    /**
     * Connects to an IMAP server and tries to authenticate
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    function imapOpen($username, $password) {

        return ($username == 'username' && $password == 'password');

    }

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    function validateUserPass($username, $password) {

        return parent::validateUserPass($username, $password);

    }

}
