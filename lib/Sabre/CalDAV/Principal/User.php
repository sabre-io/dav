<?php

/**
 * CalDAV principal 
 *
 * This is a standard user-principal for CalDAV. This principal is also a 
 * collection and returns the caldav-proxy-read and caldav-proxy-write child 
 * principals.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Principal_User implements Sabre_DAVACL_IPrincipal, Sabre_DAV_ICollection {

    /**
     * Principal information 
     * 
     * @var array 
     */
    protected $principalInfo;

    /**
     * principalBackend 
     * 
     * @var Sabre_DAV_IPrincipalBackend 
     */
    protected $principalBackend;

    /**
     * Creates the principal 
     * 
     * @param array $principalInfo 
     */
    public function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend, array $principalInfo) {

        $this->principalInfo = $principalInfo;
        $this->principalBackend = $principalBackend;

    }

    /**
     * Returns this principals name.
     * 
     * @return string 
     */
    public function getName() {

        $uri = $this->principalInfo['uri'];
        list(, $name) = Sabre_DAV_URLUtil::splitPath($uri);

        return $name;

    }


    /**
     * Returns the last modification time 
     *
     * @return int 
     */
    public function getLastModified() {

        return null; 

    }

    /**
     * Deleted the current node
     *
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void 
     */
    public function delete() {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to delete node');

    }

    /**
     * Renames the node
     * 
     * @throws Sabre_DAV_Exception_Forbidden
     * @param string $name The new name
     * @return void
     */
    public function setName($name) {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to rename file');

    }

    /**
     * Creates a new file in the directory 
     * 
     * @param string $name Name of the file 
     * @param resource $data Initial payload, passed as a readable stream resource. 
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createFile($name, $data = null) {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $name . ')');

    }

    /**
     * Creates a new subdirectory 
     * 
     * @param string $name 
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createDirectory($name) {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create directory');

    }

    /**
     * Returns a specific child node, referenced by its name 
     * 
     * @param string $name 
     * @return Sabre_DAV_INode 
     */
    public function getChild($name) {

        if ($name === 'calendar-proxy-read')
            return new Sabre_CalDAV_Principal_ProxyRead($this->principalBackend, $this->principalInfo);

        if ($name === 'calendar-proxy-write')
            return new Sabre_CalDAV_Principal_ProxyWrite($this->principalBackend, $this->principalInfo);

        throw new Sabre_DAV_Exception_FileNotFound('Node with name ' . $name . ' was not found');

    }

    /**
     * Returns an array with all the child nodes 
     * 
     * @return Sabre_DAV_INode[] 
     */
    public function getChildren() {

        return array(
            new Sabre_CalDAV_Principal_ProxyRead($this->principalBackend, $this->principalInfo),
            new Sabre_CalDAV_Principal_ProxyWrite($this->principalBackend, $this->principalInfo),
        );

    }

    /**
     * Checks if a child-node with the specified name exists 
     * 
     * @return bool 
     */
    public function childExists($name) {

        return $name === 'calendar-proxy-read' ||  $name === 'calendar-proxy-write';

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
     * Returns the full principal url 
     * 
     * @return string 
     */
    public function getPrincipalUrl() {

        return $this->principalInfo['uri']; 

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
    public function setGroupMemberSet(array $principals) {

        throw new Sabre_DAV_Exception_Forbidden('Updating the group member-set is not yet supported');

    }

    /**
     * Returns the displayname
     *
     * This should be a human readable name for the principal.
     * If none is available, return the nodename. 
     * 
     * @return string 
     */
    public function getDisplayName() {

        return isset($this->principalInfo['{DAV:}displayname'])?$this->principalInfo['{DAV:}displayname']:$this->getName();

    }


}
