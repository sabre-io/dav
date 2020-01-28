<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;




class GetIfConditionsTest extends AbstractServer
{
    public function testNoConditions()
    {
        $request = new HTTP\Request('GET', '/foo');

        $conditions = $this->server->getIfConditions($request);
        $this->assertEquals([], $conditions);
    }

    public function testLockToken()
    {
        $request = new HTTP\Request('GET', '/path/', ['If' => '(<opaquelocktoken:token1>)']);
        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => 'path',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '',
                    ],
                ],
            ],
        ];

        $this->assertEquals($compare, $conditions);
    }

    public function testNotLockToken()
    {
        $request = new HTTP\Request('GET', '/bla', [
            'If' => '(Not <opaquelocktoken:token1>)',
        ]);

        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => 'bla',
                'tokens' => [
                    [
                        'negate' => true,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '',
                    ],
                ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }

    public function testLockTokenUrl()
    {
        $request = new HTTP\Request('GET', '/bla', [
            'If' => '<http://www.example.com/> (<opaquelocktoken:token1>)',
        ]);
        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => '',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '',
                    ],
                ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }

    public function test2LockTokens()
    {
        $request = new HTTP\Request('GET', '/bla', [
            'If' => '(<opaquelocktoken:token1>) (Not <opaquelocktoken:token2>)',
        ]);

        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => 'bla',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '',
                    ],
                    [
                        'negate' => true,
                        'token' => 'opaquelocktoken:token2',
                        'etag' => '',
                    ],
                ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }

    public function test2UriLockTokens()
    {
        $request = new HTTP\Request('GET', '/bla', [
            'If' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) <http://www.example.org/node2> (Not <opaquelocktoken:token2>)',
        ]);

        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '',
                    ],
                 ],
            ],
            [
                'uri' => 'node2',
                'tokens' => [
                    [
                        'negate' => true,
                        'token' => 'opaquelocktoken:token2',
                        'etag' => '',
                    ],
                ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }

    public function test2UriMultiLockTokens()
    {
        $request = new HTTP\Request('GET', '/bla', [
            'If' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) (<opaquelocktoken:token2>) <http://www.example.org/node2> (Not <opaquelocktoken:token3>)',
        ]);

        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '',
                    ],
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token2',
                        'etag' => '',
                    ],
                 ],
            ],
            [
                'uri' => 'node2',
                'tokens' => [
                    [
                        'negate' => true,
                        'token' => 'opaquelocktoken:token3',
                        'etag' => '',
                    ],
                ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }

    public function testEtag()
    {
        $request = new HTTP\Request('GET', '/foo', [
            'If' => '(["etag1"])',
        ]);

        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => 'foo',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => '',
                        'etag' => '"etag1"',
                    ],
                 ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }

    public function test2Etags()
    {
        $request = new HTTP\Request('GET', '/foo', [
            'If' => '<http://www.example.org/> (["etag1"]) (["etag2"])',
        ]);

        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => '',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => '',
                        'etag' => '"etag1"',
                    ],
                    [
                        'negate' => false,
                        'token' => '',
                        'etag' => '"etag2"',
                    ],
                 ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }

    public function testComplexIf()
    {
        $request = new HTTP\Request('GET', '/foo', [
            'If' => '<http://www.example.org/node1> (<opaquelocktoken:token1> ["etag1"]) '.
                    '(Not <opaquelocktoken:token2>) (["etag2"]) <http://www.example.org/node2> '.
                    '(<opaquelocktoken:token3>) (Not <opaquelocktoken:token4>) (["etag3"])',
        ]);

        $conditions = $this->server->getIfConditions($request);

        $compare = [
            [
                'uri' => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '"etag1"',
                    ],
                    [
                        'negate' => true,
                        'token' => 'opaquelocktoken:token2',
                        'etag' => '',
                    ],
                    [
                        'negate' => false,
                        'token' => '',
                        'etag' => '"etag2"',
                    ],
                 ],
            ],
            [
                'uri' => 'node2',
                'tokens' => [
                    [
                        'negate' => false,
                        'token' => 'opaquelocktoken:token3',
                        'etag' => '',
                    ],
                    [
                        'negate' => true,
                        'token' => 'opaquelocktoken:token4',
                        'etag' => '',
                    ],
                    [
                        'negate' => false,
                        'token' => '',
                        'etag' => '"etag3"',
                    ],
                 ],
            ],
        ];
        $this->assertEquals($compare, $conditions);
    }
}
