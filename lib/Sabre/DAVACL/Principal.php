<?php

/**
 * Principal class
 *
 * This class is a representation of a simple principal
 * 
 * Many WebDAV specs require a user to show up in the directory 
 * structure. 
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Principal extends Sabre_DAV_Node implements Sabre_DAVACL_IPrincipal, Sabre_DAV_IProperties {

    /**
     * Struct with principal information.
     *
     * @var array 
     */
    protected $principalProperties;

    /**
     * Creates the principal object 
     *
     * @param array $principalProperties
     */
    public function __construct(array $principalProperties = array()) {

        if (!isset($principalProperties['uri'])) {
            throw new Sabre_DAV_Exception('The principal properties must at least contain the \'uri\' key');
        }
        $this->principalProperties = $principalProperties;

    }

    /**
     * Returns the full principal url 
     * 
     * @return string 
     */
    public function getPrincipalUrl() {

        return $this->principalProperties['uri'];

    } 

    /**
     * Returns a list of altenative urls for a principal
     * 
     * This can for example be an email address, or ldap url.
     * 
     * @return array 
     */
    public function getAlternateUriSet() {

        if (isset($this->principalProperties['{http://sabredav.org/ns}email-address'])) {
            return array('mailto:' . $this->principalProperties['{http://sabredav.org/ns}email-address']);
        } else {
            return array();
        }

    }

    /**
     * Returns the list of group members
     * 
     * If this principal is a group, this function should return
     * all member principal uri's for the group. 
     * 
     * @return array
     */
    public function getGroupMemberSet() {

        return array();

    }

    /**
     * Returns the list of groups this principal is member of
     * 
     * If this principal is a member of a (list of) groups, this function
     * should return a list of principal uri's for it's members. 
     * 
     * @return array 
     */
    public function getGroupMembership() {

        return array();

    }


    /**
     * Sets a list of group members
     *
     * If this principal is a group, this method sets all the group members.
     * The list of members is always overwritten, never appended to.
     * 
     * This method should throw an exception if the members could not be set. 
     * 
     * @param array $principals 
     * @return void 
     */
    public function setGroupMemberSet(array $groupMembers) {

        throw new Sabre_DAV_Exception_Forbidden('This principal does not allow setting group members');

    }


    /**
     * Returns the name of the element 
     * 
     * @return void
     */
    public function getName() {

        list(, $name) = Sabre_DAV_URLUtil::splitPath($this->principalProperties['uri']);
        return $name;

    }

    /**
     * Returns the name of the user 
     * 
     * @return void
     */
    public function getDisplayName() {

        if (isset($this->principalProperties['{DAV:}displayname'])) {
            return $this->principalProperties['{DAV:}displayname'];
        } else {
            return $this->getName();
        }

    }

    /**
     * Returns a list of properties 
     * 
     * @param array $requestedProperties 
     * @return void
     */
    public function getProperties($requestedProperties) {

        $newProperties = array();
        foreach($requestedProperties as $propName) {
            
            if (isset($this->principalProperties[$propName])) {
                $newProperties[$propName] = $this->principalProperties[$propName];
            }

        }

        return $newProperties;
        
    }

    /**
     * Updates this principals properties.
     *
     * Currently this is not supported
     * 
     * @param array $properties
     * @see Sabre_DAV_IProperties::updateProperties
     * @return bool|array 
     */
    public function updateProperties($properties) {

        return false;

    }

}
