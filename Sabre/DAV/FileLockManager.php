<?php

    class Sabre_DAV_FileLockManager {

        private $basePath;

        function __construct($basePath = null) {

            if (is_null($basePath)) $basePath = ini_get('session.save_path') . '/SabreDav_';
            $this->basePath = $basePath;

        }

        function getLockInfo($location) {

            $path = $this->getPath($location);
            if (file_exists($path)) $lockInfo = unserialize(file_get_contents($path));
            else return false;

            if ($lockInfo['timeout']<time()) {
                unlink($path);
                return false;
            }
            return $lockInfo;

        }

        function lockFile($location,$owner,$timeout,$locktoken) {

            $lockInfo = array(
                'owner' => $owner,
                'locktoken' => $locktoken?$locktoken:'opaquelocktoken:' . $this->generateRandomId(), 
                'timeout' => time() + $timeout,
            );    
            file_put_contents($this->getPath($location),serialize($lockInfo));
            return $lockInfo;

        }

        function unlockFile($location) {

            $path = $this->getPath($location);
            if (file_exists($path)) {
                unlink($path);
            }

        }

        private function getPath($location) {
            
            return $this->basePath . md5($location) . '.lock';

        }

        public function generateRandomId() {

            $SabreDAVID = '44445502';

            $id = md5(microtime() . 'somethingrandom');

            return $SabreDAVID . '-' . substr($id,0,4) . '-' . substr($id,4,4) . '-' . substr($id,8,4) . '-' . substr($id,12,12);

        }
    }

?>
