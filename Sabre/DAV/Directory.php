<?php

    require_once 'Sabre/DAV/Directory.php';
    require_once 'Sabre/DAV/FS/File.php';

    class Sabre_DAV_FS_Directory extends Sabre_DAV_Directory {

        function __construct($myPath) {

            $this->myPath = $myPath;

        }

        function getName() {

            return basename($this->myPath);

        }

        function getChildren() {

            $children = array();

            clearstatcache();
            foreach(scandir($this->myPath) as $file) {

                if ($file=='.' || $file=='..') continue;
                if (is_dir($this->myPath . '/' . $file)) {

                    $children[] = new self($this->myPath . '/' . $file);

                } else {

                    $children[] = new Sabre_DAV_FS_File($this->myPath . '/' . $file);

                } 

            }

            return $children;

        }

        function createDirectory($name) {

            $newPath = $this->myPath . '/' . $name;
            mkdir($newPath);

        }

        function createFile($name,$data) {

            file_put_contents($this->myPath . '/' . basename($name),$data);

        }

        function delete() {

            foreach($this->getChildren() as $child) $child->delete();
            rmdir($this->myPath);

        }

    }

?>
