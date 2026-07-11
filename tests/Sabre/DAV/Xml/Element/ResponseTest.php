<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV;

class ResponseTest extends DAV\Xml\AbstractXmlTestCase
{
    public function testSimple()
    {
        $innerProps = [
            200 => [
                '{DAV:}displayname' => 'my file',
            ],
            404 => [
                '{DAV:}owner' => null,
            ],
        ];

        $property = new Response('uri', $innerProps);

        self::assertEquals('uri', $property->getHref());
        self::assertEquals($innerProps, $property->getResponseProperties());
    }

    /**
     * @depends testSimple
     */
    public function testSerialize()
    {
        $innerProps = [
            200 => [
                '{DAV:}displayname' => 'my file',
            ],
            404 => [
                '{DAV:}owner' => null,
            ],
        ];

        $property = new Response('uri', $innerProps);

        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
    <d:href>/uri</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>my file</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <d:owner/>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
</d:root>
', $xml);
    }

    /**
     * This one is specifically for testing properties with no namespaces, which is legal xml.
     *
     * @depends testSerialize
     */
    public function testSerializeEmptyNamespace()
    {
        $innerProps = [
            200 => [
                '{}propertyname' => 'value',
            ],
        ];

        $property = new Response('uri', $innerProps);

        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertEquals(
            '<d:root xmlns:d="DAV:">
 <d:response>
  <d:href>/uri</d:href>
  <d:propstat>
   <d:prop>
    <propertyname xmlns="">value</propertyname>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
</d:root>
', $xml);
    }

    /**
     * This one is specifically for testing properties with no namespaces, which is legal xml.
     *
     * @depends testSerialize
     */
    public function testSerializeCustomNamespace()
    {
        $innerProps = [
            200 => [
                '{http://sabredav.org/NS/example}propertyname' => 'value',
            ],
        ];

        $property = new Response('uri', $innerProps);
        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
      <d:href>/uri</d:href>
      <d:propstat>
        <d:prop>
          <x1:propertyname xmlns:x1="http://sabredav.org/NS/example">value</x1:propertyname>
        </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
      </d:propstat>
  </d:response>
</d:root>', $xml);
    }

    /**
     * @depends testSerialize
     */
    public function testSerializeComplexProperty()
    {
        $innerProps = [
            200 => [
                '{DAV:}link' => new DAV\Xml\Property\Href('http://sabredav.org/'),
            ],
        ];

        $property = new Response('uri', $innerProps);
        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
      <d:href>/uri</d:href>
      <d:propstat>
        <d:prop>
          <d:link><d:href>http://sabredav.org/</d:href></d:link>
        </d:prop>
        <d:status>HTTP/1.1 200 OK</d:status>
      </d:propstat>
  </d:response>
</d:root>
', $xml);
    }

    /**
     * @depends testSerialize
     */
    public function testSerializeBreak()
    {
        $this->expectException('InvalidArgumentException');
        $innerProps = [
            200 => [
                '{DAV:}link' => new \stdClass(),
            ],
        ];

        $property = new Response('uri', $innerProps);
        $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);
    }

    public function testDeserializeComplexProperty()
    {
        $xml = '<?xml version="1.0"?>
<d:response xmlns:d="DAV:">
  <d:href>/uri</d:href>
  <d:propstat>
    <d:prop>
      <d:foo>hello</d:foo>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
';

        $result = $this->parse($xml, [
            '{DAV:}response' => Response::class,
            '{DAV:}foo' => function ($reader) {
                $reader->next();

                return 'world';
            },
        ]);
        self::assertEquals(
            new Response('/uri', [
                '200' => [
                    '{DAV:}foo' => 'world',
                ],
            ]),
            $result['value']
        );
    }

    /**
     * @depends testSimple
     */
    public function testSerializeUrlencoding()
    {
        $innerProps = [
            200 => [
                '{DAV:}displayname' => 'my file',
            ],
        ];

        $property = new Response('space here', $innerProps);

        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
    <d:href>/space%20here</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>my file</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
</d:root>
', $xml);
    }

    /**
     * @depends testSerialize
     *
     * The WebDAV spec _requires_ at least one DAV:propstat to appear for
     * every DAV:response if there is no status.
     * In some circumstances however, there are no properties to encode.
     *
     * In those cases we MUST specify at least one DAV:propstat anyway, with
     * no properties.
     */
    public function testSerializeNoProperties()
    {
        $innerProps = [];

        $property = new Response('uri', $innerProps);
        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
      <d:href>/uri</d:href>
      <d:propstat>
        <d:prop />
        <d:status>HTTP/1.1 418 I\'m a teapot</d:status>
      </d:propstat>
  </d:response>
</d:root>
', $xml);
    }

    /**
     * @depends testSerialize
     *
     * The WebDAV spec _requires_ at least one DAV:propstat _OR_ a status to appear for
     * every DAV:response.
     * So if there are no properties but a status, the response should contain that status.
     */
    public function testSerializeNoPropertiesButStatus()
    {
        $innerProps = [];

        $property = new Response('uri', $innerProps, 200);
        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
      <d:href>/uri</d:href>
      <d:status>HTTP/1.1 200 OK</d:status>
  </d:response>
</d:root>
', $xml);
    }

    /**
     * @depends testSerialize
     *
     * The WebDAV standard only allow EITHER propstat(s) OR a status within the response.
     * Make sure that if there are propstat(s), no status element is added.
     */
    public function testSerializePropertiesAndStatus()
    {
        $innerProps = [
            200 => [
                '{DAV:}displayname' => 'my file',
            ],
        ];

        $property = new Response('uri', $innerProps, 200);

        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
    <d:href>/uri</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>my file</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
</d:root>
', $xml);
    }

    /**
     * In the case of {DAV:}prop, a deserializer should never get called, if
     * the property element is empty.
     */
    public function testDeserializeComplexPropertyEmpty()
    {
        $xml = '<?xml version="1.0"?>
<d:response xmlns:d="DAV:">
  <d:href>/uri</d:href>
  <d:propstat>
    <d:prop>
      <d:foo />
    </d:prop>
    <d:status>HTTP/1.1 404 Not Found</d:status>
  </d:propstat>
</d:response>
';

        $result = $this->parse($xml, [
            '{DAV:}response' => Response::class,
            '{DAV:}foo' => function ($reader) {
                throw new \LogicException('This should never happen');
            },
        ]);
        self::assertEquals(
            new Response('/uri', [
                '404' => [
                    '{DAV:}foo' => null,
                ],
            ]),
            $result['value']
        );
    }

    public function testDeserializeNoProperties()
    {
        $xml = '<?xml version="1.0"?>
<d:response xmlns:d="DAV:">
  <d:href>/uri</d:href>
  <d:propstat>
    <d:prop></d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>  
  <d:propstat>
    <d:prop>
        <d:foo />
    </d:prop>
    <d:status>HTTP/1.1 404 OK</d:status>
  </d:propstat>
</d:response>
';

        $result = $this->parse($xml, [
            '{DAV:}response' => Response::class,
        ]);
        self::assertEquals(
            new Response('/uri', [
                '404' => [
                    '{DAV:}foo' => null,
                ],
            ]),
            $result['value']
        );
    }
}
