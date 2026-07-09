<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class PluginPropertiesTest extends \PHPUnit\Framework\TestCase
{
    public function testPrincipalCollectionSet()
    {
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        // Anyone can do anything
        $plugin->principalCollectionSet = [
            'principals1',
            'principals2',
        ];

        $requestedProperties = [
            '{DAV:}principal-collection-set',
        ];

        $server = new DAV\Server(new DAV\SimpleCollection('root'));
        $server->addPlugin($plugin);

        $result = $server->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        self::assertEquals(1, count($result[200]));
        self::assertArrayHasKey('{DAV:}principal-collection-set', $result[200]);
        self::assertInstanceOf(DAV\Xml\Property\Href::class, $result[200]['{DAV:}principal-collection-set']);

        $expected = [
            'principals1/',
            'principals2/',
        ];

        self::assertEquals($expected, $result[200]['{DAV:}principal-collection-set']->getHrefs());
    }

    public function testCurrentUserPrincipal()
    {
        $fakeServer = new DAV\Server();
        $plugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}current-user-principal',
        ];

        $result = $fakeServer->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        self::assertEquals(1, count($result[200]));
        self::assertArrayHasKey('{DAV:}current-user-principal', $result[200]);
        self::assertInstanceOf(Xml\Property\Principal::class, $result[200]['{DAV:}current-user-principal']);
        self::assertEquals(Xml\Property\Principal::UNAUTHENTICATED, $result[200]['{DAV:}current-user-principal']->getType());

        // This will force the login
        $fakeServer->emit('beforeMethod:PROPFIND', [$fakeServer->httpRequest, $fakeServer->httpResponse]);

        $result = $fakeServer->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        self::assertEquals(1, count($result[200]));
        self::assertArrayHasKey('{DAV:}current-user-principal', $result[200]);
        self::assertInstanceOf(Xml\Property\Principal::class, $result[200]['{DAV:}current-user-principal']);
        self::assertEquals(Xml\Property\Principal::HREF, $result[200]['{DAV:}current-user-principal']->getType());
        self::assertEquals('principals/admin/', $result[200]['{DAV:}current-user-principal']->getHref());
    }

    public function testSupportedPrivilegeSet()
    {
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $server = new DAV\Server();
        $server->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}supported-privilege-set',
        ];

        $result = $server->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        self::assertEquals(1, count($result[200]));
        self::assertArrayHasKey('{DAV:}supported-privilege-set', $result[200]);
        self::assertInstanceOf(Xml\Property\SupportedPrivilegeSet::class, $result[200]['{DAV:}supported-privilege-set']);

        $server = new DAV\Server();

        $prop = $result[200]['{DAV:}supported-privilege-set'];
        $result = $server->xml->write('{DAV:}root', $prop);

        $xpaths = [
            '/d:root' => 1,
            '/d:root/d:supported-privilege' => 1,
            '/d:root/d:supported-privilege/d:privilege' => 1,
            '/d:root/d:supported-privilege/d:privilege/d:all' => 1,
            '/d:root/d:supported-privilege/d:abstract' => 0,
            '/d:root/d:supported-privilege/d:supported-privilege' => 2,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege' => 2,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege/d:read' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege/d:write' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege' => 7,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege' => 7,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:read-acl' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:read-current-user-privilege-set' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:write-content' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:write-properties' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:bind' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:unbind' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:unlock' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:abstract' => 0,
        ];

        // reloading because php dom sucks
        $dom2 = new \DOMDocument('1.0', 'utf-8');
        $dom2->loadXML($result);

        $dxpath = new \DOMXPath($dom2);
        $dxpath->registerNamespace('d', 'DAV:');
        foreach ($xpaths as $xpath => $count) {
            self::assertEquals($count, $dxpath->query($xpath)->length, 'Looking for : '.$xpath.', we could only find '.$dxpath->query($xpath)->length.' elements, while we expected '.$count.' Full XML: '.$result);
        }
    }

    public function testACL()
    {
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);

        $nodes = [
            new MockACLNode('foo', [
                [
                    'principal' => 'principals/admin',
                    'privilege' => '{DAV:}read',
                ],
            ]),
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('admin', 'principals/admin'),
            ]),
        ];

        $server = new DAV\Server($nodes);
        $server->addPlugin($plugin);
        $authPlugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $server->addPlugin($authPlugin);

        // Force login
        $authPlugin->beforeMethod(new HTTP\Request('GET', '/'), new HTTP\Response());

        $requestedProperties = [
            '{DAV:}acl',
        ];

        $result = $server->getPropertiesForPath('foo', $requestedProperties);
        $result = $result[0];

        self::assertEquals(1, count($result[200]), 'The {DAV:}acl property did not return from the list. Full list: '.print_r($result, true));
        self::assertArrayHasKey('{DAV:}acl', $result[200]);
        self::assertInstanceOf(Xml\Property\Acl::class, $result[200]['{DAV:}acl']);
    }

    public function testACLRestrictions()
    {
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;

        $nodes = [
            new MockACLNode('foo', [
                [
                    'principal' => 'principals/admin',
                    'privilege' => '{DAV:}read',
                ],
            ]),
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('admin', 'principals/admin'),
            ]),
        ];

        $server = new DAV\Server($nodes);
        $server->addPlugin($plugin);
        $authPlugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $server->addPlugin($authPlugin);

        // Force login
        $authPlugin->beforeMethod(new HTTP\Request('GET', '/'), new HTTP\Response());

        $requestedProperties = [
            '{DAV:}acl-restrictions',
        ];

        $result = $server->getPropertiesForPath('foo', $requestedProperties);
        $result = $result[0];

        self::assertEquals(1, count($result[200]), 'The {DAV:}acl-restrictions property did not return from the list. Full list: '.print_r($result, true));
        self::assertArrayHasKey('{DAV:}acl-restrictions', $result[200]);
        self::assertInstanceOf(Xml\Property\AclRestrictions::class, $result[200]['{DAV:}acl-restrictions']);
    }

    public function testAlternateUriSet()
    {
        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        // $plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend())
        // $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}alternate-URI-set',
        ];
        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        self::assertTrue(isset($result[200]));
        self::assertTrue(isset($result[200]['{DAV:}alternate-URI-set']));
        self::assertInstanceOf(DAV\Xml\Property\Href::class, $result[200]['{DAV:}alternate-URI-set']);

        self::assertEquals([], $result[200]['{DAV:}alternate-URI-set']->getHrefs());
    }

    public function testPrincipalURL()
    {
        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        // $plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend());
        // $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}principal-URL',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        self::assertTrue(isset($result[200]));
        self::assertTrue(isset($result[200]['{DAV:}principal-URL']));
        self::assertInstanceOf(DAV\Xml\Property\Href::class, $result[200]['{DAV:}principal-URL']);

        self::assertEquals('principals/user/', $result[200]['{DAV:}principal-URL']->getHref());
    }

    public function testGroupMemberSet()
    {
        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        // $plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend());
        // $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}group-member-set',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        self::assertTrue(isset($result[200]));
        self::assertTrue(isset($result[200]['{DAV:}group-member-set']));
        self::assertInstanceOf(DAV\Xml\Property\Href::class, $result[200]['{DAV:}group-member-set']);

        self::assertEquals([], $result[200]['{DAV:}group-member-set']->getHrefs());
    }

    public function testGroupMemberShip()
    {
        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $fakeServer->addPlugin($plugin);
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);

        $requestedProperties = [
            '{DAV:}group-membership',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        self::assertTrue(isset($result[200]));
        self::assertTrue(isset($result[200]['{DAV:}group-membership']));
        self::assertInstanceOf(DAV\Xml\Property\Href::class, $result[200]['{DAV:}group-membership']);

        self::assertEquals([], $result[200]['{DAV:}group-membership']->getHrefs());
    }

    public function testGetDisplayName()
    {
        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $fakeServer->addPlugin($plugin);
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);

        $requestedProperties = [
            '{DAV:}displayname',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        self::assertTrue(isset($result[200]));
        self::assertTrue(isset($result[200]['{DAV:}displayname']));

        self::assertEquals('user', $result[200]['{DAV:}displayname']);
    }
}
