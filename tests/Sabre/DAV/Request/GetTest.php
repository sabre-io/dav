<?php

namespace Sabre\DAV\Request;

class GetTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider contentType
     */
    public function testNegotiateContentType($acceptHeader, $available, $expected) {

        $get = new Get([
            'Accept' => $acceptHeader
        ]);

        $this->assertEquals(
            $expected,
            $get->negotiateContentType($available)
        );

    }

    public function contentType() {

        return [
            [ // simple
                'application/xml',
                ['application/xml'],
                'application/xml',
            ],
            [ // no header
                null,
                ['application/xml'],
                'application/xml',
            ],
            [ // 2 options
                'application/json',
                ['application/xml', 'application/json'],
                'application/json',
            ],
            [ // 2 choices
                'application/json, application/xml',
                ['application/xml'],
                'application/xml',
            ],
            [ // quality
                'application/xml;q=0.2, application/json',
                ['application/xml', 'application/json'],
                'application/json',
            ],
            [ // wildcard
                'image/jpeg, image/png, */*',
                ['application/xml', 'application/json'],
                'application/xml',
            ],
            [ // wildcard + quality
                'image/jpeg, image/png; q=0.5, */*',
                ['application/xml', 'application/json', 'image/png'],
                'application/xml',
            ],

        ];

    }

}
