<?php

/**
 * Principals Collection
 *
 * You can use this abstract collection to automatically create a collection
 * that lists all users. The node returned for each user can be specified
 * by yourself, by implementing the getChildForPrincipal method.
 *
 * The users are instances of Sabre_DAV_Auth_Principal
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Auth_AbstractPrincipalCollection extends Sabre_DAV_Directory {

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

        $children = array();
        foreach($this->authBackend->getUsers() as $principalInfo) {

            $principalUri = $principalInfo['uri'] . '/';
            $children[] = $this->getChildForPrincipal($principalInfo);


        }
        return $children; 

    }

}
