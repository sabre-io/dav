<?php

class Sabre_DAVACL_MockPrincipalBackend implements Sabre_DAVACL_IPrincipalBackend {

    function getPrincipalsByPrefix($prefix) {

        if ($prefix=='principals') {

            return array(
                array(
                    'uri' => 'principals/user1',
                    '{DAV:}displayname' => 'User 1',
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

}
