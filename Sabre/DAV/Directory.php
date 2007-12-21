<?php

    require_once 'Sabre/DAV/File.php';
    require_once 'Sabre/DAV/IDirectory.php';
    require_once 'Sabre/DAV/Exception.php';

    abstract class Sabre_DAV_Directory extends Sabre_DAV_File implements Sabre_DAV_IDirectory {

        function createFile($filename,$data) {

            throw new Sabre_DAV_PermissionDeniedException();

        }

        function createDirectory($name) {

            throw new Sabre_DAV_PermissionDeniedException();

        }

        function getChildren() {

            throw new Sabre_DAV_PermissionDeniedException();

        }

        function getChild($path) {

            foreach($this->getChildren() as $child) {

                if ($child->getName()==$path) return $child;

            }
            throw new Sabre_DAV_FileNotFoundException();

        }

    }

?>
