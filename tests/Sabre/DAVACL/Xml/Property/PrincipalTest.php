<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Reader;

class PrincipalTest extends \PHPUnit\Framework\TestCase
{
    public function testSimple()
    {
        $principal = new Principal(Principal::UNAUTHENTICATED);
        $this->assertEquals(Principal::UNAUTHENTICATED, $principal->getType());
        $this->assertNull($principal->getHref());

        $principal = new Principal(Principal::AUTHENTICATED);
        $this->assertEquals(Principal::AUTHENTICATED, $principal->getType());
        $this->assertNull($principal->getHref());

        $principal = new Principal(Principal::HREF, 'admin');
        $this->assertEquals(Principal::HREF, $principal->getType());
        $this->assertEquals('admin/', $principal->getHref());
    }

    /**
     * @depends testSimple
     * @expectedException \Sabre\DAV\Exception
     */
    public function testNoHref()
    {
        $principal = new Principal(Principal::HREF);
    }

    /**
     * @depends testSimple
     */
    public function testSerializeUnAuthenticated()
    {
        $prin = new Principal(Principal::UNAUTHENTICATED);

        $xml = (new DAV\Server())->xml->write('{DAV:}principal', $prin);

        $this->assertXmlStringEqualsXmlString('
<d:principal xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:unauthenticated/>
</d:principal>', $xml);
    }

    /**
     * @depends testSerializeUnAuthenticated
     */
    public function testSerializeAuthenticated()
    {
        $prin = new Principal(Principal::AUTHENTICATED);
        $xml = (new DAV\Server())->xml->write('{DAV:}principal', $prin);

        $this->assertXmlStringEqualsXmlString('
<d:principal xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:authenticated/>
</d:principal>', $xml);
    }

    /**
     * @depends testSerializeUnAuthenticated
     */
    public function testSerializeHref()
    {
        $prin = new Principal(Principal::HREF, 'principals/admin');
        $xml = (new DAV\Server())->xml->write('{DAV:}principal', $prin, '/');

        $this->assertXmlStringEqualsXmlString('
<d:principal xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:href>/principals/admin/</d:href>
</d:principal>', $xml);
    }

    public function testUnserializeHref()
    {
        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">'.
'<d:href>/principals/admin</d:href>'.
'</d:principal>';

        $principal = $this->parse($xml);
        $this->assertEquals(Principal::HREF, $principal->getType());
        $this->assertEquals('/principals/admin/', $principal->getHref());
    }

    public function testUnserializeAuthenticated()
    {
        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">'.
'  <d:authenticated />'.
'</d:principal>';

        $principal = $this->parse($xml);
        $this->assertEquals(Principal::AUTHENTICATED, $principal->getType());
    }

    public function testUnserializeUnauthenticated()
    {
        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">'.
'  <d:unauthenticated />'.
'</d:principal>';

        $principal = $this->parse($xml);
        $this->assertEquals(Principal::UNAUTHENTICATED, $principal->getType());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    public function testUnserializeUnknown()
    {
        $xml = '<?xml version="1.0"?>
<d:principal xmlns:d="DAV:">'.
'  <d:foo />'.
'</d:principal>';

        $this->parse($xml);
    }

    public function parse($xml)
    {
        $reader = new Reader();
        $reader->elementMap['{DAV:}principal'] = 'Sabre\\DAVACL\\Xml\\Property\\Principal';
        $reader->xml($xml);
        $result = $reader->parse();

        return $result['value'];
    }

    /**
     * @depends testSimple
     * @dataProvider htmlProvider
     */
    public function testToHtml($principal, $output)
    {
        $html = $principal->toHtml(new HtmlOutputHelper('/', []));

        $this->assertXmlStringEqualsXmlString(
            $output,
            $html
        );
    }

    /**
     * Provides data for the html tests.
     *
     * @return array
     */
    public function htmlProvider()
    {
        return [
            [
                new Principal(Principal::UNAUTHENTICATED),
                '<em>unauthenticated</em>',
            ],
            [
                new Principal(Principal::AUTHENTICATED),
                '<em>authenticated</em>',
            ],
            [
                new Principal(Principal::ALL),
                '<em>all</em>',
            ],
            [
                new Principal(Principal::HREF, 'principals/admin'),
                '<a href="/principals/admin/">/principals/admin/</a>',
            ],
        ];
    }
}
