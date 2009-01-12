<?php

class Sabre_DAV_TemporaryFileFilter extends Sabre_DAV_FilterTree {

    private $dataDir = null;

    const FINDER_FORK =
        'eJzt1zFrAjEUB/AXodhbtDh0KkfmInI4uhVdHESoh5Ru8XzScLkk5CLit+pncuq30IhnqcLRzUHej4Rwj/cPuWyBh+cmNAAmIuPTGf/glWMNojD7AK3vsIbv1g6AxXD2BDXe0vQ9LJ1TAn7C/LxqaVT11zAyU/SEtQp7BXqxFF4M8slo7LEYl7PMIeqhsH7t8BRlYV9IALr1uYtUurUhubBKlj5JosdqF1adhF2dLDp3zjdSL83mv/523S0QQgghhBBCCLk1Bn+ebXH6JUvusDRrlyFfGZdzqT1qL40WSm25wpXnCyV0fnwH34HL//8tv0C83x8AUcxHTA==';

    public function setDataDir($path) {

        $this->dataDir = $path;

    }

    public function isTempFile($path) {

        $tempPath = basename($path);
        
        $tempFiles = array(
            '/^._(.*)$/',      // OS/X resource forks
            '/^.DS_Store$/',   // OS/X custom folder settings
            '/^desktop.ini$/', // Windows custom folder settings
            '/^Thumbs.db$/',   // Windows thumbnail cache
            '/^.(.*).swp$/',   // ViM temporary files
            '/.dat(.*)$/',     // Smultron seems to create these
        );

        $match = false;
        foreach($tempFiles as $tempFile) {

            if (preg_match($tempFile,$tempPath)) $match = true; 

        }

        if ($match) {
            $dataDir = (is_null($this->dataDir)?ini_get('session.save_path').'/sabredav/':$this->dataDir);
            return $dataDir . '/sabredav_' . md5($path) . '.tempfile';
        } else {
            return false;
        }

    }

    public function put($path,$data) {

        if ($tempPath = $this->isTempFile($path)) {

            file_put_contents($tempPath,$data);

        } else return parent::put($path,$data);

    }

    public function createFile($path,$data) {

        if ($tempPath = $this->isTempFile($path)) {

            file_put_contents($tempPath,$data);

        } else return parent::createFile($path,$data);

    }

    public function get($path) {

        if ($tempPath = $this->isTempFile($path)) {

            if (!file_exists($tempPath)) {
                if (strpos(basename($path),'._')===0) return gzuncompress(base64_decode(self::FINDER_FORK));
                else throw new Sabre_DAV_FileNotFoundException();
            } else { 
                return file_get_contents($tempPath);
            }

        } else return parent::get($path);

    }
    
    public function delete($path) {

        if ($tempPath = $this->isTempFile($path)) {
            
            return(file_exists($tempPath) && unlink($tempPath));

        } else return parent::delete($path);

    }

    public function getNodeInfo($path,$depth=0) {

        if (($tempPath = $this->isTempFile($path)) && !$depth) {

            //echo $tempPath;
            if (!file_exists($tempPath)) {
                if (strpos(basename($path),'._')===0) {
                    return array(array(
                        'name'         => '',
                        'type'         => Sabre_DAV_Server::NODE_FILE,
                        'lastmodified' => filemtime(__FILE__),
                        'size'         => 4096, 
                    ));
                    
                } else {
                    throw new Sabre_DAV_FileNotFoundException();
                }
            }
            $props = array(
                'name'         => '',
                'type'         => Sabre_DAV_Server::NODE_FILE,
                'lastmodified' => filemtime($tempPath),
                'size'         => filesize($tempPath), 
            );

            return array($props);

        } else return parent::getNodeInfo($path,$depth);

    }

}

