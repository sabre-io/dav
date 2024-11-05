<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;

class PluginTest extends AbstractPluginTest
{
    public function testConstruct()
    {
        self::assertEquals('{'.Plugin::NS_CARDDAV.'}addressbook', $this->server->resourceTypeMapping[\Sabre\CardDAV\IAddressBook::class]);

        self::assertTrue(in_array('addressbook', $this->plugin->getFeatures()));
        self::assertEquals('carddav', $this->plugin->getPluginInfo()['name']);
    }

    public function testSupportedReportSet()
    {
        self::assertEquals([
            '{'.Plugin::NS_CARDDAV.'}addressbook-multiget',
            '{'.Plugin::NS_CARDDAV.'}addressbook-query',
        ], $this->plugin->getSupportedReportSet('addressbooks/user1/book1'));
    }

    public function testSupportedReportSetEmpty()
    {
        self::assertEquals([
        ], $this->plugin->getSupportedReportSet(''));
    }

    public function testAddressBookHomeSet()
    {
        $result = $this->server->getProperties('principals/user1', ['{'.Plugin::NS_CARDDAV.'}addressbook-home-set']);

        self::assertEquals(1, count($result));
        self::assertTrue(isset($result['{'.Plugin::NS_CARDDAV.'}addressbook-home-set']));
        self::assertEquals('addressbooks/user1/', $result['{'.Plugin::NS_CARDDAV.'}addressbook-home-set']->getHref());
    }

    public function testDirectoryGateway()
    {
        $result = $this->server->getProperties('principals/user1', ['{'.Plugin::NS_CARDDAV.'}directory-gateway']);

        self::assertEquals(1, count($result));
        self::assertTrue(isset($result['{'.Plugin::NS_CARDDAV.'}directory-gateway']));
        self::assertEquals(['directory'], $result['{'.Plugin::NS_CARDDAV.'}directory-gateway']->getHrefs());
    }

    public function testReportPassThrough()
    {
        self::assertNull($this->plugin->report('{DAV:}foo', new \DomDocument(), ''));
    }

    public function testHTMLActionsPanel()
    {
        $output = '';
        $r = $this->server->emit('onHTMLActionsPanel', [$this->server->tree->getNodeForPath('addressbooks/user1'), &$output]);
        self::assertFalse($r);

        self::assertTrue((bool) strpos($output, 'Display name'));
    }

    public function testAddressbookPluginProperties()
    {
        $ns = '{'.Plugin::NS_CARDDAV.'}';
        $propFind = new DAV\PropFind('addressbooks/user1/book1', [
            $ns.'supported-address-data',
            $ns.'supported-collation-set',
        ]);
        $node = $this->server->tree->getNodeForPath('addressbooks/user1/book1');
        $this->plugin->propFindEarly($propFind, $node);

        self::assertInstanceOf(
            \Sabre\CardDAV\Xml\Property\SupportedAddressData::class,
            $propFind->get($ns.'supported-address-data')
        );
        self::assertInstanceOf(
            \Sabre\CardDAV\Xml\Property\SupportedCollationSet::class,
            $propFind->get($ns.'supported-collation-set')
        );
    }

    public function testGetTransform()
    {
        $request = new \Sabre\HTTP\Request('GET', '/addressbooks/user1/book1/card1', ['Accept' => 'application/vcard+json']);
        $response = new \Sabre\HTTP\ResponseMock();
        $this->server->invokeMethod($request, $response);

        self::assertEquals(200, $response->getStatus());
    }

    public function testGetWithoutContentType()
    {
        $request = new \Sabre\HTTP\Request('GET', '/');
        $response = new \Sabre\HTTP\ResponseMock();
        $this->plugin->httpAfterGet($request, $response);
        self::assertTrue(true);
    }
}
