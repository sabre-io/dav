<?php

/**
 * Principals Collection
 *
 * This collection represents a list of users. It uses
 * Sabre_DAV_Auth_Backend to determine which users are available on the list.
 *
 * The users themselves are instances of Sabre_DAVACL_Principal
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_PrincipalsCollection extends Sabre_DAV_Directory {

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
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend) {

        $this->authBackend = $authBackend;

    }

    /**
     * Returns the name of this collection. 
     * 
     * @return string 
     */
    public function getName() {

        return Sabre_DAVACL_Plugin::PRINCIPAL_ROOT; 

    }

    /**
     * Retursn the list of users 
     * 
     * @return void
     */
    public function getChildren() {

        $children = array();
        foreach($this->authBackend->getUsers() as $principalInfo) {

            $principalUri = $this->getName() . '/' . $principalInfo['userId'];
            $children[] = new Sabre_DAVACL_Principal($principalUri,$principalInfo);


        }
        return $children; 

    }

}

?>
