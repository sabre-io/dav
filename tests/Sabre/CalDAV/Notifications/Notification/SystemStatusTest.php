<?php

class Sabre_CalDAV_Notifications_Notification extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataProvider
     */
    function testSerializers($notification, $expected1, $expected2) {

        $this->assertEquals('foo', $notification->getId());
        $this->assertEquals('"1"', $notification->getETag());


        $dom = new DOMDocument('1.0','UTF-8');
        $elem = $dom->createElement('cs:root');
        $elem->setAttribute('xmlns:cs',Sabre_CalDAV_Plugin::NS_CALENDARSERVER);
        $dom->appendChild($elem);
        $notification->serialize(new Sabre_DAV_Server(), $elem);
        $this->assertEquals($expected1, $dom->saveXML());

        $dom = new DOMDocument('1.0','UTF-8');
        $elem = $dom->createElement('cs:root');
        $elem->setAttribute('xmlns:cs',Sabre_CalDAV_Plugin::NS_CALENDARSERVER);
        $dom->appendChild($elem);
        $notification->serializeBody(new Sabre_DAV_Server(), $elem);
        $this->assertEquals($expected2, $dom->saveXML());


    }

    function dataProvider() {

        return array(

            array(
                new Sabre_CalDAV_Notifications_Notification_SystemStatus('foo','"1"'),
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="high"/></cs:root>' . "\n",
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="high"/></cs:root>' . "\n",
            ),

            array(
                new Sabre_CalDAV_Notifications_Notification_SystemStatus('foo','"1"', Sabre_CalDAV_Notifications_Notification_SystemStatus::TYPE_MEDIUM,'bar'),
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="medium"/></cs:root>' . "\n",
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="medium"><cs:description>bar</cs:description></cs:systemstatus></cs:root>' . "\n",
            ),

            array(
                new Sabre_CalDAV_Notifications_Notification_SystemStatus('foo','"1"', Sabre_CalDAV_Notifications_Notification_SystemStatus::TYPE_LOW,null,'http://example.org/'),
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="low"/></cs:root>' . "\n",
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="low"><d:href>http://example.org/</d:href></cs:systemstatus></cs:root>' . "\n",
            )
        );

    }

}
