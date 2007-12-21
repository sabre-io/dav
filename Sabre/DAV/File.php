<?php

    require_once 'Sabre/DAV/IFile.php';

    abstract class Sabre_DAV_File implements Sabre_DAV_IFile {

        private $fullPath;

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

        function setFullPath($path) {

            $this->fullPath = $path;

        }

        function getFullpath() {

            return $this->fullPath;

        }

    }

?>
