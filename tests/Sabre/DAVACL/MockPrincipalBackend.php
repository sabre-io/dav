<?php

class Sabre_DAVACL_MockPrincipalBackend implements Sabre_DAVACL_IPrincipalBackend {

    public $groupMembers = array();
    public $principals;

    function __construct() {

        $this->principals = array(
                array(
                    'uri' => 'principals/user1',
                    '{DAV:}displayname' => 'User 1',
                    '{http://sabredav.org/ns}email-address' => 'user1.sabredav@sabredav.org',
                    '{http://sabredav.org/ns}vcard-url' => 'addressbooks/user1/book1/vcard1.vcf',
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

    function getPrincipalsByPrefix($prefix) {

        $prefix = trim($prefix,'/') . '/';
        $return = array();

        foreach($this->principals as $principal) {

            if (strpos($principal['uri'], $prefix)!==0) continue;

            $return[] = $principal;

        }

        return $return;

    }

    function addPrincipal(array $principal) {

        $this->principals[] = $principal;

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
