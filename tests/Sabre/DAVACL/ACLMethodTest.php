<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class ACLMethodTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testCallback() {

        $acl = new Plugin();
        $server = new DAV\Server();
        $server->addPlugin($acl);

        $acl->unknownMethod('ACL','test');

    }

    function testCallbackPassthru() {

        $acl = new Plugin();
        $server = new DAV\Server();
        $server->addPlugin($acl);

        $this->assertNull($acl->unknownMethod('FOO','test'));

    }

    /**

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testNotSupportedByNode() {

        $tree = array(
            new DAV\SimpleCollection('test'),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    function testSuccessSimple() {

        $tree = array(
            new MockACLNode('test',array()),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $this->assertNull($acl->httpACL('test'));

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NotRecognizedPrincipal
     */
    function testUnrecognizedPrincipal() {

        $tree = array(
            new MockACLNode('test',array()),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NotRecognizedPrincipal
     */
    function testUnrecognizedPrincipal2() {

        $tree = array(
            new MockACLNode('test',array()),
            new DAV\SimpleCollection('principals',array(
                new DAV\SimpleCollection('notaprincipal'),
            )),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notaprincipal</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NotSupportedPrivilege
     */
    function testUnknownPrivilege() {

        $tree = array(
            new MockACLNode('test',array()),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:bananas /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\NoAbstract
     */
    function testAbstractPrivilege() {

        $tree = array(
            new MockACLNode('test',array()),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read-acl /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\AceConflict
     */
    function testUpdateProtectedPrivilege() {

        $oldACL = array(
            array(
                'principal' => 'principals/notfound',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ),
        );

        $tree = array(
            new MockACLNode('test',$oldACL),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\AceConflict
     */
    function testUpdateProtectedPrivilege2() {

        $oldACL = array(
            array(
                'principal' => 'principals/notfound',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ),
        );

        $tree = array(
            new MockACLNode('test',$oldACL),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:write /></d:privilege></d:grant>
        <d:principal><d:href>/principals/foo</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    /**
     * @expectedException Sabre\DAVACL\Exception\AceConflict
     */
    function testUpdateProtectedPrivilege3() {

        $oldACL = array(
            array(
                'principal' => 'principals/notfound',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ),
        );

        $tree = array(
            new MockACLNode('test',$oldACL),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:write /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $acl->httpACL('test');

    }

    function testSuccessComplex () {

        $oldACL = array(
            array(
                'principal' => 'principals/foo',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ),
            array(
                'principal' => 'principals/bar',
                'privilege' => '{DAV:}read',
            ),
        );

        $tree = array(
            $node = new MockACLNode('test',$oldACL),
            new DAV\SimpleCollection('principals', array(
                new MockPrincipal('foo','principals/foo'),
                new MockPrincipal('baz','principals/baz'),
            )),
        );
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->httpRequest = new HTTP\Request();
        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:write /></d:privilege></d:grant>
        <d:principal><d:href>/principals/foo</d:href></d:principal>
        <d:protected />
    </d:ace>
    <d:ace>
        <d:grant><d:privilege><d:write /></d:privilege></d:grant>
        <d:principal><d:href>/principals/baz</d:href></d:principal>
    </d:ace>
</d:acl>';
        $server->httpRequest->setBody($body);
        $server->addPlugin($acl);

        $this->assertFalse($acl->unknownMethod('ACL','test'));

        $this->assertEquals(array(
            array(
                'principal' => 'principals/foo',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ),
            array(
                'principal' => 'principals/baz',
                'privilege' => '{DAV:}write',
                'protected' => false,
            ),
        ), $node->getACL());

    }
}
