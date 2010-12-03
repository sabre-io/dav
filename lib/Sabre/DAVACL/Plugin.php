<?php

/**
 * SabreDAV ACL Plugin
 *
 * This plugin provides funcitonality to enforce ACL permissions.
 * ACL is defined in RFC3744. 
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to server object. 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * Returns a list of features added by this plugin.
     *
     * This list is used in the response of a HTTP OPTIONS request.
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('access-control');

    }

    /**
     * Returns a list of available methods for a given url 
     * 
     * @param string $uri 
     * @return array 
     */
    public function getMethods($uri) {

        // TODO: needs to support ACL.
        return array();

    }

    /**
     * Checks if the current user has the specified privilege(s). 
     * 
     * You can specify a single privilege, or a list of privileges.
     * This method will throw an exception if the privilege is not available
     * and return true otherwise.
     *
     * @param string $uri
     * @param array|string $privileges
     * @throws Sabre_DAVACL_Exception_NeedPrivileges
     * @return bool 
     */
    public function checkPrivileges($uri,$privileges,$recursive) {

        if (!is_array($privileges)) $privileges = array($privileges);            
        throw new Sabre_DAVACL_Exception_NeedPrivileges($uri,$privileges);

    } 

    /**
     * Sets up the plugin
     *
     * This method is automatically called by the server class.
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('beforeMethod',array($this,'beforeRead'),20);
        $server->subscribeEvent('beforeBind',  array($this,'beforeBind'),20);
        $server->subscribeEvent('afterGetProperties', array($this,'afterGetProperties',220));
        $server->subscribeEvent('beforeUnlock', array($this,'beforeUnlock'),20);

    }

    /* {{{ Event handlers */

    /**
     * Triggered before any method is handled 
     * 
     * @param string $method 
     * @param string $uri 
     * @return void
     */
    public function beforeMethod($method, $uri) {

        $exists = $this->server->tree->nodeExists($uri);

        switch($method) {

            case 'GET' :
            case 'HEAD' :
            case 'OPTIONS' :
                // For these 3 we only need to know if the node is readable.
                // We only check if the node exist. 
                if ($exists) 
                    $this->checkPrivileges($uri,'{DAV:}read');

                break;

            case 'PUT' :
            case 'LOCK' :
                // This method requires the write-content priv if the node 
                // already exists, and bind on the parent if the node is being 
                // created. 
                // The bind privilege is handled in the beforeBind event. 
                if ($exists)
                    $this->checkPrivileges($uri,'{DAV:}write-content');

                break;
            

            case 'PROPPATCH' :
                if ($exists)
                    $this->checkPrivileges($uri,'{DAV:}write-properties');

                break;

            case 'ACL' :
                if ($exists)
                    $this->checkPrivileges($uri,'{DAV:}write-acl');

                break;

            case 'COPY' :
            case 'MOVE' :
                // Copy requires read privileges on the entire source tree.
                // If the target exists write-content normally needs to be 
                // checked, however, we're deleting the node beforehand and 
                // creating a new one after, so this is handled by the 
                // beforeUnbind event.
                // 
                // The creation of the new node is handled by the beforeBind 
                // event.
                //
                // If MOVE is used beforeUnbind will also be used to check if 
                // the sourcenode can be deleted. 
                if ($exists)
                    $this->checkPrivileges($uri,'{DAV:}read',true);

                break;

        }

    }

    /**
     * Triggered before a new node is created.
     * 
     * This allows us to check permissions for any operation that creates a
     * new node, such as PUT, MKCOL and MKCALENDAR. 
     * 
     * @param string $uri 
     * @return void
     */
    public function beforeBind($uri) {

        list($parentUri,$nodeName) = Sabre_DAV_URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}bind');

    }

    /**
     * Triggered after all properties are received.
     * 
     * This allows us to deny access to any properties if there's no
     * permission to grab them. 
     * 
     * @param string $uri 
     * @param array $properties 
     * @return void
     */
    public function afterGetProperties($uri, array &$properties) {

        try {

            $this->checkPrivileges($uri,'{DAV:}read');
    
        } catch (Sabre_DAVACL_NeedPrivileges $e) {

            // Access to properties was denied
            
            if (!isset($properties[403])) $properties[403] = array();
            foreach($properties as $httpStatus=>$properties) {

                // The odd one out
                if ($httpStatus === 'href') continue;

                // No need to do anything if they are already 403
                if ($httpStatus == 403) continue;

                foreach($properties as $propName=>$propValue) {

                    $properties[403][$propName] = null;

                }

                unset($properties[$httpStatus]); 

            }

        }

        // We need to do specific checks for the {DAV:}acl and 
        // {DAV:}current-user-privilege-set properties.
        if (isset($properties[200]['{DAV:}acl'])) {

            try {

                $this->checkPrivileges($uri,'{DAV:}read-acl');

            } catch (Sabre_DAVACL_NeedPrivileges $e) {

                if (!isset($properties[403])) $properties[403] = array();

                $properties[403]['{DAV:}acl'] = null;
                unset($properties[200]['{DAV:}acl']);

            }

        }
        if (isset($properties[200]['{DAV:}read-current-user-privilege-set'])) {

            try {

                $this->checkPrivileges($uri,'{DAV:}read-acl');

            } catch (Sabre_DAVACL_NeedPrivileges $e) {

                if (!isset($properties[403])) $properties[403] = array();

                $properties[403]['{DAV:}acl'] = null;
                unset($properties[200]['{DAV:}acl']);

            }

        }


    }

    /**
     * Triggered before a node is unlocked. 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lock
     * @param TODO: not yet implemented 
     * @return void
     */
    public function beforeUnlock($uri, Sabre_DAV_Locks_LockInfo $lock) {
           

    }

    /* }}} */

}
