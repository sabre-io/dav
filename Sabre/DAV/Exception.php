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

    /**
     * UnSupportedMediaTypeException
     *
     * The 415 Unsupported Media Type status code is generally sent back when the client tried to call an HTTP method, with a body the server didn't understand
     */
    class Sabre_DAV_UnsupportedMediaTypeException extends Sabre_DAV_Exception { 

        function getHTTPCode() {

            return 415;

        }

    }

    /**
     * ConflictException
     *
     * A 409 Conflict is thrown when a user tried to make a directory over an existing file or in a parent directory that doesn't exist
     */
    class Sabre_DAV_ConflictException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 409;

        }

    }

    /**
     * MethodNotAllowedException 
     *
     * The 405 is thrown when a client tried to create a directory on an already existing directory
     */
    class Sabre_DAV_MethodNotAllowedException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 405;

        }

    }

    /**
     * LockedException 
     *
     * The 423 is thrown when a client tried to access a resource that was locked, without supplying a valid lock token
     */
    class Sabre_DAV_LockedException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 423;

        }

    }

    /**
     * InsufficientStorageException 
     *
     * This Exception can be thrown, when for example a harddisk is full or a quota is exceeded
     */
    class Sabre_DAV_InsufficientStorageException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 423;

        }

    }

    /**
     * PreconditionFailedException 
     *
     * This exception is normally thrown when a client submitted a conditional request, like for example an If, If-None-Match or If-Match header, which 
     * caused the HTTP request to not execute (the condition of the header failed)
     */
    class Sabre_DAV_PreconditionFailedException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 412; 

        }

    }

    /**
     * BabRequestException
     *
     * The BadRequestException is thrown when the user submitted an invalid HTTP request
     */
    class Sabre_DAV_BadRequestException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 400; 

        }

    }
?>
