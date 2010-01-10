<?php

/**
 * InsufficientStorage
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * InsufficientStorage 
 *
 * This Exception can be thrown, when for example a harddisk is full or a quota is exceeded
 */
class Sabre_DAV_Exception_InsufficientStorage extends Sabre_DAV_Exception {

    function getHTTPCode() {

        return 507;

    }

}
