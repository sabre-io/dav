<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * ServiceUnavailable
 *
 * This exception is thrown in case the service
 * is currently not available (e.g. down for maintenance).
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

class ServiceUnavailable extends Sabre_DAV_Exception {

	/**
	 * Returns the HTTP statuscode for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {

		return 503;

	}

}
