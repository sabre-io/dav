<?php

interface Sabre_DAVACL_IACL extends Sabre_DAV_INode {

    function getOwner();

    function getGroup();

    function getACL();

    function setACL($acl);

}
