<?php

class Sabre_CalDAV_Notifications_Notification_InviteTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataProvider
     */
    function testSerializers($notification, $expected) {

        $notification = new Sabre_CalDAV_Notifications_Notification_Invite(
            $notification[0],
            $notification[1],
            $notification[2],
            $notification[3],
            $notification[4],
            $notification[5],
            $notification[6],
            isset($notification[7])?$notification[7]:null,
            isset($notification[8])?$notification[8]:null
        );

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
        $dom->appendChild($elem);
        $notification->serializeBody(new Sabre_DAV_Server(), $elem);
        $this->assertEquals($expected, $dom->saveXML());


    }

    function dataProvider() {

        return array(
            array(
                array('foo', '"1"', 'mailto:foo@example.org', Sabre_CalDAV_SharingPlugin::STATUS_ACCEPTED, true, 'calendar', 'principal/user1', 'John Doe', 'Awesome stuff!'),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-accepted/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
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
                array('foo', '"1"', 'mailto:foo@example.org', Sabre_CalDAV_SharingPlugin::STATUS_DECLINED, true, 'calendar', 'principal/user1', 'John Doe'),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-declined/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:organizer>
      <cs:common-name>John Doe</cs:common-name>
      <d:href>/principal/user1</d:href>
    </cs:organizer>
  </cs:invite-notification>
</cs:root>

FOO
            ),
            array(
                array('foo', '"1"', 'mailto:foo@example.org', Sabre_CalDAV_SharingPlugin::STATUS_NORESPONSE, true, 'calendar', 'principal/user1'),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-noresponse/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:organizer>
      <d:href>/principal/user1</d:href>
    </cs:organizer>
  </cs:invite-notification>
</cs:root>

FOO
            ),
            array(
                array('foo', '"1"', 'mailto:foo@example.org', Sabre_CalDAV_SharingPlugin::STATUS_DELETED, true, 'calendar', 'principal/user1'),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-deleted/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:organizer>
      <d:href>/principal/user1</d:href>
    </cs:organizer>
  </cs:invite-notification>
</cs:root>

FOO
            ),

        );

    }

}
