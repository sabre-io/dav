<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutputHelper;

class ACLTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $acl = new Acl([]);
        self::assertInstanceOf(\Sabre\DAVACL\Xml\Property\ACL::class, $acl);
    }

    public function testSerializeEmpty()
    {
        $acl = new Acl([]);
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $acl);

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" />';

        self::assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function testSerialize()
    {
        $privileges = [
            [
                'principal' => 'principals/evert',
                'privilege' => '{DAV:}write',
            ],
            [
                'principal' => 'principals/foo',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ],
        ];

        $acl = new Acl($privileges);
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $acl, '/');

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <d:ace>
    <d:principal>
      <d:href>/principals/evert/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/principals/foo/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:read/>
      </d:privilege>
    </d:grant>
    <d:protected/>
  </d:ace>
</d:root>
';
        self::assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function testSerializeSpecialPrincipals()
    {
        $privileges = [
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}write',
            ],
            [
                'principal' => '{DAV:}unauthenticated',
                'privilege' => '{DAV:}write',
            ],
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}write',
            ],
        ];

        $acl = new Acl($privileges);
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $acl, '/');

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <d:ace>
    <d:principal>
      <d:authenticated/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:unauthenticated/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:all/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
</d:root>
';
        self::assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function testUnserialize()
    {
        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:principal>
      <d:href>/principals/evert/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/principals/foo/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:read/>
      </d:privilege>
    </d:grant>
    <d:protected/>
  </d:ace>
</d:root>
';

        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = \Sabre\DAVACL\Xml\Property\Acl::class;
        $reader->xml($source);

        $result = $reader->parse();
        $result = $result['value'];

        self::assertInstanceOf(\Sabre\DAVACL\Xml\Property\Acl::class, $result);

        $expected = [
            [
                'principal' => '/principals/evert/',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ],
            [
                'principal' => '/principals/foo/',
                'protected' => true,
                'privilege' => '{DAV:}read',
            ],
        ];

        self::assertEquals($expected, $result->getPrivileges());
    }

    public function testUnserializeNoPrincipal()
    {
        $this->expectException(\Sabre\DAV\Exception\BadRequest::class);
        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
</d:root>
';

        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = \Sabre\DAVACL\Xml\Property\Acl::class;
        $reader->xml($source);

        $result = $reader->parse();
    }

    public function testUnserializeOtherPrincipal()
    {
        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:authenticated /></d:principal>
  </d:ace>
  <d:ace>
    <d:grant>
      <d:ignoreme />
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:unauthenticated /></d:principal>
  </d:ace>
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:all /></d:principal>
  </d:ace>
</d:root>
';

        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = \Sabre\DAVACL\Xml\Property\Acl::class;
        $reader->xml($source);

        $result = $reader->parse();
        $result = $result['value'];

        self::assertInstanceOf(\Sabre\DAVACL\Xml\Property\Acl::class, $result);

        $expected = [
            [
                'principal' => '{DAV:}authenticated',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ],
            [
                'principal' => '{DAV:}unauthenticated',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ],
            [
                'principal' => '{DAV:}all',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ],
        ];

        self::assertEquals($expected, $result->getPrivileges());
    }

    public function testUnserializeDeny()
    {
        $this->expectException(\Sabre\DAV\Exception\NotImplemented::class);
        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ignore-me />
  <d:ace>
    <d:deny>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:deny>
    <d:principal><d:href>/principals/evert</d:href></d:principal>
  </d:ace>
</d:root>
';

        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = \Sabre\DAVACL\Xml\Property\Acl::class;
        $reader->xml($source);

        $result = $reader->parse();
    }

    public function testToHtml()
    {
        $privileges = [
            [
                'principal' => 'principals/evert',
                'privilege' => '{DAV:}write',
            ],
            [
                'principal' => 'principals/foo',
                'privilege' => '{http://example.org/ns}read',
                'protected' => true,
            ],
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}write',
            ],
        ];

        $acl = new Acl($privileges);
        $html = new HtmlOutputHelper(
            '/base/',
            ['DAV:' => 'd']
        );

        $expected =
            '<table>'.
            '<tr><th>Principal</th><th>Privilege</th><th></th></tr>'.
            '<tr><td><a href="/base/principals/evert">/base/principals/evert</a></td><td><span title="{DAV:}write">d:write</span></td><td></td></tr>'.
            '<tr><td><a href="/base/principals/foo">/base/principals/foo</a></td><td><span title="{http://example.org/ns}read">{http://example.org/ns}read</span></td><td>(protected)</td></tr>'.
            '<tr><td><span title="{DAV:}authenticated">d:authenticated</span></td><td><span title="{DAV:}write">d:write</span></td><td></td></tr>'.
            '</table>';

        self::assertEquals($expected, $acl->toHtml($html));
    }
}
