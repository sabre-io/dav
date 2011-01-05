<?php

class Sabre_CalDAV_Principal_ProxyRead implements Sabre_DAVACL_IPrincipal {

    protected $principalInfo;
    protected $principalBackend;

    function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend, array $principalInfo) {

        $this->principalInfo = $principalInfo;
        $this->principalBackend = $principalBackend;

    }

    /**
     * Returns this principals name.
     * 
     * @return string 
     */
    public function getName() {

        return 'calendar-proxy-read';

    }

    /**
     * Returns the last modification time 
     *
     * In this case, it will simply return the current time
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
     * Returns a list of altenative urls for a principal
     * 
     * This can for example be an email address, or ldap url.
     * 
     * @return array 
     */
    public function getAlternateUriSet() {

        return array();

    }

    /**
     * Returns the full principal url 
     * 
     * @return string 
     */
    public function getPrincipalUrl() {

        return $this->principalInfo['uri'] . '/' . $this->getName(); 

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

        return $this->principalBackend->getGroupMemberSet($this->getPrincipalUrl()); 

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

        return $this->principalBackend->getGroupMembership($this->getPrincipalUrl()); 

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

        return $this->getName();

    }

}
