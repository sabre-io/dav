<?php

/**
 * Sabre_HTTP_Response 
 * 
 * @package Sabre
 * @version $Id$
 * @copyright Copyright (C) 2007 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
 */
class Sabre_HTTP_Response {

    /**
     * Returns a full HTTP status message for an HTTP status code 
     * 
     * @param int $code 
     * @return string
     */
    public function getStatusMessage($code) {

        $msg = array(
            200 => 'Ok',
            201 => 'Created',
            204 => 'No Content',
            207 => 'Multi-Status',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method not allowed',
            409 => 'Conflict',
            412 => 'Precondition failed',
            415 => 'Unsupported Media Type',
            423 => 'Locked',
            500 => 'Internal Server Error',
            501 => 'Method not implemented',
            507 => 'Unsufficient Storage',
       ); 

       return 'HTTP/1.1 ' . $code . ' ' . $msg[$code];

    }

    /**
     * Sends an HTTP status header to the client 
     * 
     * @param int $code HTTP status code 
     * @return void
     */
    public function sendStatus($code) {

        header($this->getStatusMessage($code));

    }

}
