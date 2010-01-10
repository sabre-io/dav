<?php

/**
 * This is the base class for any authentication object.
 *
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Auth_Backend_Abstract {

    /**
     * Returns the HTTP Digest hash for a username
     *
     * This must be the A1 part of the digest hash
     * 
     * @param string $username 
     * @return string 
     */
    abstract public function getDigestHash($username);

    /**
     * Returns a userid for a username
     *
     * The result may be any string, or simply a number.
     * By default the username is just returned, but it is
     * possible for backends to supply a different type of userid.
     * 
     * @param mixed $username 
     * @return void
     */
    public function getUserId($username) {

        return $username;

    }

    /**
     * Returns the full list of users.
     *
     * This method must at least return a userId for each user.
     * 
     * @return array 
     */
    public function getUsers() {

        return array();

    }

}

?>
