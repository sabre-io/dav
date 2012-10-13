<?php

class Sabre_CalDAV_Notifications_Notification_InviteTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataProvider
     */
    function testSerializers($notification, $expected) {

        $notification = new Sabre_CalDAV_Notifications_Notification_Invite($notification);

        $this->assertEquals('foo', $notification->getId());
        $this->assertEquals('"1"', $notification->getETag());

        $simpleExpected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:invite-notification/></cs:root>' . "\n";

        $dom = new DOMDocument('1.0','UTF-8');
        $elem = $dom->createElement('cs:root');
        $elem->setAttribute('xmlns:cs',Sabre_CalDAV_Plugin::NS_CALENDARSERVER);
        $dom->appendChild($elem);
        $notification->serialize(new Sabre_DAV_Server(), $elem);
        $this->assertEquals($simpleExpected, $dom->saveXML());

        $dom = new DOMDocument('1.0','UTF-8');
        $dom->formatOutput = true;
        $elem = $dom->createElement('cs:root');
        $elem->setAttribute('xmlns:cs',Sabre_CalDAV_Plugin::NS_CALENDARSERVER);
        $elem->setAttribute('xmlns:d','DAV:');
        $elem->setAttribute('xmlns:cal',Sabre_CalDAV_Plugin::NS_CALDAV);
        $dom->appendChild($elem);
        $notification->serializeBody(new Sabre_DAV_Server(), $elem);
        $this->assertEquals($expected, $dom->saveXML());


    }

    function dataProvider() {

        $dtStamp = new DateTime('2012-01-01 00:00:00', new DateTimeZone('GMT'));
        return array(
            array(
                array(
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'href' => 'mailto:foo@example.org',
                    'type' => Sabre_CalDAV_SharingPlugin::STATUS_ACCEPTED,
                    'readOnly' => true,
                    'hostUrl' => 'calendar',
                    'organizer' => 'principal/user1',
                    'commonName' => 'John Doe',
                    'summary' => 'Awesome stuff!'
                ),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-accepted/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:access>
      <cs:read/>
    </cs:access>
    <cs:organizer>
      <cs:common-name>John Doe</cs:common-name>
      <d:href>/principal/user1</d:href>
    </cs:organizer>
    <cs:summary>Awesome stuff!</cs:summary>
  </cs:invite-notification>
</cs:root>

FOO
            ),
            array(
                array(
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'href' => 'mailto:foo@example.org',
                    'type' => Sabre_CalDAV_SharingPlugin::STATUS_DECLINED,
                    'readOnly' => true,
                    'hostUrl' => 'calendar',
                    'organizer' => 'principal/user1',
                    'commonName' => 'John Doe',
                ),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-declined/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:access>
      <cs:read/>
    </cs:access>
    <cs:organizer>
      <cs:common-name>John Doe</cs:common-name>
      <d:href>/principal/user1</d:href>
    </cs:organizer>
  </cs:invite-notification>
</cs:root>

FOO
            ),
            array(
                array(
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'href' => 'mailto:foo@example.org',
                    'type' => Sabre_CalDAV_SharingPlugin::STATUS_NORESPONSE,
                    'readOnly' => true,
                    'hostUrl' => 'calendar',
                    'organizer' => 'principal/user1'
                ),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-noresponse/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:access>
      <cs:read/>
    </cs:access>
    <cs:organizer>
      <d:href>/principal/user1</d:href>
    </cs:organizer>
  </cs:invite-notification>
</cs:root>

FOO
            ),
            array(
                array(
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'href' => 'mailto:foo@example.org',
                    'type' => Sabre_CalDAV_SharingPlugin::STATUS_DELETED,
                    'readOnly' => false,
                    'hostUrl' => 'calendar',
                    'organizer' => 'principal/user1',
                    'supportedComponents' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT','VTODO')),
                ),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-deleted/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:access>
      <cs:read-write/>
    </cs:access>
    <cs:organizer>
      <d:href>/principal/user1</d:href>
    </cs:organizer>
    <cal:supported-calendar-component-set>
      <cal:comp name="VEVENT"/>
      <cal:comp name="VTODO"/>
    </cal:supported-calendar-component-set>
  </cs:invite-notification>
</cs:root>

FOO
            ),

        );

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testMissingArg() {

        new Sabre_CalDAV_Notifications_Notification_Invite(array());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testUnknownArg() {

        new Sabre_CalDAV_Notifications_Notification_Invite(array(
            'foo-i-will-break' => true,

            'id' => 1,
            'etag' => '"bla"',
            'href' => 'abc',
            'dtStamp' => 'def',
            'type' => 'ghi',
            'readOnly' => true,
            'hostUrl' => 'jkl',
            'organizer' => 'mno',
        ));

    }
}
