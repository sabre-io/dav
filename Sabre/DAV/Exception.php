<?php

    class Sabre_DAV_Exception extends Exception {

        function getHTTPCode() {

            return 500;

        }

    }

    class Sabre_DAV_MethodNotImplementedException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 501;

        }

    }



    class Sabre_DAV_PermissionDeniedException extends Sabre_DAV_Exception {
   
        function getHTTPCode() {

            return 403;

        }

    }

    class Sabre_DAV_FileNotFoundException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 404;

        }

    }

    class Sabre_DAV_ConflictException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 409;

        }

    }

    class Sabre_DAV_FileExistsException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 409;

        }
    }

    class Sabre_DAV_MethodNotAllowedException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 405;

        }

    }

    class Sabre_DAV_UnsupportedMediaTypeException extends Sabre_DAV_Exception { 

        function getHTTPCode() {

            return 415;

        }

    }

    class Sabre_DAV_BadRequestException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 400;

        }

    }

    class Sabre_DAV_PreconditionFailedException extends Sabre_DAV_Exception {

        function getHTTPCode() {

            return 412;

        }

    }
?>
