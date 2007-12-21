<?php

    require_once 'Sabre/DAV/File.php';

    class Sabre_DAV_FS_File extends Sabre_DAV_File {

        private $myPath;

        function __construct($myPath) {

            $this->myPath = $myPath;

        }

        function getName() {

            return basename($this->myPath);

        }

        function get() {

            readfile($this->myPath);

        }

        function delete() {

            unlink($this->myPath);

        }

        function put($data) {

            file_put_contents($this->myPath,$data);

        }

        function getLastModified() {

            return filemtime($this->myPath);

        }

        function getSize() {

            return filesize($this->myPath);
            
        }

    }

?>
