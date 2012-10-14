<?php

class Sabre_CalDAV_Property_InviteTest extends PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new Sabre_CalDAV_Property_Invite(array());

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new Sabre_CalDAV_Property_Invite(array(
            array(
                'href' => 'mailto:user1@example.org',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_ACCEPTED,
                'readOnly' => false,
            ),
            array(
                'href' => 'mailto:user2@example.org',
                'commonName' => 'John Doe',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_DECLINED,
                'readOnly' => true,
            ),
            array(
                'href' => 'mailto:user3@example.org',
                'commonName' => 'Joe Shmoe',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_NORESPONSE,
                'readOnly' => true,
                'summary' => 'Something, something',
            ),
            array(
                'href' => 'mailto:user4@example.org',
                'commonName' => 'Hoe Boe',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_INVALID,
                'readOnly' => true,
            ),
        ));

        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',Sabre_CalDAV_Plugin::NS_CALDAV);
        $root->setAttribute('xmlns:cs',Sabre_CalDAV_Plugin::NS_CALENDARSERVER);

        $doc->appendChild($root);
        $server = new Sabre_DAV_Server();

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . Sabre_CalDAV_Plugin::NS_CALDAV . '" xmlns:cs="' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '">
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
    public function testUnserialize() {

        $input = array(
            array(
                'href' => 'mailto:user1@example.org',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_ACCEPTED,
                'readOnly' => false,
                'commonName' => '',
                'summary' => '',
            ),
            array(
                'href' => 'mailto:user2@example.org',
                'commonName' => 'John Doe',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_DECLINED,
                'readOnly' => true,
                'summary' => '',
            ),
            array(
                'href' => 'mailto:user3@example.org',
                'commonName' => 'Joe Shmoe',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_NORESPONSE,
                'readOnly' => true,
                'summary' => 'Something, something',
            ),
            array(
                'href' => 'mailto:user4@example.org',
                'commonName' => 'Hoe Boe',
                'status' => Sabre_CalDAV_SharingPlugin::STATUS_INVALID,
                'readOnly' => true,
                'summary' => '',
            ),
        );

        // Creating the xml
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',Sabre_CalDAV_Plugin::NS_CALDAV);
        $root->setAttribute('xmlns:cs',Sabre_CalDAV_Plugin::NS_CALENDARSERVER);

        $doc->appendChild($root);
        $server = new Sabre_DAV_Server();

        $inputProperty = new Sabre_CalDAV_Property_Invite($input);
        $inputProperty->serialize($server, $root);

        $xml = $doc->saveXML();

        // Parsing it again

        $doc2 = Sabre_DAV_XMLUtil::loadDOMDocument($xml);

        $outputProperty = Sabre_CalDAV_Property_Invite::unserialize($doc2->firstChild);

        $this->assertEquals($input, $outputProperty->getValue());

    }

    /**
     * @expectedException Sabre_DAV_Exception
     */
    function testUnserializeNoStatus() {

$xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . Sabre_CalDAV_Plugin::NS_CALDAV . '" xmlns:cs="' . Sabre_CalDAV_Plugin::NS_CALENDARSERVER . '">
  <cs:user>
    <d:href>mailto:user1@example.org</d:href>
    <!-- <cs:invite-accepted/> -->
    <cs:access>
      <cs:read-write/>
    </cs:access>
  </cs:user>
</d:root>';

        $doc2 = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $outputProperty = Sabre_CalDAV_Property_Invite::unserialize($doc2->firstChild);

    }

}
