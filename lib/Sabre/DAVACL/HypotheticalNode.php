<?php

class Sabre_DAVACL_HypotheticalNode extends Sabre_DAV_Node {

    public function checkPermission($userId, $privilege) {

        $concretePermission = $this->getConcretePermission($privilege);

    }

    /**
     * Returns a list of privileges for a specific user 
     * 
     * @param string $userUri 
     * @return array 
     */
    public function getPrivilegesForUser($userUri) {

    }

    /**
     * Returns the full list of privileges 
     * 
     * @return array 
     */
    public function getACL() {

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

    /**
     * Returns the concrete privilege for a privilege.
     *
     * This is used for grouped privileges. If a privilege is abstract, the arent
     * privilege will be returned.
     * 
     * @param string $privilege 
     * @return string 
     */
    public function getConcretePrivilege($privilege) {

        $privilegeTree = $this->getSupportedPrivileges();
        
        // Turning the tree into a flat list
        $privilegeList = array();

        $flatArray = function($list,$parent = null) use ($privilegeList, $flatArray) {
            foreach($privilegeTree as $privName=>$privDetail) {
                $privDetail['parent'] = $parent;
                $privilegeList[$privName] = $privDetail;
                if (isset($privDetail['privileges']) && count($privDetail['privileges'])>0)
                    $flatArray($privDetail['privileges'],$privName);
                

            } 
        };

        while(true) {

            if (!isset($privilegeList[$privilege])) return null;
            if (!isset($privilegeList[$privilege]['abstract']) || !$privilegeList[$privilege]['abstract']) return $privilege;

            // It was abstract, run the loop again on the parent
            $privilege = $privilegeList[$privilege]['parent'];

        }

    }

}
