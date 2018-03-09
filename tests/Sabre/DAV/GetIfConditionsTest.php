<?php declare (strict_types=1);

namespace Sabre\DAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class GetIfConditionsTest extends AbstractServer {

    function testNoConditions() {

        $request = new ServerRequest('GET', '/foo');

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));
        $this->assertEquals([], $conditions);

    }

    function testLockToken() {

        $request = new ServerRequest('GET', '/path/', ['If' => '(<opaquelocktoken:token1>)']);
        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => 'path',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                ],

            ],

        ];

        $this->assertEquals($compare, $conditions);

    }

    function testNotLockToken() {

        $request = new ServerRequest('GET', '/bla', [
            'If' => '(Not <opaquelocktoken:token1>)',
        ]);

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => 'bla',
                'tokens' => [
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function testLockTokenUrl() {

        $request = new ServerRequest('GET', '/bla', [
            'If' => '<http://www.example.com/> (<opaquelocktoken:token1>)',
        ]);
        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => '',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2LockTokens() {

        $request = new ServerRequest('GET', '/bla', [
            'If' => '(<opaquelocktoken:token1>) (Not <opaquelocktoken:token2>)',
        ]);

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => 'bla',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2UriLockTokens() {

        $request = new ServerRequest('GET', '/bla', [
            'If' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) <http://www.example.org/node2> (Not <opaquelocktoken:token2>)',
        ]);

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                 ],
            ],
            [
                'uri'    => 'node2',
                'tokens' => [
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2UriMultiLockTokens() {

        $request = new ServerRequest('GET', '/bla', [
            'If' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) (<opaquelocktoken:token2>) <http://www.example.org/node2> (Not <opaquelocktoken:token3>)',
        ]);

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                 ],
            ],
            [
                'uri'    => 'node2',
                'tokens' => [
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token3',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function testEtag() {

        $request = new ServerRequest('GET', '/foo', [
            'If' => '(["etag1"])',
        ]);

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => 'foo',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag1"',
                    ],
                 ],
            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2Etags() {

        $request = new ServerRequest('GET', '/foo', [
            'If' => '<http://www.example.org/> (["etag1"]) (["etag2"])',
        ]);

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => '',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag1"',
                    ],
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag2"',
                    ],
                 ],
            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function testComplexIf() {

        $request = new ServerRequest('GET', '/foo', [
            'If' => '<http://www.example.org/node1> (<opaquelocktoken:token1> ["etag1"]) ' .
                    '(Not <opaquelocktoken:token2>) (["etag2"]) <http://www.example.org/node2> ' .
                    '(<opaquelocktoken:token3>) (Not <opaquelocktoken:token4>) (["etag3"])',
        ]);

        $conditions = $this->server->getIfConditions(new Psr7RequestWrapper($request));

        $compare = [

            [
                'uri'    => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '"etag1"',
                    ],
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag2"',
                    ],
                 ],
            ],
            [
                'uri'    => 'node2',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token3',
                        'etag'   => '',
                    ],
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token4',
                        'etag'   => '',
                    ],
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag3"',
                    ],
                 ],
            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

}
