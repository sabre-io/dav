<?php

class Sabre_DAVACL_MockPrincipalBackend implements Sabre_DAVACL_IPrincipalBackend {

    public $groupMembers = array();

    function getPrincipalsByPrefix($prefix) {

        if ($prefix=='principals') {

            return array(
                array(
                    'uri' => 'principals/user1',
                    '{DAV:}displayname' => 'User 1',
                    '{http://sabredav.org/ns}email-address' => 'user1.sabredav@sabredav.org',
                ),
                array(
                    'uri' => 'principals/admin',
                    '{DAV:}displayname' => 'Admin',
                ),
                array(
                    'uri' => 'principals/user2',
                    '{DAV:}displayname' => 'User 2',
                    '{http://sabredav.org/ns}email-address' => 'user2.sabredav@sabredav.org',
                ),
            );

         }

    }

    function getPrincipalByPath($path) {

        foreach($this->getPrincipalsByPrefix('principals') as $principal) {
            if ($principal['uri'] === $path) return $principal;
        }

    }

    function searchPrincipals($prefixPath, array $searchProperties) {

        $matches = array();
        foreach($this->getPrincipalsByPrefix($prefixPath) as $principal) {

            foreach($searchProperties as $key=>$value) {

                if (!isset($principal[$key])) {
                    continue 2;
                }
                if (mb_stripos($principal[$key],$value, 0, 'UTF-8')===false) {
                    continue 2;
                }

            }
            $matches[] = $principal['uri'];

        }
        return $matches;

    }

    function getGroupMemberSet($path) {

        return isset($this->groupMembers[$path]) ? $this->groupMembers[$path] : array();

    }

    function getGroupMembership($path) {

        $membership = array();
        foreach($this->groupMembers as $group=>$members) {
            if (in_array($path, $members)) $membership[] = $group;
        } 
        return $membership; 

    }

    function setGroupMemberSet($path, array $members) {

        $this->groupMembers[$path] = $members;

    }

}
