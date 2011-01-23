<?php

class Sabre_DAVACL_MockPrincipalBackend implements Sabre_DAVACL_IPrincipalBackend {

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
            );

         }

    }

    function getPrincipalByPath($path) {

        foreach($this->getPrincipalsByPrefix('principals') as $principal) {
            if ($principal['uri'] === $path) return $principal;
        }

    }

    function getGroupMemberSet($path) {

        return array();

    }

    function getGroupMembership($path) {

        return array();

    }

    function setGroupMemberSet($path, array $members) {

        throw new Exception('Not implemented');

    }

}
