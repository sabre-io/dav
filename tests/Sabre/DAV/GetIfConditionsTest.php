<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class GetIfConditionsTest extends AbstractServer {

    function testNoConditions() {

        $serverVars = array(
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();
        $this->assertEquals(array(),$conditions);

    }

    function testLockToken() {

        $serverVars = array(
            'HTTP_IF' => '(<opaquelocktoken:token1>)',
            'REQUEST_URI' => '/path/',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => 'path',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token' => 'opaquelocktoken:token1',
                        'etag' => '',
                    ),
                ),

            ),

        );

        $this->assertEquals($compare,$conditions);

    }

    function testNotLockToken() {

        $serverVars = array(
            'HTTP_IF' => '(Not <opaquelocktoken:token1>)',
            'REQUEST_URI' => '/bla'
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => 'bla',
                'tokens' => array(
                    array(
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ),
                ),

            ),

        );
        $this->assertEquals($compare,$conditions);

    }

    function testLockTokenUrl() {

        $serverVars = array(
            'HTTP_IF' => '<http://www.example.com/> (<opaquelocktoken:token1>)',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => '',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ),
                ),

            ),

        );
        $this->assertEquals($compare,$conditions);

    }

    function test2LockTokens() {

        $serverVars = array(
            'HTTP_IF' => '(<opaquelocktoken:token1>) (Not <opaquelocktoken:token2>)',
            'REQUEST_URI' => '/bla',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => 'bla',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ),
                    array(
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ),
                ),

            ),

        );
        $this->assertEquals($compare,$conditions);

    }

    function test2UriLockTokens() {

        $serverVars = array(
            'HTTP_IF' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) <http://www.example.org/node2> (Not <opaquelocktoken:token2>)',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => 'node1',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ),
                 ),
            ),
            array(
                'uri' => 'node2',
                'tokens' => array(
                    array(
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ),
                ),

            ),

        );
        $this->assertEquals($compare,$conditions);

    }

    function test2UriMultiLockTokens() {

        $serverVars = array(
            'HTTP_IF' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) (<opaquelocktoken:token2>) <http://www.example.org/node2> (Not <opaquelocktoken:token3>)',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => 'node1',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ),
                    array(
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ),
                 ),
            ),
            array(
                'uri' => 'node2',
                'tokens' => array(
                    array(
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token3',
                        'etag'   => '',
                    ),
                ),

            ),

        );
        $this->assertEquals($compare,$conditions);

    }

    function testEtag() {

        $serverVars = array(
            'HTTP_IF' => '(["etag1"])',
            'REQUEST_URI' => '/foo',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => 'foo',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag1"',
                    ),
                 ),
            ),

        );
        $this->assertEquals($compare,$conditions);

    }

    function test2Etags() {

        $serverVars = array(
            'HTTP_IF' => '<http://www.example.org/> (["etag1"]) (["etag2"])',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => '',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag1"',
                    ),
                    array(
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag2"',
                    ),
                 ),
            ),

        );
        $this->assertEquals($compare,$conditions);

    }

    function testComplexIf() {

        $serverVars = array(
            'HTTP_IF' => '<http://www.example.org/node1> (<opaquelocktoken:token1> ["etag1"]) ' .
                         '(Not <opaquelocktoken:token2>) (["etag2"]) <http://www.example.org/node2> ' .
                         '(<opaquelocktoken:token3>) (Not <opaquelocktoken:token4>) (["etag3"])',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);

        $conditions = $this->server->getIfConditions();

        $compare = array(

            array(
                'uri' => 'node1',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '"etag1"',
                    ),
                    array(
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ),
                    array(
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag2"',
                    ),
                 ),
            ),
            array(
                'uri' => 'node2',
                'tokens' => array(
                    array(
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token3',
                        'etag'   => '',
                    ),
                    array(
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token4',
                        'etag'   => '',
                    ),
                    array(
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag3"',
                    ),
                 ),
            ),

        );
        $this->assertEquals($compare,$conditions);

    }

}
