<?php

namespace Sabre\CardDAV\Xml\Request;

use Sabre\DAV\Xml\XmlTest;

class AddressBookMultiGetTest extends XmlTest {

    protected $elementMap = [
        '{urn:ietf:params:xml:ns:carddav}addressbook-multiget' => 'Sabre\\CardDAV\\Xml\\Request\AddressBookMultiGetReport',
    ];

    /**
     * @dataProvider providesAddressDataXml
     * @param $xml
     */
    function testDeserialize($xml, $expectedProps, $expectedVersion = '3.0') {

        $result = $this->parse($xml);
        $addressBookMultiGetReport = new AddressBookMultiGetReport();
        $addressBookMultiGetReport->properties = [
            '{DAV:}getcontenttype',
            '{DAV:}getetag',
            '{urn:ietf:params:xml:ns:carddav}address-data',
        ];
        $addressBookMultiGetReport->hrefs = ['/foo.vcf'];
        $addressBookMultiGetReport->contentType = 'text/vcard';
        $addressBookMultiGetReport->version = $expectedVersion;
        $addressBookMultiGetReport->addressDataProperties = $expectedProps;


        $this->assertEquals(
            $addressBookMultiGetReport,
            $result['value']
        );

    }

    function providesAddressDataXml()
    {
        $simpleXml = <<<XML
<?xml version='1.0' encoding='UTF-8' ?>
<CARD:addressbook-multiget xmlns:d="DAV:" xmlns:CARD="urn:ietf:params:xml:ns:carddav">
  <d:prop>
    <d:getcontenttype />
    <d:getetag />
    <CARD:address-data content-type="text/vcard" version="4.0"/>
  </d:prop>
  <d:href>/foo.vcf</d:href>
</CARD:addressbook-multiget>
XML;
        $allPropsXml = <<<XML
<?xml version='1.0' encoding='UTF-8' ?>
<CARD:addressbook-multiget xmlns:d="DAV:" xmlns:CARD="urn:ietf:params:xml:ns:carddav">
  <d:prop>
    <d:getcontenttype />
    <d:getetag />
    <CARD:address-data>
        <CARD:allprop/>
    </CARD:address-data>
  </d:prop>
  <d:href>/foo.vcf</d:href>
</CARD:addressbook-multiget>
XML;
        $multiplePropsXml = <<<XML
<?xml version='1.0' encoding='UTF-8' ?>
<CARD:addressbook-multiget xmlns:d="DAV:" xmlns:CARD="urn:ietf:params:xml:ns:carddav">
  <d:prop>
    <d:getcontenttype />
    <d:getetag />
    <CARD:address-data>
        <CARD:prop name="VERSION"/>
        <CARD:prop name="UID"/>
        <CARD:prop name="NICKNAME"/>
        <CARD:prop name="EMAIL"/>
        <CARD:prop name="FN"/>
    </CARD:address-data>
  </d:prop>
  <d:href>/foo.vcf</d:href>
</CARD:addressbook-multiget>
XML;
        return [
            'address data with version'         => [$simpleXml, [], '4.0'],
            'address data with inner all props' => [$allPropsXml, []],
            'address data with mutliple props'  => [$multiplePropsXml, ['VERSION', 'UID', 'NICKNAME', 'EMAIL', 'FN']]
        ];
    }
}
