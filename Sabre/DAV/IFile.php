<?php

    interface Sabre_DAV_IFile {

        function delete();

        function put($data);

        function get();

        function getName();

        function getSize();

        function getLastModified();

        function setFullPath($path);

        function getFullPath();

    }

?>
