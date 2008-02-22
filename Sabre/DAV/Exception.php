<?php

    /**
     * SabreDAV Exceptions
     *
     * This file contains a bunch of classes that are used throughout SabreDAV
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */

    /**
     * Main Exception class. 
     *
     * This class defines a getHTTPCode method, which should return the appropriate HTTP code for the Exception occured.
     * The default for this is 500
     */
    class Sabre_DAV_Exception extends Exception { 

        /**
         * getHTTPCode
         *
         * @return int
         */
        public function getHTTPCode() { 

            return 500;

        }

    }

    /**
     * FileNotFoundException
     *
     * This Exception is thrown when a Node couldn't be found. It returns HTTP error code 404
     */
    class Sabre_DAV_FileNotFoundException extends Sabre_DAV_Exception {
   
        /**
         * getHTTPCode 
         * 
         * @return int 
         */
        public function getHTTPCode() {

            return 404;

        }

    }

    /**
     * PermissionDeniedException 
     *
     * This exception is thrown whenever a user tries to do an operation that he's not allowed to
     */
    class Sabre_DAV_PermissionDeniedException extends Sabre_DAV_Exception {

        /**
         * getHTTPCode 
         * 
         * @return int 
         */
        public function getHTTPCode() {

            return 403;

        }

    }

    /**
     * MethodNotImplementedException 
     *
     * This exception is thrown when the client tried to call an unsupported HTTP method
     */
    class Sabre_DAV_MethodNotImplementedException extends Sabre_DAV_Exception {

        /**
         * getHTTPCode 
         * 
         * @return void
         */
        public function getHTTPCode() {
            
            return 501;

        }

    }

?>
