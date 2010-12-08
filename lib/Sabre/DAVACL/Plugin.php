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
     * Recursion constants
     *
     * This only checks the base node
     */
    const R_PARENT = 1;

    /**
     * Recursion constants
     *
     * This checks every node in the tree
     */
    const R_RECURSIVE = 2;

    /**
     * Recursion constants
     *
     * This checks every parentnode in the tree, but not leaf-nodes.
     */
    const R_RECURSIVEPARENTS = 3;

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
     * Returns a plugin name.
     * 
     * Using this name other plugins will be able to access other plugins
     * using Sabre_DAV_Server::getPlugin 
     * 
     * @return string 
     */
    public function getPluginName() {

        return 'acl';

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
    public function checkPrivileges($uri,$privileges,$recursion = self::R_PARENT) {

        if (!is_array($privileges)) $privileges = array($privileges);
        throw new Sabre_DAVACL_Exception_NeedPrivileges($uri,$privileges);

    }

    /**
     * Returns a list of principals that's associated to the current
     * user, either directly or through group membership. 
     * 
     * @return array 
     */
    public function getCurrentUserPrincipals() {

        $authPlugin = $this->server->getPlugin('auth');
        $currentUser = $authPlugin->getCurrentUserPrincipal();

        $check = array($currentUser);
        $principals = array($currentUser);

        while(count($check)) {

            $principal = array_shift($check);
            
            $node = $this->server->objectTree->getNodeForPath($principal);
            if ($node instanceof Sabre_DAVACL_Principal) {
                foreach($node->getGroupMembership() as $groupMember) {

                    if (!in_array($groupMember, $principals)) {

                        $check[] = $groupMember;
                        $principals[] = $groupMember;

                    }

                }

            }

        }

        return $principals;

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
        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));

        $server->subscribeEvent('beforeMethod', array($this,'beforeMethod'),20);
        $server->subscribeEvent('beforeBind', array($this,'beforeBind'),20);
        $server->subscribeEvent('beforeUnbind', array($this,'beforeUnbind'),20);
        $server->subscribeEvent('afterGetProperties', array($this,'afterGetProperties'),220);
        $server->subscribeEvent('beforeUnlock', array($this,'beforeUnlock'),20);

        array_push($server->protectedProperties,
            '{DAV:}alternate-URI-set',
            '{DAV:}principal-URL',
            '{DAV:}group-membership'
        );

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

        // If the node doesn't exists, none of these checks apply
        if (!$exists) return;

        switch($method) {

            case 'GET' :
            case 'HEAD' :
            case 'OPTIONS' :
                // For these 3 we only need to know if the node is readable.
                $this->checkPrivileges($uri,'{DAV:}read');
                break;

            case 'PUT' :
            case 'LOCK' :
                // This method requires the write-content priv if the node 
                // already exists, and bind on the parent if the node is being 
                // created. 
                // The bind privilege is handled in the beforeBind event. 
                $this->checkPrivileges($uri,'{DAV:}write-content');
                break;
            

            case 'PROPPATCH' :
                $this->checkPrivileges($uri,'{DAV:}write-properties');
                break;

            case 'ACL' :
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
                $this->checkPrivileges($uri,'{DAV:}read',self::R_RECURSIVE);

                break;

        }

    }

    /**
     * Triggered before a new node is created.
     * 
     * This allows us to check permissions for any operation that creates a
     * new node, such as PUT, MKCOL, MKCALENDAR, LOCK, COPY and MOVE.
     * 
     * @param string $uri 
     * @return void
     */
    public function beforeBind($uri) {

        list($parentUri,$nodeName) = Sabre_DAV_URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}bind');

    }

    /**
     * Triggered before a node is deleted 
     * 
     * This allows us to check permissions for any operation that will delete 
     * an existing node. 
     * 
     * @param string $uri 
     * @return void
     */
    public function beforeUnbind($uri) {

        list($parentUri,$nodeName) = Sabre_DAV_URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}unbind',self::R_RECURSIVEPARENTS);

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
    
        } catch (Sabre_DAVACL_Exception_NeedPrivileges $e) {

            // Access to properties was denied
            
            if (!isset($properties[403])) $properties[403] = array();
            foreach($properties as $httpStatus=>$propList) {

                // The odd one out
                if ($httpStatus === 'href') continue;

                // No need to do anything if they are already 403
                if ($httpStatus == 403) continue;

                foreach($propList as $propName=>$propValue) {

                    $properties[403][$propName] = null;

                }

                unset($properties[$httpStatus]); 

            }
            return;

        }

        // We need to do specific checks for the {DAV:}acl and 
        // {DAV:}current-user-privilege-set properties.
        if (isset($properties[200]['{DAV:}acl'])) {

            try {

                $this->checkPrivileges($uri,'{DAV:}read-acl');

            } catch (Sabre_DAVACL_Exception_NeedPrivileges $e) {

                if (!isset($properties[403])) $properties[403] = array();

                $properties[403]['{DAV:}acl'] = null;
                unset($properties[200]['{DAV:}acl']);

            }

        }
        if (isset($properties[200]['{DAV:}read-current-user-privilege-set'])) {

            try {

                $this->checkPrivileges($uri,'{DAV:}read-acl');

            } catch (Sabre_DAVACL_Exception_NeedPrivileges $e) {

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

    /**
     * Triggered before properties are looked up in specific nodes. 
     * 
     * @param string $uri 
     * @param Sabre_DAV_INode $node 
     * @param array $requestedProperties 
     * @param array $returnedProperties 
     * @return void
     */
    public function beforeGetProperties($uri, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal) {

            if (false !== ($index = array_search('{DAV:}alternate-URI-set', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}alternate-URI-set'] = new Sabre_DAV_Property_HrefList($node->getAlternateUriSet());

            }
            if (false !== ($index = array_search('{DAV:}principal-URL', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}principal-URL'] = new Sabre_DAV_Property_Href($node->getPrincipalUrl());

            }
            if (false !== ($index = array_search('{DAV:}group-member-set', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}group-member-set'] = new Sabre_DAV_Property_HrefList($node->getGroupMemberSet());

            }
            if (false !== ($index = array_search('{DAV:}group-membership', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}group-membership'] = new Sabre_DAV_Property_HrefList($node->getGroupMembership());

            }

            if (false !== ($index = array_search('{DAV:}resourcetype', $requestedProperties))) {

                $returnedProperties[200]['{DAV:}resourcetype'] = new Sabre_DAV_Property_ResourceType('{DAV:}principal');

            }

        }

    }

    /* }}} */

}
