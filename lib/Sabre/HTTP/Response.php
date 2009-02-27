<?php

/**
 * Sabre_HTTP_Response 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
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
            206 => 'Partial Content',
            207 => 'Multi-Status',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method not allowed',
            409 => 'Conflict',
            412 => 'Precondition failed',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            423 => 'Locked',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
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

    /**
     * Sets an HTTP header for the response
     * 
     * @param string $name 
     * @param string $value 
     * @return void
     */
    public function setHeader($name, $value) {

        $value = str_replace(array("\r","\n"),array('\r','\n'),$value);
        header($name . ': ' . $value);

    }

    /**
     * Sends the entire response body
     *
     * This method can accept either an open filestream, or a string.
     * Note that this method will first rewind the stream before output.
     * 
     * @param mixed $body 
     * @return void
     */
    public function sendBody($body) {

        if (is_resource($body)) {
        
            rewind($body);
            fpassthru($body);

        } else {

            // We assume a string
            echo $body;

        }

    }

}
