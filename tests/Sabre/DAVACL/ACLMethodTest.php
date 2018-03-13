<?php declare (strict_types=1);

namespace Sabre\DAVACL;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\HTTP;

class ACLMethodTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testCallback() {

        $acl = new Plugin();
        $server = new DAV\Server();
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);

        $acl->httpACL(new DAV\Psr7RequestWrapper(new ServerRequest('GET', '/')), $server->httpResponse);

    }

    /**
     /**
     * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
     */
    function testNotSupportedByNode() {

        $tree = [
            new DAV\SimpleCollection('test'),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
</d:acl>';
        $request = new ServerRequest('GET', '/', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    function testSuccessSimple() {

        $tree = [
            new MockACLNode('test', []),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);


        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
</d:acl>';
        $request = new ServerRequest('GET', '/test', [], $body);

        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $this->assertFalse($acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse));

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NotRecognizedPrincipal
     */
    function testUnrecognizedPrincipal() {

        $tree = [
            new MockACLNode('test', []),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NotRecognizedPrincipal
     */
    function testUnrecognizedPrincipal2() {

        $tree = [
            new MockACLNode('test', []),
            new DAV\SimpleCollection('principals', [
                new DAV\SimpleCollection('notaprincipal'),
            ]),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notaprincipal</d:href></d:principal>
    </d:ace>
</d:acl>';
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NotSupportedPrivilege
     */
    function testUnknownPrivilege() {

        $tree = [
            new MockACLNode('test', []),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:bananas /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\NoAbstract
     */
    function testAbstractPrivilege() {

        $tree = [
            new MockACLNode('test', []),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->on('getSupportedPrivilegeSet', function($node, &$supportedPrivilegeSet) {
            $supportedPrivilegeSet['{DAV:}foo'] = ['abstract' => true];
        });

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:foo /></d:privilege></d:grant>
        <d:principal><d:href>/principals/foo/</d:href></d:principal>
    </d:ace>
</d:acl>';
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\AceConflict
     */
    function testUpdateProtectedPrivilege() {

        $oldACL = [
            [
                'principal' => 'principals/notfound',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ],
        ];

        $tree = [
            new MockACLNode('test', $oldACL),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:read /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\AceConflict
     */
    function testUpdateProtectedPrivilege2() {

        $oldACL = [
            [
                'principal' => 'principals/notfound',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ],
        ];

        $tree = [
            new MockACLNode('test', $oldACL),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:write /></d:privilege></d:grant>
        <d:principal><d:href>/principals/foo</d:href></d:principal>
    </d:ace>
</d:acl>';
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    /**
     * @expectedException \Sabre\DAVACL\Exception\AceConflict
     */
    function testUpdateProtectedPrivilege3() {

        $oldACL = [
            [
                'principal' => 'principals/notfound',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ],
        ];

        $tree = [
            new MockACLNode('test', $oldACL),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

        $body = '<?xml version="1.0"?>
<d:acl xmlns:d="DAV:">
    <d:ace>
        <d:grant><d:privilege><d:write /></d:privilege></d:grant>
        <d:principal><d:href>/principals/notfound</d:href></d:principal>
    </d:ace>
</d:acl>';
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);
        $server->handle($request);
        $acl->httpACL(new DAV\Psr7RequestWrapper($request), $server->httpResponse);

    }

    function testSuccessComplex() {

        $oldACL = [
            [
                'principal' => 'principals/foo',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ],
            [
                'principal' => 'principals/bar',
                'privilege' => '{DAV:}read',
            ],
        ];

        $tree = [
            $node = new MockACLNode('test', $oldACL),
            new DAV\SimpleCollection('principals', [
                new MockPrincipal('foo', 'principals/foo'),
                new MockPrincipal('baz', 'principals/baz'),
            ]),
        ];
        $acl = new Plugin();
        $server = new DAV\Server($tree);

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
        $request = new ServerRequest('ACL', '/test', [], $body);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin($acl);


        $this->assertFalse($acl->httpAcl(new DAV\Psr7RequestWrapper($request), $server->httpResponse));

        $this->assertEquals([
            [
                'principal' => 'principals/foo',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ],
            [
                'principal' => 'principals/baz',
                'privilege' => '{DAV:}write',
                'protected' => false,
            ],
        ], $node->getACL());

    }
}
