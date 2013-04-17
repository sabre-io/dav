<?php

/**
 * RequestedRangeNotSatisfiable
 *
 * This exception is normally thrown when the user
 * request a range that is out of the entity bounds.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Exception_RequestedRangeNotSatisfiable extends Sabre_DAV_Exception {

    /**
     * returns the http statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

        return 416;

    }

}

