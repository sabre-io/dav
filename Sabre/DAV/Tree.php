<?php

    require_once 'Sabre/DAV/Handler.php';

    class Sabre_DAV_Tree extends Sabre_DAV_Handler {

        private $root;

        function __construct(Sabre_DAV_Directory $root) {

            $this->root = $root;

        }

        public function getObject($path) {

            $path = trim($path,'/');

            $current = $this->root;

            if ($path && $path!='.') { 
                $path = explode('/',trim($path));

                foreach($path as $part) {

                    $current = $current->getChild($part);                

                }

            }

            return $current;

        }

        public function getDirectoryList($path) {

            $directory = $this->getObject($path);
            $list = array();
            foreach($directory->getChildren() as $child) {

                $list[] = array(
                    'name' => $child->getName(),
                    'type' => $child instanceof Sabre_DAV_IDirectory?1:0,
                );    

            }
            
            return $list;
    
        }

        public function getFileInfo($path) {

            $file = $this->getObject($path);
            return array(
                'name' => $file->getName(),
                'type' => $file instanceof Sabre_DAV_IDirectory?1:0,
            );    

        }

        public function echoFileContents($path) { 

            $this->getObject($path)->get();

        }

        public function createFile($directory,$name,$data) {

            $this->getObject($directory)->createFile($name,$data);

        }

        public function updateFile($path,$data) {

            $this->getObject($path)->put($data);

        }

        public function createDirectory($parent,$name) {

            $this->getObject($parent)->createDirectory($name);

        }

        public function deleteFile($path) {

            $this->getObject($path)->delete();

        }

    }

?>
