<?php

class Sabre_CalDAV_Principal_User implements Sabre_DAVACL_IPrincipal, Sabre_DAV_ICollection {

    protected $principalInfo;

    function __construct(array $principalInfo) {

        $this->principalInfo = $principalInfo;

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

        if ($name === 'calendar-proxy-read' ||  $name === 'calendar-proxy-write') {
            return new Sabre_DAV_SimpleDirectory($name);
        }

    }

    /**
     * Returns an array with all the child nodes 
     * 
     * @return Sabre_DAV_INode[] 
     */
    public function getChildren() {

        return array(
            new Sabre_DAV_SimpleDirectory('calendar-proxy-read'),
            new Sabre_DAV_SimpleDirectory('calendar-proxy-write'),
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

        return array();

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
