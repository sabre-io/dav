<?php

require_once 'Sabre/DAVACL/AbstractServer.php';

class Sabre_DAVACL_PrincipalPropertiesTest extends Sabre_DAVACL_AbstractServer {

    function testAlternateUriSet() {

        $this->markTestSkipped();
        $this->aclBackend->setACL('principals/testuser1',array('testuser1' => array('{DAV:}read')));
        $props = $this->server->getPropertiesForPath('principals/testuser1',array('{DAV:}alternate-URI-set'));
        print_r($props);
        $this->assertArrayHasKey(0,$props);
        $this->assertArrayHasKey(200,$props[0]);
        $this->assertArrayHasKey('{DAV:}alternate-URI-set',$props[0][200]);

    }

}

?>
