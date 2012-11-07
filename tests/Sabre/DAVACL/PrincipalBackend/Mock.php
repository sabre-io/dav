<?php

namespace Sabre\DAVACL\PrincipalBackend;

class Mock extends AbstractBackend {

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

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is supplied as an array. Each key in the array is
     * a propertyname, such as {DAV:}displayname.
     *
     * Each value is the actual value to be updated. If a value is null, it
     * must be deleted.
     *
     * This method should be atomic. It must either completely succeed, or
     * completely fail. Success and failure can simply be returned as 'true' or
     * 'false'.
     *
     * It is also possible to return detailed failure information. In that case
     * an array such as this should be returned:
     *
     * array(
     *   200 => array(
     *      '{DAV:}prop1' => null,
     *   ),
     *   201 => array(
     *      '{DAV:}prop2' => null,
     *   ),
     *   403 => array(
     *      '{DAV:}prop3' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}prop4' => null,
     *   ),
     * );
     *
     * In this previous example prop1 was successfully updated or deleted, and
     * prop2 was succesfully created.
     *
     * prop3 failed to update due to '403 Forbidden' and because of this prop4
     * also could not be updated with '424 Failed dependency'.
     *
     * This last example was actually incorrect. While 200 and 201 could appear
     * in 1 response, if there's any error (403) the other properties should
     * always fail with 423 (failed dependency).
     *
     * But anyway, if you don't want to scratch your head over this, just
     * return true or false.
     *
     * @param string $path
     * @param array $mutations
     * @return array|bool
     */
    public function updatePrincipal($path, $mutations) {

        $value = null;
        foreach($this->principals as $principalIndex=>$value) {
            if ($value['uri'] === $path) {
                $principal = $value;
                break;
            }
        }
        if (!$principal) return false;

        foreach($mutations as $prop=>$value) {

            if (is_null($value) && isset($principal[$prop])) {
                unset($principal[$prop]);
            } else {
                $principal[$prop] = $value;
            }

        }

        $this->principals[$principalIndex] = $principal;

        return true;

    }


}
