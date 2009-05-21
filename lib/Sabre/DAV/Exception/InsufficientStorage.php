<?php

/**
 * InsufficientStorage
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id: Exception.php 348 2009-03-26 00:24:28Z evertpot $
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
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
