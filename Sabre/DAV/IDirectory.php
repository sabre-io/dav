<?php

    require_once 'Sabre/DAV/IFile.php';

    interface Sabre_DAV_IDirectory extends Sabre_DAV_IFile {

        function createFile($name,$data);

        function createDirectory($name);

        function getChildren();

        function getChild($name);

    }

?>
