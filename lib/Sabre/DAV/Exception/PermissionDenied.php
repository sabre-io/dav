<?php

/**
 * PermissionDenied
 *
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */


/**
 * PermissionDenied 
 *
 * This exception is thrown whenever a user tries to do an operation that he's not allowed to.
 * This class will be deprecated in the future. Update your code to use Sabre_DAV_Exception_Forbidden instead.
 */
class Sabre_DAV_Exception_PermissionDenied extends Sabre_DAV_Exception_Forbidden {


}
