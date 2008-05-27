<?php

    require_once 'Sabre/DAV/FilterTree.php';

    class Sabre_DAV_TemporaryFileFilter extends Sabre_DAV_FilterTree {

        private $dataDir = null;

        const FINDER_FORK = 
            'H4sIAAAAAAAAA+3XMWsCMRQH8Beh2Fu0OHQqR+Yicji6FV0cRKiHlG7xfNJwuSTkIuK36mdy6rfQiGepwtHNQd6PhHCP9w+5bIGH5yY0ACYi49MZ/+CVYw2iMPsAre+whu/WDoDFcPYENd7S9D0snVMCfsL8vGppVPXXMDJT9IS1CnsFerEUXgzyyWjssRiXs8wh6qGwfu3wFGVhX0gAuvW5i1S6tSG5sEqWPkmix2oXVp2EXZ0sOnfON1Ivzea//nbdLRBCCCGEEEIIuTUGf55tcfolS+6wNGuXIV8Zl3OpPWovjRZKbbnClecLJXR+fAffgcv//y2/QLzfHwDNa116ABAAAA==';

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
                    if (strpos(basename($path),'._')===0) echo gzdecode(base64_decode(self::FINDER_FORK));
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

?>
