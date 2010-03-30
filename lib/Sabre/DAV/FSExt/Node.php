<?php

/**
 * Base node-class 
 *
 * The node class implements the method used by both the File and the Directory classes 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_FSExt_Node extends Sabre_DAV_FS_Node implements Sabre_DAV_ILockable, Sabre_DAV_IProperties {

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
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return void
     */
    function lock(Sabre_DAV_Locks_LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 1800;
        $lockInfo->created = time();

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
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    function unlock(Sabre_DAV_Locks_LockInfo $lockInfo) {

        //throw new Sabre_DAV_Exception('bla');
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
     * Updates properties on this node,
     *
     * The mutations array, contains arrays with mutation information, with the following 3 elements:
     *   * 0 = mutationtype (1 for set, 2 for remove)
     *   * 1 = nodename (encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     *   * 2 = value, can either be a string or a DOMElement
     * 
     * This method should return a similar array, with information about every mutation:
     *   * 0 - nodename, encoded as in the $mutations argument
     *   * 1 - statuscode, encoded as http status code, for example
     *      200 for an updated property or succesful delete
     *      201 for a new property
     *      403 for permission denied
     *      etc..
     *
     * @param array $mutations 
     * @return void
     */
    function updateProperties($mutations) {

        $resourceData = $this->getResourceData();
        
        $result = array();

        foreach($mutations as $mutation) {


            switch($mutation[0]){ 
                case Sabre_DAV_Server::PROP_SET :
                   if (isset($resourceData['properties'][$mutation[1]])) {
                       $result[] = array($mutation[1],200);
                   } else {
                       $result[] = array($mutation[1],201);
                   }
                   $resourceData['properties'][$mutation[1]] = $mutation[2];
                   break;
                case Sabre_DAV_Server::PROP_REMOVE :
                   if (isset($resourceData['properties'][$mutation[1]])) {
                       unset($resourceData['properties'][$mutation[1]]);
                   }
                   // Specifying the deletion of a property that does not exist, is _not_ an error
                   $result[] = array($mutation[1],200);
                   break;

            }

        }

        $this->putResourceData($resourceData);
        return $result;
    }

    /**
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested, encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     * If the array is empty, all properties should be returned
     *
     * @param array $properties 
     * @return void
     */
    function getProperties($properties) {

        $resourceData = $this->getResourceData();

        // if the array was empty, we need to return everything
        if (!$properties) return $resourceData['properties'];

        $props = array();
        foreach($properties as $property) {
            if (isset($resourceData['properties'][$property])) $props[$property] = $resourceData['properties'][$property];
        }

        return $props;

    }

    /**
     * Returns the path to the resource file 
     * 
     * @return string 
     */
    protected function getResourceInfoPath() {

        list($parentDir) = Sabre_DAV_URLUtil::splitPath($this->path);
        return $parentDir . '/.sabredav';

    }

    /**
     * Returns all the stored resource information 
     * 
     * @return array 
     */
    protected function getResourceData() {

        $path = $this->getResourceInfoPath();
        if (!file_exists($path)) return array('locks'=>array(), 'properties' => array());

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
            return array('locks'=>array(), 'properties' => array());
        }

        $data = $data[$this->getName()];
        if (!isset($data['locks'])) $data['locks'] = array();
        if (!isset($data['properties'])) $data['properties'] = array();
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

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    public function setName($name) {

        list($parentPath, ) = Sabre_DAV_URLUtil::splitPath($this->path);
        list(, $newName) = Sabre_DAV_URLUtil::splitPath($name);
        $newPath = $parentPath . '/' . $newName;

        // We're deleting the existing resourcedata, and recreating it
        // for the new path.
        $resourceData = $this->getResourceData();
        $this->deleteResourceData();

        rename($this->path,$newPath);
        $this->path = $newPath;
        $this->putResourceData($resourceData);


    }

    public function deleteResourceData() {

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

    public function delete() {

        return $this->deleteResourceData();

    }

}

