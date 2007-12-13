<?php

    require_once 'Sabre/DAV/IFile.php';

    abstract class Sabre_DAV_File implements Sabre_DAV_IFile {

        function delete() {

            throw new Sabre_DAV_PermissionDeniedException();

        }

        function put($data) {

            throw new Sabre_DAV_PermissionDeniedException();

        }

        function get() { 

            throw new Sabre_DAV_PermissionDeniedException();

        }

        function getSize() {

            return 0;

        }

        function getLastModified() {

            return time();

        }

    }

?>
