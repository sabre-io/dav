<?php

class Sabre_DAV_Tree_Filesystem extends Sabre_DAV_Tree {

    protected $basePath;

    public function __construct($basePath) {

        $this->basePath = $basePath;

    }

    protected function getRealPath($publicPath) {

        return rtrim($this->basePath,'/') . '/' . trim($publicPath,'/');

    }

    public function copy($source,$destination) {

        $source = $this->getRealPath($source);
        $destination = $this->getRealPath($destination);

        if (file_exists($destination)) $this->realDelete($destination);
        $this->realCopy($source,$destination); 

    }

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

    public function getNodeInfo($path,$depth=0) {

        $path = $this->getRealPath($path);
        if (!file_exists($path)) throw new Sabre_DAV_FileNotFoundException($path . ' could not be found');
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

    public function delete($path) {

        $path = $this->getRealPath($path);

        $this->realDelete($path); 

    }

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

    public function put($path,$data) {

        file_put_contents($this->getRealPath($path),$data);

    }

    public function createFile($path, $data) {

        file_put_contents($this->getRealPath($path),$data);

    }

    public function get($path) {

        return fopen($this->getRealPath($path),'r');

    }

    public function createDirectory($path) {

        mkdir($this->getRealPath($path));

    }

    public function move($source,$destination) {

        $source = $this->getRealPath($source);
        $destination = $this->getRealPath($destination);

        if (file_exists($destination)) $this->realDelete($destination);
        rename($source,$destination);

    }

}

?>
