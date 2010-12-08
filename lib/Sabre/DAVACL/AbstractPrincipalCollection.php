<?php

/**
 * Principals Collection
 *
 * This is a helper class that easily allows you to create a collection that 
 * has a childnode for every principal.
 * 
 * To use this class, simply implement the getChildForPrincipal method. 
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAVACL_AbstractPrincipalCollection extends Sabre_DAV_Directory  {

    /**
     * Disallows users to access other users except themselves. 
     *
     * @var bool 
     */
    public $disallowListing = false;

    /**
     * Node or 'directory' name. 
     * 
     * @var string 
     */
    protected $nodeName;

    /**
     * Authentication backend 
     * 
     * @var Sabre_DAV_Auth_Backend 
     */
    protected $authBackend;

    /**
     * Creates the object 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend, $nodeName = 'principals') {

        $this->nodeName = $nodeName;
        $this->authBackend = $authBackend;

    }

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     * 
     * @param array $principal 
     * @return Sabre_DAV_INode 
     */
    abstract function getChildForPrincipal(array $principal);

    /**
     * Returns the name of this collection. 
     * 
     * @return string 
     */
    public function getName() {

        return $this->nodeName; 

    }

    /**
     * Return the list of users 
     * 
     * @return void
     */
    public function getChildren() {

        if ($this->disallowListing) 
            throw new Sabre_DAV_Exception_MethodNotAllowed('You are not allowed to list principals');

        $children = array();
        foreach($this->authBackend->getUsers() as $principalInfo) {

            $children[] = $this->getChildForPrincipal($principalInfo);


        }
        return $children; 

    }

    /**
     * Returns a child object, by its name.
     * 
     * @param string $name
     * @throws Sabre_DAV_Exception_FileNotFound
     * @return Sabre_DAV_INode 
     */
    public function getChild($name) {

        if ($this->disallowListing) {
            $currentUser = $this->authBackend->getCurrentUser();
            
            // Not logged in
            if (is_null($currentUser)) {
                throw new Sabre_DAV_Exception_Forbidden('Access denied to this principal');
            }

            list(, $currentUserName) = Sabre_DAV_URLUtil::splitPath($currentUser['uri']);

            // Not the current user
            if ($currentUserName!==$name) {
                throw new Sabre_DAV_Exception_Forbidden('Access denied to this principal');
            }
            return $this->getChildForPrincipal($currentUser);
        }
        return parent::getChild($name);

    }

}
