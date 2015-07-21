<?php

namespace Sabre\CalDAV;

use
    Sabre\DAVACL,
    Sabre\DAV,
    Sabre\HTTP,
    DateTime,
    DateTimeZone;

class PluginTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var DAV\Server
     */
    protected $server;
    /**
     * @var Plugin
     */
    protected $plugin;
    protected $response;
    /**
     * @var Backend\PDO
     */
    protected $caldavBackend;

    function setup() {

        $this->caldavBackend = new Backend\Mock(array(
            array(
                'id' => 1,
                'uri' => 'UUID-123467',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'user1 calendar',
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Calendar description',
                '{http://apple.com/ns/ical/}calendar-order' => '1',
                '{http://apple.com/ns/ical/}calendar-color' => '#FF0000',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VEVENT','VTODO']),
            ),
            array(
                'id' => 2,
                'uri' => 'UUID-123468',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'user1 calendar2',
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Calendar description',
                '{http://apple.com/ns/ical/}calendar-order' => '1',
                '{http://apple.com/ns/ical/}calendar-color' => '#FF0000',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VEVENT','VTODO']),
            )
        ), array(
            1 => array(
                'UUID-2345' => array(
                    'calendardata' => TestUtil::getTestCalendarData(),
                )
            )
        ));
        $principalBackend = new DAVACL\PrincipalBackend\Mock();
        $principalBackend->setGroupMemberSet('principals/admin/calendar-proxy-read',array('principals/user1'));
        $principalBackend->setGroupMemberSet('principals/admin/calendar-proxy-write',array('principals/user1'));
        $principalBackend->addPrincipal(array(
            'uri' => 'principals/admin/calendar-proxy-read',
        ));
        $principalBackend->addPrincipal(array(
            'uri' => 'principals/admin/calendar-proxy-write',
        ));

        $calendars = new CalendarRoot($principalBackend,$this->caldavBackend);
        $principals = new Principal\Collection($principalBackend);

        $root = new DAV\SimpleCollection('root');
        $root->addChild($calendars);
        $root->addChild($principals);

        $this->server = new DAV\Server($root);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;
        $this->server->setBaseUri('/');
        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);

        // Adding ACL plugin
        $this->server->addPlugin(new DAVACL\Plugin());

        // Adding Auth plugin, and ensuring that we are logged in.
        $authBackend = new DAV\Auth\Backend\Mock();
        $authBackend->setPrincipal('principals/user1');
        $authPlugin = new DAV\Auth\Plugin($authBackend, 'SabreDAV');
        $authPlugin->beforeMethod(new \Sabre\HTTP\Request(), new \Sabre\HTTP\Response());
        $this->server->addPlugin($authPlugin);

        // This forces a login
        $authPlugin->beforeMethod(new HTTP\Request(), new HTTP\Response());

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

    }

    function testSimple() {

        $this->assertEquals(array('MKCALENDAR'), $this->plugin->getHTTPMethods('calendars/user1/randomnewcalendar'));
        $this->assertEquals(array('calendar-access','calendar-proxy'), $this->plugin->getFeatures());
        $this->assertEquals(
            'caldav',
            $this->plugin->getPluginInfo()['name']
        );

    }

    function testUnknownMethodPassThrough() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'MKBREAKFAST',
            'REQUEST_URI'    => '/',
        ));

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(501, $this->response->status,'Incorrect status returned. Full response body:' . $this->response->body);

    }

    function testReportPassThrough() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'    => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'REQUEST_URI'       => '/',
        ));
        $request->setBody('<?xml version="1.0"?><s:somereport xmlns:s="http://www.rooftopsolutions.nl/NS/example" />');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(415, $this->response->status);

    }

    function testMkCalendarBadLocation() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI'    => '/blabla',
        ));

        $body = '<?xml version="1.0" encoding="utf-8" ?>
   <C:mkcalendar xmlns:D="DAV:"
                 xmlns:C="urn:ietf:params:xml:ns:caldav">
     <D:set>
       <D:prop>
         <D:displayname>Lisa\'s Events</D:displayname>
         <C:calendar-description xml:lang="en"
   >Calendar restricted to events.</C:calendar-description>
         <C:supported-calendar-component-set>
           <C:comp name="VEVENT"/>
         </C:supported-calendar-component-set>
         <C:calendar-timezone><![CDATA[BEGIN:VCALENDAR
   PRODID:-//Example Corp.//CalDAV Client//EN
   VERSION:2.0
   BEGIN:VTIMEZONE
   TZID:US-Eastern
   LAST-MODIFIED:19870101T000000Z
   BEGIN:STANDARD
   DTSTART:19671029T020000
   RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
   TZOFFSETFROM:-0400
   TZOFFSETTO:-0500
   TZNAME:Eastern Standard Time (US & Canada)
   END:STANDARD
   BEGIN:DAYLIGHT
   DTSTART:19870405T020000
   RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4
   TZOFFSETFROM:-0500
   TZOFFSETTO:-0400
   TZNAME:Eastern Daylight Time (US & Canada)
   END:DAYLIGHT
   END:VTIMEZONE
   END:VCALENDAR
   ]]></C:calendar-timezone>
       </D:prop>
     </D:set>
   </C:mkcalendar>';

        $request->setBody($body);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(403, $this->response->status);

    }

    function testMkCalendarNoParentNode() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI'    => '/doesntexist/calendar',
        ));

        $body = '<?xml version="1.0" encoding="utf-8" ?>
   <C:mkcalendar xmlns:D="DAV:"
                 xmlns:C="urn:ietf:params:xml:ns:caldav">
     <D:set>
       <D:prop>
         <D:displayname>Lisa\'s Events</D:displayname>
         <C:calendar-description xml:lang="en"
   >Calendar restricted to events.</C:calendar-description>
         <C:supported-calendar-component-set>
           <C:comp name="VEVENT"/>
         </C:supported-calendar-component-set>
         <C:calendar-timezone><![CDATA[BEGIN:VCALENDAR
   PRODID:-//Example Corp.//CalDAV Client//EN
   VERSION:2.0
   BEGIN:VTIMEZONE
   TZID:US-Eastern
   LAST-MODIFIED:19870101T000000Z
   BEGIN:STANDARD
   DTSTART:19671029T020000
   RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
   TZOFFSETFROM:-0400
   TZOFFSETTO:-0500
   TZNAME:Eastern Standard Time (US & Canada)
   END:STANDARD
   BEGIN:DAYLIGHT
   DTSTART:19870405T020000
   RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4
   TZOFFSETFROM:-0500
   TZOFFSETTO:-0400
   TZNAME:Eastern Daylight Time (US & Canada)
   END:DAYLIGHT
   END:VTIMEZONE
   END:VCALENDAR
   ]]></C:calendar-timezone>
       </D:prop>
     </D:set>
   </C:mkcalendar>';

        $request->setBody($body);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(409, $this->response->status);

    }

    function testMkCalendarExistingCalendar() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI'    => '/calendars/user1/UUID-123467',
        ));

        $body = '<?xml version="1.0" encoding="utf-8" ?>
   <C:mkcalendar xmlns:D="DAV:"
                 xmlns:C="urn:ietf:params:xml:ns:caldav">
     <D:set>
       <D:prop>
         <D:displayname>Lisa\'s Events</D:displayname>
         <C:calendar-description xml:lang="en"
   >Calendar restricted to events.</C:calendar-description>
         <C:supported-calendar-component-set>
           <C:comp name="VEVENT"/>
         </C:supported-calendar-component-set>
         <C:calendar-timezone><![CDATA[BEGIN:VCALENDAR
   PRODID:-//Example Corp.//CalDAV Client//EN
   VERSION:2.0
   BEGIN:VTIMEZONE
   TZID:US-Eastern
   LAST-MODIFIED:19870101T000000Z
   BEGIN:STANDARD
   DTSTART:19671029T020000
   RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
   TZOFFSETFROM:-0400
   TZOFFSETTO:-0500
   TZNAME:Eastern Standard Time (US & Canada)
   END:STANDARD
   BEGIN:DAYLIGHT
   DTSTART:19870405T020000
   RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4
   TZOFFSETFROM:-0500
   TZOFFSETTO:-0400
   TZNAME:Eastern Daylight Time (US & Canada)
   END:DAYLIGHT
   END:VTIMEZONE
   END:VCALENDAR
   ]]></C:calendar-timezone>
       </D:prop>
     </D:set>
   </C:mkcalendar>';

        $request->setBody($body);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(405, $this->response->status);

    }

    function testMkCalendarSucceed() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI'    => '/calendars/user1/NEWCALENDAR',
        ));

        $timezone = 'BEGIN:VCALENDAR
PRODID:-//Example Corp.//CalDAV Client//EN
VERSION:2.0
BEGIN:VTIMEZONE
TZID:US-Eastern
LAST-MODIFIED:19870101T000000Z
BEGIN:STANDARD
DTSTART:19671029T020000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:Eastern Standard Time (US & Canada)
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:19870405T020000
RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:Eastern Daylight Time (US & Canada)
END:DAYLIGHT
END:VTIMEZONE
END:VCALENDAR';

        $body = '<?xml version="1.0" encoding="utf-8" ?>
   <C:mkcalendar xmlns:D="DAV:"
                 xmlns:C="urn:ietf:params:xml:ns:caldav">
     <D:set>
       <D:prop>
         <D:displayname>Lisa\'s Events</D:displayname>
         <C:calendar-description xml:lang="en"
   >Calendar restricted to events.</C:calendar-description>
         <C:supported-calendar-component-set>
           <C:comp name="VEVENT"/>
         </C:supported-calendar-component-set>
         <C:calendar-timezone><![CDATA[' . $timezone . ']]></C:calendar-timezone>
       </D:prop>
     </D:set>
   </C:mkcalendar>';

        $request->setBody($body);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(201, $this->response->status,'Invalid response code received. Full response body: ' .$this->response->body);

        $calendars = $this->caldavBackend->getCalendarsForUser('principals/user1');
        $this->assertEquals(3, count($calendars));

        $newCalendar = null;
        foreach($calendars as $calendar) {
           if ($calendar['uri'] === 'NEWCALENDAR') {
                $newCalendar = $calendar;
                break;
           }
        }

        $this->assertInternalType('array',$newCalendar);

        $keys = array(
            'uri' => 'NEWCALENDAR',
            'id' => null,
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Calendar restricted to events.',
            '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => $timezone,
            '{DAV:}displayname' => 'Lisa\'s Events',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => null,
        );

        foreach($keys as $key=>$value) {

            $this->assertArrayHasKey($key, $newCalendar);

            if (is_null($value)) continue;
            $this->assertEquals($value, $newCalendar[$key]);

        }
        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        $this->assertTrue($newCalendar[$sccs] instanceof Xml\Property\SupportedCalendarComponentSet);
        $this->assertEquals(array('VEVENT'),$newCalendar[$sccs]->getValue());

    }

    function testMkCalendarEmptyBodySucceed() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI'    => '/calendars/user1/NEWCALENDAR',
        ));

        $request->setBody('');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(201, $this->response->status,'Invalid response code received. Full response body: ' .$this->response->body);

        $calendars = $this->caldavBackend->getCalendarsForUser('principals/user1');
        $this->assertEquals(3, count($calendars));

        $newCalendar = null;
        foreach($calendars as $calendar) {
           if ($calendar['uri'] === 'NEWCALENDAR') {
                $newCalendar = $calendar;
                break;
           }
        }

        $this->assertInternalType('array',$newCalendar);

        $keys = array(
            'uri' => 'NEWCALENDAR',
            'id' => null,
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => null,
        );

        foreach($keys as $key=>$value) {

            $this->assertArrayHasKey($key, $newCalendar);

            if (is_null($value)) continue;
            $this->assertEquals($value, $newCalendar[$key]);

        }
        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        $this->assertTrue($newCalendar[$sccs] instanceof Xml\Property\SupportedCalendarComponentSet);
        $this->assertEquals(array('VEVENT','VTODO'),$newCalendar[$sccs]->getValue());

    }

    function testMkCalendarBadXml() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI'    => '/blabla',
        ));

        $body = 'This is not xml';

        $request->setBody($body);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(400, $this->response->status);

    }

    function testPrincipalProperties() {

        $httpRequest = HTTP\Sapi::createFromServerArray(array(
            'HTTP_HOST' => 'sabredav.org',
        ));
        $this->server->httpRequest = $httpRequest;

        $props = $this->server->getPropertiesForPath('/principals/user1',array(
            '{' . Plugin::NS_CALDAV . '}calendar-home-set',
            '{' . Plugin::NS_CALENDARSERVER . '}calendar-proxy-read-for',
            '{' . Plugin::NS_CALENDARSERVER . '}calendar-proxy-write-for',
            '{' . Plugin::NS_CALENDARSERVER . '}notification-URL',
            '{' . Plugin::NS_CALENDARSERVER . '}email-address-set',
        ));

        $this->assertArrayHasKey(0,$props);
        $this->assertArrayHasKey(200,$props[0]);


        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-home-set',$props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-home-set'];
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $prop);
        $this->assertEquals('calendars/user1/',$prop->getHref());

        $this->assertArrayHasKey('{http://calendarserver.org/ns/}calendar-proxy-read-for', $props[0][200]);
        $prop = $props[0][200]['{http://calendarserver.org/ns/}calendar-proxy-read-for'];
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $prop);
        $this->assertEquals(array('principals/admin/'), $prop->getHrefs());

        $this->assertArrayHasKey('{http://calendarserver.org/ns/}calendar-proxy-write-for', $props[0][200]);
        $prop = $props[0][200]['{http://calendarserver.org/ns/}calendar-proxy-write-for'];
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $prop);
        $this->assertEquals(array('principals/admin/'), $prop->getHrefs());

        $this->assertArrayHasKey('{' . Plugin::NS_CALENDARSERVER . '}email-address-set',$props[0][200]);
        $prop = $props[0][200]['{' . Plugin::NS_CALENDARSERVER . '}email-address-set'];
        $this->assertInstanceOf('Sabre\\CalDAV\\Xml\\Property\\EmailAddressSet', $prop);
        $this->assertEquals(['user1.sabredav@sabredav.org'],$prop->getValue());

    }

    function testSupportedReportSetPropertyNonCalendar() {

        $props = $this->server->getPropertiesForPath('/calendars/user1',array(
            '{DAV:}supported-report-set',
        ));

        $this->assertArrayHasKey(0,$props);
        $this->assertArrayHasKey(200,$props[0]);
        $this->assertArrayHasKey('{DAV:}supported-report-set',$props[0][200]);

        $prop = $props[0][200]['{DAV:}supported-report-set'];

        $this->assertInstanceOf('\\Sabre\\DAV\\Xml\\Property\\SupportedReportSet', $prop);
        $value = array(
            '{DAV:}expand-property',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set'
        );
        $this->assertEquals($value,$prop->getValue());

    }

    /**
     * @depends testSupportedReportSetPropertyNonCalendar
     */
    function testSupportedReportSetProperty() {

        $props = $this->server->getPropertiesForPath('/calendars/user1/UUID-123467',array(
            '{DAV:}supported-report-set',
        ));

        $this->assertArrayHasKey(0,$props);
        $this->assertArrayHasKey(200,$props[0]);
        $this->assertArrayHasKey('{DAV:}supported-report-set',$props[0][200]);

        $prop = $props[0][200]['{DAV:}supported-report-set'];

        $this->assertInstanceOf('\\Sabre\\DAV\\Xml\\Property\\SupportedReportSet', $prop);
        $value = array(
            '{urn:ietf:params:xml:ns:caldav}calendar-multiget',
            '{urn:ietf:params:xml:ns:caldav}calendar-query',
            '{urn:ietf:params:xml:ns:caldav}free-busy-query',
            '{DAV:}expand-property',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set'
        );
        $this->assertEquals($value,$prop->getValue());

    }

    function testSupportedReportSetUserCalendars() {

        $this->server->addPlugin(new \Sabre\DAV\Sync\Plugin());

        $props = $this->server->getPropertiesForPath('/calendars/user1',array(
            '{DAV:}supported-report-set',
        ));

        $this->assertArrayHasKey(0,$props);
        $this->assertArrayHasKey(200,$props[0]);
        $this->assertArrayHasKey('{DAV:}supported-report-set',$props[0][200]);

        $prop = $props[0][200]['{DAV:}supported-report-set'];

        $this->assertInstanceOf('\\Sabre\\DAV\\Xml\\Property\\SupportedReportSet', $prop);
        $value = array(
            '{DAV:}sync-collection',
            '{DAV:}expand-property',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        );
        $this->assertEquals($value,$prop->getValue());

    }

    /**
     * @depends testSupportedReportSetProperty
     */
    function testCalendarMultiGetReport() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data />' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>' .
            '</c:calendar-multiget>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1',
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(207, $this->response->status,'Invalid HTTP status received. Full response body');

        $expectedIcal = TestUtil::getTestCalendarData();

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:response>
  <d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>
  <d:propstat>
    <d:prop>
      <cal:calendar-data>$expectedIcal</cal:calendar-data>
      <d:getetag>"e207e33c10e5fb9c12cfb35b5d9116e1"</d:getetag>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());

    }

    /**
     * @depends testCalendarMultiGetReport
     */
    function testCalendarMultiGetReportExpand() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand start="20110101T000000Z" end="20111231T235959Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>' .
            '</c:calendar-multiget>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1',
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(207, $this->response->status,'Invalid HTTP status received. Full response body: ' . $this->response->body);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal->expand(
            new DateTime('2011-01-01 00:00:00', new DateTimeZone('UTC')),
            new DateTime('2011-12-31 23:59:59', new DateTimeZone('UTC'))
        );
        $expectedIcal = str_replace("\r\n", "&#xD;\n", $expectedIcal->serialize());

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:response>
  <d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>
  <d:propstat>
    <d:prop>
      <cal:calendar-data>$expectedIcal</cal:calendar-data>
      <d:getetag>"e207e33c10e5fb9c12cfb35b5d9116e1"</d:getetag>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());

    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    function testCalendarQueryReport() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<c:filter>' .
            '  <c:comp-filter name="VCALENDAR">' .
            '    <c:comp-filter name="VEVENT" />' .
            '  </c:comp-filter>' .
            '</c:filter>' .
            '</c:calendar-query>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1/UUID-123467',
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(207, $this->response->status,'Received an unexpected status. Full response body: ' . $this->response->body);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal->expand(
            new DateTime('2000-01-01 00:00:00', new DateTimeZone('UTC')),
            new DateTime('2010-12-31 23:59:59', new DateTimeZone('UTC'))
        );
        $expectedIcal = str_replace("\r\n", "&#xD;\n", $expectedIcal->serialize());

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:response>
  <d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>
  <d:propstat>
    <d:prop>
      <cal:calendar-data>$expectedIcal</cal:calendar-data>
      <d:getetag>"e207e33c10e5fb9c12cfb35b5d9116e1"</d:getetag>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());

    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    function testCalendarQueryReportWindowsPhone() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<c:filter>' .
            '  <c:comp-filter name="VCALENDAR">' .
            '    <c:comp-filter name="VEVENT" />' .
            '  </c:comp-filter>' .
            '</c:filter>' .
            '</c:calendar-query>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'REPORT',
            'REQUEST_URI'     => '/calendars/user1/UUID-123467',
            'HTTP_USER_AGENT' => 'MSFT-WP/8.10.14219 (gzip)',
            'HTTP_DEPTH'      => '0',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(207, $this->response->status,'Received an unexpected status. Full response body: ' . $this->response->body);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal->expand(
            new \DateTime('2000-01-01 00:00:00', new \DateTimeZone('UTC')),
            new \DateTime('2010-12-31 23:59:59', new \DateTimeZone('UTC'))
        );
        $expectedIcal = str_replace("\r\n", "&#xD;\n", $expectedIcal->serialize());

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:response>
  <d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>
  <d:propstat>
    <d:prop>
      <cal:calendar-data>$expectedIcal</cal:calendar-data>
      <d:getetag>"e207e33c10e5fb9c12cfb35b5d9116e1"</d:getetag>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());

    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    function testCalendarQueryReportBadDepth() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<c:filter>' .
            '  <c:comp-filter name="VCALENDAR">' .
            '    <c:comp-filter name="VEVENT" />' .
            '  </c:comp-filter>' .
            '</c:filter>' .
            '</c:calendar-query>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1/UUID-123467',
            'HTTP_DEPTH'     => '0',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(400, $this->response->status,'Received an unexpected status. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testCalendarQueryReport
     */
    function testCalendarQueryReportNoCalData() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<c:filter>' .
            '  <c:comp-filter name="VCALENDAR">' .
            '    <c:comp-filter name="VEVENT" />' .
            '  </c:comp-filter>' .
            '</c:filter>' .
            '</c:calendar-query>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1//UUID-123467',
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(207, $this->response->status,'Received an unexpected status. Full response body: ' . $this->response->body);

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:response>
  <d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>
  <d:propstat>
    <d:prop>
      <d:getetag>"e207e33c10e5fb9c12cfb35b5d9116e1"</d:getetag>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());

    }

    /**
     * @depends testCalendarQueryReport
     */
    function testCalendarQueryReportNoFilters() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data />' .
            '  <d:getetag />' .
            '</d:prop>' .
            '</c:calendar-query>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1//UUID-123467',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(400, $this->response->status,'Received an unexpected status. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    function testCalendarQueryReport1Object() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<c:filter>' .
            '  <c:comp-filter name="VCALENDAR">' .
            '    <c:comp-filter name="VEVENT" />' .
            '  </c:comp-filter>' .
            '</c:filter>' .
            '</c:calendar-query>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1/UUID-123467/UUID-2345',
            'HTTP_DEPTH'     => '0',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(207, $this->response->status,'Received an unexpected status. Full response body: ' . $this->response->body);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal->expand(
            new DateTime('2000-01-01 00:00:00', new DateTimeZone('UTC')),
            new DateTime('2010-12-31 23:59:59', new DateTimeZone('UTC'))
        );
        $expectedIcal = str_replace("\r\n", "&#xD;\n", $expectedIcal->serialize());

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:response>
  <d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>
  <d:propstat>
    <d:prop>
      <cal:calendar-data>$expectedIcal</cal:calendar-data>
      <d:getetag>"e207e33c10e5fb9c12cfb35b5d9116e1"</d:getetag>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());

    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    function testCalendarQueryReport1ObjectNoCalData() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<c:filter>' .
            '  <c:comp-filter name="VCALENDAR">' .
            '    <c:comp-filter name="VEVENT" />' .
            '  </c:comp-filter>' .
            '</c:filter>' .
            '</c:calendar-query>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1/UUID-123467/UUID-2345',
            'HTTP_DEPTH'     => '0',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(207, $this->response->status,'Received an unexpected status. Full response body: ' . $this->response->body);

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
<d:response>
  <d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>
  <d:propstat>
    <d:prop>
      <d:getetag>"e207e33c10e5fb9c12cfb35b5d9116e1"</d:getetag>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());

    }

    function testHTMLActionsPanel() {

        $output = '';
        $r = $this->server->emit('onHTMLActionsPanel', [$this->server->tree->getNodeForPath('calendars/user1'), &$output]);
        $this->assertFalse($r);

        $this->assertTrue(!!strpos($output,'Display name'));

    }

    /**
     * @depends testCalendarMultiGetReport
     */
    function testCalendarMultiGetReportNoEnd() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand start="20110101T000000Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>' .
            '</c:calendar-multiget>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1',
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(400, $this->response->status,'Invalid HTTP status received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testCalendarMultiGetReport
     */
    function testCalendarMultiGetReportNoStart() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand end="20110101T000000Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>' .
            '</c:calendar-multiget>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1',
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(400, $this->response->status,'Invalid HTTP status received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testCalendarMultiGetReport
     */
    function testCalendarMultiGetReportEndBeforeStart() {

        $body =
            '<?xml version="1.0"?>' .
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">' .
            '<d:prop>' .
            '  <c:calendar-data>' .
            '     <c:expand start="20200101T000000Z" end="20110101T000000Z" />' .
            '  </c:calendar-data>' .
            '  <d:getetag />' .
            '</d:prop>' .
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>' .
            '</c:calendar-multiget>';

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/user1',
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(400, $this->response->status,'Invalid HTTP status received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testSupportedReportSetPropertyNonCalendar
     */
    function testCalendarProperties() {

        $ns = '{urn:ietf:params:xml:ns:caldav}';
        $props = $this->server->getProperties('calendars/user1/UUID-123467', [
            $ns . 'max-resource-size',
            $ns . 'supported-calendar-data',
            $ns . 'supported-collation-set',
        ]);

        $this->assertEquals([
            $ns . 'max-resource-size' => 10000000,
            $ns . 'supported-calendar-data' => new Xml\Property\SupportedCalendarData(),
            $ns . 'supported-collation-set' => new Xml\Property\SupportedCollationSet(),
        ], $props);

    }

}
