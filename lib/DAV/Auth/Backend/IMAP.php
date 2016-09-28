<?php

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses imap.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Michael Niewöhner (foss@mniewoehner.de)
 * @author rosali (https://github.com/rosali)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class IMAP extends AbstractBasic {

    /**
     * IMAP server in the form host[:port]
     *
     * @var string
     */
    protected $imap_host;

    /**
     * IMAP connection flags
     * @see http://php.net/manual/en/function.imap-open.php
     *
     * @var string
     */
    protected $imap_flags;

    /**
     * Creates the backend object.
     * imap_host is the IMAP server in the form host[:port]
     * imap_flags specifies the IMAP connection flags
     * @see http://php.net/manual/en/function.imap-open.php
     *
     * @param string $imap_host
     * @param string $imap_flags
     */
    function __construct($imap_host, $imap_flags = '') {

        $this->imap_host = $imap_host;
        $this->imap_flags = $imap_flags;

    }

    /**
     * Connects to an IMAP server and tries to authenticate
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function imapOpen($username, $password) {

        $mailbox = "{" . $this->imap_host . $this->imap_flags . "/readonly}";

        $success = false;

        try {
            $imap = imap_open($mailbox, $username, $password, 0, 1);
            if ($imap)
                $success = true;
        } catch (\ErrorException $e) {
            error_log($e->getMessage());
        }

        foreach (imap_errors() as $error)
            error_log($error);

        if (isset($imap) && $imap)
            imap_close($imap);

        return $success;
    }

    /**
     * Validates a username and password by trying to authenticate against IMAP
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function validateUserPass($username, $password) {

        return $this->imapOpen($username, $password);

    }

}
