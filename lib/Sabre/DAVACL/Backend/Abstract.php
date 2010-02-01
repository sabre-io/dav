<?php

/**
 * Abstract ACL backend
 *
 * This class must be extended to define your own ACL backend.
 * An ACL backend maintains all the access control lists on a per-user basis.
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAVACL_Backend_Abstract {

    /**
     * Returns a list of privileges available to a specific principal 
     *
     * The list should be an array with privileges in clark-notation.
     * If the principal has no permissions to perform on the uri, it should return
     * an empty array.
     *
     * @param string $uri 
     * @param string $userId 
     * @return array 
     */
    abstract function getPrivilegesForPrincipal($uri,$principal);

    /**
     * Returns the full list of access control entries for the specified uri
     *
     * @param string $uri
     * @return array
     */
    abstract function getACL($uri); 

    /**
     * Returns the full list of access control entries for the specified uri
     *
     * @param string $uri;
     * @return array
     */
    abstract function setACL($uri,array $privileges); 

    /**
     * Checks if a principal has privileges for a specific uri. 
     *
     * This function simply returns true or false, and only if a principal 
     * has all the privileges from the privileges array.
     *
     * If a user does not have some of the privileges, 1 or more of these 
     * must be added to the failedPriviliges array, which is passed as an
     * array.
     *
     * @param string $uri
     * @param string $userId
     * @param array $permission 
     * @param array $failedPermissions 
     * @return bool 
     */
    public function checkPrivilege($uri,$principal, array $privileges, array &$failedPrivileges) {

        $currentPrivileges = $this->getPrivilegesForPrincipal($uri,$principal);

        $success = true;
        foreach($privileges as $priv) {
            if (!in_array($priv,$currentPrivileges)) {
                $success = false;
                $failedPrivileges[] = $priv;
            }
        }
        return $success; 

    }



    /**
     * Returns a list of privileges available to the user.
     *
     * If the default privilege model does not suffice for the user,
     * this should be changed. In most cases the default model should
     * provide enough flexibility.
     * 
     * @return array 
     */
    public function getSupportedPrivileges() {

        return array(
            '{DAV:}all' => array(
                'abstract'  => 1,
                'description' => 'Any operation',
                'privileges' => array(
                    '{DAV:}read' => array(
                        'description' => 'Reading files and properties',
                        'privileges' => array(
                            '{DAV:}read-acl' => array(
                                'abstract' => 1,
                            ),
                            '{DAV:}read-current-user-privilege-set' => array(
                                'abstract' => 1,
                            ),
                            '{urn:ietf:params:xml:ns:caldav}read-free-busy' => array(
                                'abstract' => 1,
                            ),
                        ),
                    ),
                    '{DAV:}write' => array(
                        'description' => 'Altering any resources state',
                        'abstract' => 1,
                        'privileges' => array(
                            '{DAV:}write-acl' => array(
                                'description' => 'Update a resource\'s ACL',
                            ),
                            '{DAV:}write-properties' => array(
                                'description' => 'Update a resource\'s dead properties',
                            ),
                            '{DAV:}write-content' => array(
                                'description' => 'Update a resource\'s contents',
                            ),
                            '{DAV:}bind' => array(
                                'description' => 'Create a new resource within this collection',
                            ),
                            '{DAV:}unbind' => array(
                                'description' => 'Delete a resource from this collection',
                            ),
                        ),
                    ),
                    '{DAV:}unlock' => array(
                        'description' => 'Remove another principals locks from this resource',
                    ),
                ),
            ));
    }

    public function getFlatPrivilegeList() {

        $privs = $this->getSupportedPrivileges();
    
        $flatList = array();
        $scan = function ($privileges,$self,$concretePriv = null) use (&$flatList) {
            foreach($privileges as $privName=>$privInfo) {

                $flatList[$privName] = $privInfo;
                $flatList[$privName]['privileges'] = array_keys($privInfo);

                if (!isset($privInfo['abstract'])) $privInfo['abstract'] = false;
                if ($privInfo['abstract']) 
                    $flatList[$privName]['concrete'] = $concretePriv;
                else
                    $concretePriv = $privName; 

                if (isset($privInfo['privileges']) && count($privInfo['privileges'])>0)
                    $self($privInfo['privileges'],$self,$concretePriv);

            } 
        };

        $scan($privs,$scan);

        return $flatList;

    }

    public function getPrivilegeInfo($privilege) {

        $list = $this->getFlatPrivilegeList();
        if (isset($list[$privilege])) {
            $privInfo = $list[$privilege];
            return $privInfo;
        } else { 
            return false; 
        }

    }

    public function setInitialPrivileges($uri,$currentPrincipal) {

        $this->setACL($uri,array(array(
            'principal' => $currentPrincipal,
            'special'   => 0,
            'grant' => array(
                '{DAV:}read',
                '{DAV:}write-acl',
                '{DAV:}write-properties',
                '{DAV:}write-content',
                '{DAV:}bind',
                '{DAV:}unbind',
                '{DAV:}unlock',
            ),
        )));

    }

}
