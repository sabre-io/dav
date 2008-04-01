<?php

    require_once 'Sabre/DAV/FS/Node.php';
    require_once 'Sabre/DAV/ILockable.php';

    /**
     * Base node-class 
     *
     * The node class implements the method used by both the File and the Directory classes 
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license license http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */
    abstract class Sabre_DAV_FSExt_Node extends Sabre_DAV_FS_Node implements Sabre_DAV_ILockable {

        /**
         * Returns all the locks on this node
         * 
         * @return array 
         */
        function getLocks() {

            $resourceData = $this->getResourceData();
            $locks = $resourceData['locks'];
            foreach($locks as $k=>$lock) {
                if (time() > $lock->timeout + $lock->created) unset($locks[$k]); 
            }
            return $locks;

        }

        /**
         * Locks this node 
         * 
         * @param Sabre_DAV_Lock $lockInfo 
         * @return void
         */
        function lock(Sabre_DAV_Lock $lockInfo) {

            // We're making the lock timeout 30 minutes
            $lockInfo->timeout = 1800;
            $lockInfo->created = time();

            $try = array(
                'X_LITMUS',
                'X_LITMUS_ONE',
                'X_LITMUS_SECOND',
            );

            foreach($try as $t) if (isset($_SERVER['HTTP_'.$t])) $lockInfo->info = "\n" . $_SERVER['HTTP_' . $t] . "\n";

            $resourceData = $this->getResourceData();
            if (!isset($resourceData['locks'])) $resourceData['locks'] = array();
            foreach($resourceData['locks'] as $k=>$lock) {
                if ($lock->token == $lockInfo->token) unset($resourceData['locks'][$k]);
            }
            $resourceData['locks'][] = $lockInfo;
            $this->putResourceData($resourceData);

        }

        /**
         * Removes a lock from this node
         * 
         * @param Sabre_DAV_Lock $lockInfo 
         * @return bool 
         */
        function unlock(Sabre_DAV_Lock $lockInfo) {

            throw new Sabre_DAV_Exception('bla');
            $resourceData = $this->getResourceData();
            foreach($resourceData['locks'] as $k=>$lock) {

                if ($lock->token == $lockInfo->token) {

                    unset($resourceData['locks'][$k]);
                    $this->putResourceData($resourceData);
                    return true;

                }
            }
            return false;

        }

        /**
         * Returns the path to the resource file 
         * 
         * @return string 
         */
        protected function getResourceInfoPath() {

            return dirname($this->path) . '/.sabredav';

        }

        /**
         * Returns all the stored resource information 
         * 
         * @return array 
         */
        protected function getResourceData() {

            $path = $this->getResourceInfoPath();
            if (!file_exists($path)) return array('locks'=>array());

            // opening up the file, and creating a shared lock
            $handle = fopen($path,'r');
            flock($handle,LOCK_SH);
            $data = '';

            // Reading data until the eof
            while(!feof($handle)) {
                $data.=fread($handle,8192);
            }

            // We're all good
            fclose($handle);

            // Unserializing and checking if the resource file contains data for this file
            $data = unserialize($data);
            if (!isset($data[$this->getName()])) {
                return array('locks'=>array());
            }

            $data = $data[$this->getName()];
            if (!isset($data['locks'])) $data['locks'] = array();
            return $data;

        }

        /**
         * Updates the resource information 
         * 
         * @param array $newData 
         * @return void
         */
        protected function putResourceData(array $newData) {

            $path = $this->getResourceInfoPath();

            // opening up the file, and creating a shared lock
            $handle = fopen($path,'a+');
            flock($handle,LOCK_EX);
            $data = '';

            rewind($handle);

            // Reading data until the eof
            while(!feof($handle)) {
                $data.=fread($handle,8192);
            }

            // Unserializing and checking if the resource file contains data for this file
            $data = unserialize($data);
            $data[$this->getName()] = $newData;
            ftruncate($handle,0);
            rewind($handle);

            fwrite($handle,serialize($data));
            fclose($handle);

        }

        public function delete() {

            // When we're deleting this node, we also need to delete any resource information
            $path = $this->getResourceInfoPath();
            if (!file_exists($path)) return true;

            // opening up the file, and creating a shared lock
            $handle = fopen($path,'a+');
            flock($handle,LOCK_EX);
            $data = '';

            rewind($handle);

            // Reading data until the eof
            while(!feof($handle)) {
                $data.=fread($handle,8192);
            }

            // Unserializing and checking if the resource file contains data for this file
            $data = unserialize($data);
            if (isset($data[$this->getName()])) unset($data[$this->getName()]);
            ftruncate($handle,0);
            rewind($handle);
            fwrite($handle,serialize($data));
            fclose($handle);

        }


    }

?>
