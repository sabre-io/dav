<?php

/**
 * NotFound
 *
 * This Exception is thrown when a Node couldn't be found. It returns HTTP error code 404
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Exception_NotFound extends Sabre_DAV_Exception {

    /**
     * Returns the HTTP statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

        return 404;

    }

}

