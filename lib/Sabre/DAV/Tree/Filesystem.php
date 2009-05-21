<?php

/**
 * Sabre_DAV_Tree_Filesystem 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Tree_Filesystem extends Sabre_DAV_Tree {

    /**
     * Base url on the filesystem.
     *
     * @var string 
     */
    protected $basePath;

    /**
     * Creates this tree
     *
     * Supply the path you'd like to share.
     * 
     * @param string $basePath 
     * @return void
     */
    public function __construct($basePath) {

        $this->basePath = $basePath;

    }

    /**
     * Returns the real filesystem path for a webdav url. 
     * 
     * @param string $publicPath 
     * @return string 
     */
    protected function getRealPath($publicPath) {

        return rtrim($this->basePath,'/') . '/' . trim($publicPath,'/');

    }

    /**
     * Copies a file or directory.
     *
     * This method must work recursively and delete the destination
     * if it exists
     * 
     * @param string $source 
     * @param string $destination 
     * @return void
     */
    public function copy($source,$destination) {

        $source = $this->getRealPath($source);
        $destination = $this->getRealPath($destination);

        if (file_exists($destination)) $this->realDelete($destination);
        $this->realCopy($source,$destination); 

    }

    /**
     * Used by self::copy 
     * 
     * @param string $source 
     * @param string $destination 
     * @return void
     */
    protected function realCopy($source,$destination) {

        if (is_file($source)) {
            copy($source,$destination);
        } else {
            mkdir($destination);
            foreach(scandir($source) as $subnode) {

                if ($subnode=='.' || $subnode=='..') continue;
                $this->realCopy($source.'/'.$subnode,$destination.'/'.$subnode);

            }
        }

    }

    /**
     * Returns information about a directory or file
     *
     * this should be an array for each file. If depth = 0, only the given 
     * path has to be in there. For depth = 1, it should also have entries
     * for it's children.
     * 
     * @param string $path 
     * @param int $depth 
     * @throws Sabre_DAV_Exception_FileNotFound This exception must be thrown if the node does not exist.
     * @return array 
     */
    public function getNodeInfo($path,$depth=0) {

        $path = $this->getRealPath($path);
        if (!file_exists($path)) throw new Sabre_DAV_Exception_FileNotFound($path . ' could not be found');
        $nodeInfo = array();

        $nodeInfo[] = array(
            'name'            => '',
            'type'            => is_dir($path)?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
            'size'            => filesize($path),
            'lastmodified'    => filemtime($path),
            'quota-used'      => disk_total_space($path)-disk_free_space($path),
            'quota-available' => disk_free_space($path),
            'etag'            => md5(filesize($path) . filemtime($path) . $path),
        );

        if ($depth>0 && is_dir($path)) {

            foreach(scandir($path) as $node) {
                $subPath = $path.'/'.$node;
                if ($node=='.' || $node==='..') continue;
                $nodeInfo[] = array(
                    'name'            => $node,
                    'type'            => is_dir($subPath)?Sabre_DAV_Server::NODE_DIRECTORY:Sabre_DAV_Server::NODE_FILE,
                    'size'            => filesize($subPath),
                    'lastmodified'    => filemtime($subPath),
                    'quota-used'      => disk_total_space($subPath)-disk_free_space($subPath),
                    'quota-available' => disk_free_space($subPath),
                    'etag'            => md5(filesize($subPath) . filemtime($subPath) . $subPath),
                );
            }

        }

        return $nodeInfo;

    }

    /**
     * Deletes a file or a directory (recursively). 
     * 
     * @param string $path 
     * @return void
     */
    public function delete($path) {

        $path = $this->getRealPath($path);

        $this->realDelete($path); 

    }

    /**
     * Used by self::delete 
     * 
     * @param string $path 
     * @return void
     */
    protected function realDelete($path) {

        if (is_file($path)) {
            unlink($path);
        } else {
            foreach(scandir($path) as $subnode) {

                if ($subnode=='.' || $subnode=='..') continue;
                $this->realDelete($path.'/' . $subnode);

            }
            rmdir($path);
        }

    }

    /**
     * Updates a file
     * 
     * @param string $path 
     * @param resource $data 
     * @return void
     */
    public function put($path,$data) {

        file_put_contents($this->getRealPath($path),$data);

    }

    /**
     * Creates a new file 
     * 
     * @param string $path 
     * @param resource $data 
     * @return void
     */
    public function createFile($path, $data) {

        file_put_contents($this->getRealPath($path),$data);

    }

    /**
     * Returns the contents of a file as file stream 
     * 
     * @param string $path 
     * @return resource 
     */
    public function get($path) {

        return fopen($this->getRealPath($path),'r');

    }

    /**
     * Creates a new directory 
     * 
     * @param string $path 
     * @return void
     */
    public function createDirectory($path) {

        mkdir($this->getRealPath($path));

    }

    /**
     * Moves a file or directory recursively.
     *
     * If the destination exists, delete it first.
     * 
     * @param string $source 
     * @param string $destination 
     * @return void
     */
    public function move($source,$destination) {

        $source = $this->getRealPath($source);
        $destination = $this->getRealPath($destination);

        if (file_exists($destination)) $this->realDelete($destination);
        rename($source,$destination);

    }

    /**
     * Returns the additional properties for a given node.
     *
     * In this case we implemented {http://apache.org/dav/props/}executable
     * which is used by DavFS to tell it a file is an executable.
     * 
     * @param string $path 
     * @param array $properties 
     * @return array 
     */
    public function getProperties($path,$properties) {

        $path = $this->getRealPath($path);
            
        $returnProps = array();

        if (in_array('{http://apache.org/dav/props/}executable',$properties) && is_file($path)) {
            $returnProps['{http://apache.org/dav/props/}executable']  = is_executable($path)?'T':'F';
        }

        return $returnProps;

    }

}

?>
