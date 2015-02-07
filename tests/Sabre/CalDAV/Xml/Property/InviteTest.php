<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class InviteTest extends DAV\Xml\XmlTest {

    function setUp() {

        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $this->namespaceMap[CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';


    }

    function testSimple() {

        $sccs = new Invite([]);
        $this->assertInstanceOf('Sabre\CalDAV\Xml\Property\Invite', $sccs);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new Invite([
            [
                'href' => 'mailto:user1@example.org',
                'status' => CalDAV\SharingPlugin::STATUS_ACCEPTED,
                'readOnly' => false,
            ],
            [
                'href' => 'mailto:user2@example.org',
                'commonName' => 'John Doe',
                'status' => CalDAV\SharingPlugin::STATUS_DECLINED,
                'readOnly' => true,
            ],
            [
                'href' => 'mailto:user3@example.org',
                'commonName' => 'Joe Shmoe',
                'status' => CalDAV\SharingPlugin::STATUS_NORESPONSE,
                'readOnly' => true,
                'summary' => 'Something, something',
            ],
            [
                'href' => 'mailto:user4@example.org',
                'commonName' => 'Hoe Boe',
                'status' => CalDAV\SharingPlugin::STATUS_INVALID,
                'readOnly' => true,
            ],
        ], [
            'href' => 'mailto:thedoctor@example.org',
            'commonName' => 'The Doctor',
            'firstName' => 'The',
            'lastName' => 'Doctor',
        ]);

        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cs:organizer>
    <d:href>mailto:thedoctor@example.org</d:href>
    <cs:common-name>The Doctor</cs:common-name>
    <cs:first-name>The</cs:first-name>
    <cs:last-name>Doctor</cs:last-name>
  </cs:organizer>
  <cs:user>
    <d:href>mailto:user1@example.org</d:href>
    <cs:invite-accepted/>
    <cs:access>
      <cs:read-write/>
    </cs:access>
  </cs:user>
  <cs:user>
    <d:href>mailto:user2@example.org</d:href>
    <cs:common-name>John Doe</cs:common-name>
    <cs:invite-declined/>
    <cs:access>
      <cs:read/>
    </cs:access>
  </cs:user>
  <cs:user>
    <d:href>mailto:user3@example.org</d:href>
    <cs:common-name>Joe Shmoe</cs:common-name>
    <cs:invite-noresponse/>
    <cs:access>
      <cs:read/>
    </cs:access>
    <cs:summary>Something, something</cs:summary>
  </cs:user>
  <cs:user>
    <d:href>mailto:user4@example.org</d:href>
    <cs:common-name>Hoe Boe</cs:common-name>
    <cs:invite-invalid/>
    <cs:access>
      <cs:read/>
    </cs:access>
  </cs:user>
</d:root>
', $xml);

    }

    /**
     * @depends testSerialize
     */
    function testUnserialize() {

        $input = [
            [
                'href' => 'mailto:user1@example.org',
                'status' => CalDAV\SharingPlugin::STATUS_ACCEPTED,
                'readOnly' => false,
                'commonName' => '',
                'summary' => '',
            ],
            [
                'href' => 'mailto:user2@example.org',
                'commonName' => 'John Doe',
                'status' => CalDAV\SharingPlugin::STATUS_DECLINED,
                'readOnly' => true,
                'summary' => '',
            ],
            [
                'href' => 'mailto:user3@example.org',
                'commonName' => 'Joe Shmoe',
                'status' => CalDAV\SharingPlugin::STATUS_NORESPONSE,
                'readOnly' => true,
                'summary' => 'Something, something',
            ],
            [
                'href' => 'mailto:user4@example.org',
                'commonName' => 'Hoe Boe',
                'status' => CalDAV\SharingPlugin::STATUS_INVALID,
                'readOnly' => true,
                'summary' => '',
            ],
        ];

        // Creating the xml
        $inputProperty = new Invite($input);
        $xml = $this->write(['{DAV:}root' => $inputProperty]);
        // Parsing it again

        $doc2 = $this->parse(
            $xml,
            ['{DAV:}root' => 'Sabre\\CalDAV\\Xml\\Property\\Invite']
        );

        $outputProperty = $doc2['value'];

        $this->assertEquals($input, $outputProperty->getValue());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testUnserializeNoStatus() {

$xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cs:user>
    <d:href>mailto:user1@example.org</d:href>
    <!-- <cs:invite-accepted/> -->
    <cs:access>
      <cs:read-write/>
    </cs:access>
  </cs:user>
</d:root>';

        $this->parse(
            $xml,
            ['{DAV:}root' => 'Sabre\\CalDAV\\Xml\\Property\\Invite']
        );

    }

}
