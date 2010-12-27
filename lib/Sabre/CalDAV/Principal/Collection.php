<?php

class Sabre_CalDAV_Principal_Collection extends Sabre_DAVACL_AbstractPrincipalCollection {

    function getChildForPrincipal(array $principalInfo) {

        return new Sabre_CalDAV_Principal_User($principalInfo);

    }

}
