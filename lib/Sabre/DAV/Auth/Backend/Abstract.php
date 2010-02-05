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
     * Authenticates the user based on the current request.
     *
     * If authentication succeeds, a struct with user-information must be returned
     * If authentication fails, false must be returned.
     *
     * @return void
     */
    abstract public function authenticate(Sabre_DAV_Server $server,$realm); 

    /**
     * Returns the full list of users.
     *
     * This method must at least return a userId for each user.
     *
     * It is optional to implement this.
     * 
     * @return array 
     */
    public function getUsers() {

        return array();

    }

}

?>
