<?php

// !!!! Make sure the Sabre directory is in the include_path !!!

/* Settings */

// Files we're going to share
$publicDir = './public/';
// Filestore for locks
$dataDir = './data/';
// Base url
$baseUrl = '/';

// {$Id$} //

// Files we need
require_once 'Sabre.includes.php';

class streamTree extends Sabre_DAV_Tree {

    private $stream;

    public function __construct($stream) {

        $this->stream = rtrim($stream,'/') . '/';

    }

    private function getPath($path,$mustExist = false) {

        $newPath = '';
        // Basic security check
        foreach(explode('/',$path) as $pathPart) {

            switch($pathPart) {
                
                case '.' :
                    continue;
                case '..' :
                    throw new Sabre_DAV_BadRequestException('Invalid path');
                    break;


            }
            $newPath.=($pathPart?'/':'').$pathPart;

        }

        $newPath = $this->stream . trim($newPath,'/');
        if ($mustExist && !file_exists($newPath)) throw new Sabre_DAV_FileNotFoundException('File not found!');
        return $newPath;

    }


    public function copy($source,$destination) {

        copy($this->getPath($source),$this->getPath($destination));

    }

    public function getNodeInfo($path,$depth=0) {
        
        $props = array();

        $realPath = $this->getPath($path,true);

        if ($depth == 0 || $depth == Sabre_DAV_Server::DEPTH_INFINITY) {
            $props[] = array(
                'name'         => '',
                'type'         => is_dir($realPath)?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
                'lastmodified' => filemtime($realPath),
                'size'         => filesize($realPath),
            );
        }

        if ($depth == 1 || $depth == Sabre_DAV_Server::DEPTH_INFINITY) {

            foreach(scandir($realPath) as $node) {
                
                if ($node=='.' || $node=='..') continue;
                $subPath = $realPath . '/' . $node;
                $props[] = array(
                    'name'         => $node,
                    'type'         => is_dir($subPath)?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
                    'lastmodified' => filemtime($subPath),
                    'size'         => filesize($subPath),
                );

            }

        }

        return $props;

    }

    public function delete($path) {

        $realPath = $this->getPath($path);
        if (is_dir($realPath)) {
            rmdir($realPath);
        } else {
            unlink($realPath);
        }

    }

    public function put($path,$data) {

        file_put_contents($this->getPath($path,1),$data);

    }

    public function createFile($path,$data) {

        file_put_contents($this->getPath($path),$data);

    }

    public function get($path) {

        return file_get_contents($this->getPath($path,1));

    }

    public function createDirectory($path) {

        mkdir($this->getPath($path));

    }

    public function move($source,$destination) {

        rename($this->getPath($source,true),$this->getPath($destination));

    }

}

$objectTree = new streamTree($publicDir);
$lockManager = new Sabre_DAV_LockManager_FS($dataDir);
$objectTree->setLockManager($lockManager);


// The object tree needs in turn to be passed to the server class
$server = new Sabre_DAV_Server($objectTree);

$server->setBaseUri($baseUrl);

// And off we go!
$server->exec();


?>
