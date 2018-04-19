<?php

namespace Sabre\DAV\Auth\Backend;

class IMAPTest extends \PHPUnit\Framework\TestCase {

    function testGoodPassword() {

        $mailbox = '{localhost:9993}';
        $imap = new IMAPMock($mailbox);
        $this->assertTrue($imap->validateUserPass('username', 'password'));

    }

    function testBadPassword() {

        $mailbox = '{localhost:9993}';
        $imap = new IMAPMock($mailbox);
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
