<?php

/**
 * Sabre_DAV_Exception_ServiceUnavailable
 *
 * This exception is thrown in case the service
 * is currently not available (e.g. down for maintenance).
 *
 * @package Sabre
 * @subpackage DAV
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

class Sabre_DAV_Exception_ServiceUnavailable extends Sabre_DAV_Exception {

	/**
	 * Returns the HTTP statuscode for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {

		return 503;

	}

}
