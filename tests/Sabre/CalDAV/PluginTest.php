<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use DateTime;
use DateTimeZone;
use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;

class PluginTest extends \PHPUnit\Framework\TestCase
{
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

    public function setup(): void
    {
        $caldavNS = '{urn:ietf:params:xml:ns:caldav}';

        $this->caldavBackend = new Backend\Mock([
            [
                'id' => 1,
                'uri' => 'UUID-123467',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'user1 calendar',
                $caldavNS.'calendar-description' => 'Calendar description',
                '{http://apple.com/ns/ical/}calendar-order' => '1',
                '{http://apple.com/ns/ical/}calendar-color' => '#FF0000',
                $caldavNS.'supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VEVENT', 'VTODO']),
            ],
            [
                'id' => 2,
                'uri' => 'UUID-123468',
                'principaluri' => 'principals/user1',
                '{DAV:}displayname' => 'user1 calendar2',
                $caldavNS.'calendar-description' => 'Calendar description',
                '{http://apple.com/ns/ical/}calendar-order' => '1',
                '{http://apple.com/ns/ical/}calendar-color' => '#FF0000',
                $caldavNS.'supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VEVENT', 'VTODO']),
            ],
        ], [
            1 => [
                'UUID-2345' => [
                    'calendardata' => TestUtil::getTestCalendarData(),
                ],
            ],
        ]);
        $principalBackend = new DAVACL\PrincipalBackend\Mock();
        $principalBackend->setGroupMemberSet('principals/admin/calendar-proxy-read', ['principals/user1']);
        $principalBackend->setGroupMemberSet('principals/admin/calendar-proxy-write', ['principals/user1']);
        $principalBackend->addPrincipal([
            'uri' => 'principals/admin/calendar-proxy-read',
        ]);
        $principalBackend->addPrincipal([
            'uri' => 'principals/admin/calendar-proxy-write',
        ]);

        $calendars = new CalendarRoot($principalBackend, $this->caldavBackend);
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
        $aclPlugin = new DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin($aclPlugin);

        // Adding Auth plugin, and ensuring that we are logged in.
        $authBackend = new DAV\Auth\Backend\Mock();
        $authBackend->setPrincipal('principals/user1');
        $authPlugin = new DAV\Auth\Plugin($authBackend);
        $authPlugin->beforeMethod(new \Sabre\HTTP\Request('GET', '/'), new \Sabre\HTTP\Response());
        $this->server->addPlugin($authPlugin);

        // This forces a login
        $authPlugin->beforeMethod(new HTTP\Request('GET', '/'), new HTTP\Response());

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    public function testSimple()
    {
        self::assertEquals(['MKCALENDAR'], $this->plugin->getHTTPMethods('calendars/user1/randomnewcalendar'));
        self::assertEquals(['calendar-access', 'calendar-proxy'], $this->plugin->getFeatures());
        self::assertEquals(
            'caldav',
            $this->plugin->getPluginInfo()['name']
        );
    }

    public function testUnknownMethodPassThrough()
    {
        $request = new HTTP\Request('MKBREAKFAST', '/');

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(501, $this->response->status, 'Incorrect status returned. Full response body:'.$this->response->getBodyAsString());
    }

    public function testGetWithoutContentType()
    {
        $request = new HTTP\Request('GET', '/');
        $this->plugin->httpAfterGet($request, $this->response);
        self::assertTrue(true);
    }

    public function testReportPassThrough()
    {
        $request = new HTTP\Request('REPORT', '/', ['Content-Type' => 'application/xml']);
        $request->setBody('<?xml version="1.0"?><s:somereport xmlns:s="http://www.rooftopsolutions.nl/NS/example" />');

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(415, $this->response->status);
    }

    public function testMkCalendarBadLocation()
    {
        $request = new HTTP\Request('MKCALENDAR', '/blabla');

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

        self::assertEquals(403, $this->response->status);
    }

    public function testMkCalendarNoParentNode()
    {
        $request = new HTTP\Request('MKCALENDAR', '/doesntexist/calendar');

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

        self::assertEquals(409, $this->response->status);
    }

    public function testMkCalendarExistingCalendar()
    {
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI' => '/calendars/user1/UUID-123467',
        ]);

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

        self::assertEquals(405, $this->response->status);
    }

    public function testMkCalendarSucceed()
    {
        $request = new HTTP\Request('MKCALENDAR', '/calendars/user1/NEWCALENDAR');

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
         <C:calendar-timezone><![CDATA['.$timezone.']]></C:calendar-timezone>
       </D:prop>
     </D:set>
   </C:mkcalendar>';

        $request->setBody($body);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(201, $this->response->status, 'Invalid response code received. Full response body: '.$this->response->getBodyAsString());

        $calendars = $this->caldavBackend->getCalendarsForUser('principals/user1');
        self::assertEquals(3, count($calendars));

        $newCalendar = null;
        foreach ($calendars as $calendar) {
            if ('NEWCALENDAR' === $calendar['uri']) {
                $newCalendar = $calendar;
                break;
            }
        }

        self::assertIsArray($newCalendar);

        $keys = [
            'uri' => 'NEWCALENDAR',
            'id' => null,
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Calendar restricted to events.',
            '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => $timezone,
            '{DAV:}displayname' => 'Lisa\'s Events',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => null,
        ];

        foreach ($keys as $key => $value) {
            self::assertArrayHasKey($key, $newCalendar);

            if (is_null($value)) {
                continue;
            }
            self::assertEquals($value, $newCalendar[$key]);
        }
        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        self::assertTrue($newCalendar[$sccs] instanceof Xml\Property\SupportedCalendarComponentSet);
        self::assertEquals(['VEVENT'], $newCalendar[$sccs]->getValue());
    }

    public function testMkCalendarEmptyBodySucceed()
    {
        $request = new HTTP\Request('MKCALENDAR', '/calendars/user1/NEWCALENDAR');

        $request->setBody('');
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(201, $this->response->status, 'Invalid response code received. Full response body: '.$this->response->getBodyAsString());

        $calendars = $this->caldavBackend->getCalendarsForUser('principals/user1');
        self::assertEquals(3, count($calendars));

        $newCalendar = null;
        foreach ($calendars as $calendar) {
            if ('NEWCALENDAR' === $calendar['uri']) {
                $newCalendar = $calendar;
                break;
            }
        }

        self::assertIsArray($newCalendar);

        $keys = [
            'uri' => 'NEWCALENDAR',
            'id' => null,
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => null,
        ];

        foreach ($keys as $key => $value) {
            self::assertArrayHasKey($key, $newCalendar);

            if (is_null($value)) {
                continue;
            }
            self::assertEquals($value, $newCalendar[$key]);
        }
        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        self::assertTrue($newCalendar[$sccs] instanceof Xml\Property\SupportedCalendarComponentSet);
        self::assertEquals(['VEVENT', 'VTODO'], $newCalendar[$sccs]->getValue());
    }

    public function testMkCalendarBadXml()
    {
        $request = new HTTP\Request('MKCALENDAR', '/blabla');
        $body = 'This is not xml';

        $request->setBody($body);
        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(400, $this->response->status);
    }

    public function testPrincipalProperties()
    {
        $httpRequest = new HTTP\Request('FOO', '/blabla', ['Host' => 'sabredav.org']);
        $this->server->httpRequest = $httpRequest;

        $props = $this->server->getPropertiesForPath('/principals/user1', [
            '{'.Plugin::NS_CALDAV.'}calendar-home-set',
            '{'.Plugin::NS_CALENDARSERVER.'}calendar-proxy-read-for',
            '{'.Plugin::NS_CALENDARSERVER.'}calendar-proxy-write-for',
            '{'.Plugin::NS_CALENDARSERVER.'}notification-URL',
            '{'.Plugin::NS_CALENDARSERVER.'}email-address-set',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(200, $props[0]);

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-home-set', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-home-set'];
        self::assertInstanceOf(\Sabre\DAV\Xml\Property\Href::class, $prop);
        self::assertEquals('calendars/user1/', $prop->getHref());

        self::assertArrayHasKey('{http://calendarserver.org/ns/}calendar-proxy-read-for', $props[0][200]);
        $prop = $props[0][200]['{http://calendarserver.org/ns/}calendar-proxy-read-for'];
        self::assertInstanceOf(\Sabre\DAV\Xml\Property\Href::class, $prop);
        self::assertEquals(['principals/admin/'], $prop->getHrefs());

        self::assertArrayHasKey('{http://calendarserver.org/ns/}calendar-proxy-write-for', $props[0][200]);
        $prop = $props[0][200]['{http://calendarserver.org/ns/}calendar-proxy-write-for'];
        self::assertInstanceOf(\Sabre\DAV\Xml\Property\Href::class, $prop);
        self::assertEquals(['principals/admin/'], $prop->getHrefs());

        self::assertArrayHasKey('{'.Plugin::NS_CALENDARSERVER.'}email-address-set', $props[0][200]);
        $prop = $props[0][200]['{'.Plugin::NS_CALENDARSERVER.'}email-address-set'];
        self::assertInstanceOf(\Sabre\CalDAV\Xml\Property\EmailAddressSet::class, $prop);
        self::assertEquals(['user1.sabredav@sabredav.org'], $prop->getValue());
    }

    public function testSupportedReportSetPropertyNonCalendar()
    {
        $props = $this->server->getPropertiesForPath('/calendars/user1', [
            '{DAV:}supported-report-set',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(200, $props[0]);
        self::assertArrayHasKey('{DAV:}supported-report-set', $props[0][200]);

        $prop = $props[0][200]['{DAV:}supported-report-set'];

        self::assertInstanceOf(\Sabre\DAV\Xml\Property\SupportedReportSet::class, $prop);
        $value = [
            '{DAV:}expand-property',
            '{DAV:}principal-match',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        ];
        self::assertEquals($value, $prop->getValue());
    }

    /**
     * @depends testSupportedReportSetPropertyNonCalendar
     */
    public function testSupportedReportSetProperty()
    {
        $props = $this->server->getPropertiesForPath('/calendars/user1/UUID-123467', [
            '{DAV:}supported-report-set',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(200, $props[0]);
        self::assertArrayHasKey('{DAV:}supported-report-set', $props[0][200]);

        $prop = $props[0][200]['{DAV:}supported-report-set'];

        self::assertInstanceOf(\Sabre\DAV\Xml\Property\SupportedReportSet::class, $prop);
        $value = [
            '{urn:ietf:params:xml:ns:caldav}calendar-multiget',
            '{urn:ietf:params:xml:ns:caldav}calendar-query',
            '{urn:ietf:params:xml:ns:caldav}free-busy-query',
            '{DAV:}expand-property',
            '{DAV:}principal-match',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        ];
        self::assertEquals($value, $prop->getValue());
    }

    public function testSupportedReportSetUserCalendars()
    {
        $this->server->addPlugin(new \Sabre\DAV\Sync\Plugin());

        $props = $this->server->getPropertiesForPath('/calendars/user1', [
            '{DAV:}supported-report-set',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(200, $props[0]);
        self::assertArrayHasKey('{DAV:}supported-report-set', $props[0][200]);

        $prop = $props[0][200]['{DAV:}supported-report-set'];

        self::assertInstanceOf(\Sabre\DAV\Xml\Property\SupportedReportSet::class, $prop);
        $value = [
            '{DAV:}sync-collection',
            '{DAV:}expand-property',
            '{DAV:}principal-match',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        ];
        self::assertEquals($value, $prop->getValue());
    }

    /**
     * @depends testSupportedReportSetProperty
     */
    public function testCalendarMultiGetReport()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data />'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>'.
            '</c:calendar-multiget>';

        $request = new HTTP\Request('REPORT', '/calendars/user1', ['Depth' => '1']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(207, $this->response->status, 'Invalid HTTP status received. Full response body');

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

        self::assertXmlStringEqualsXmlString($expected, $this->response->getBodyAsString());
    }

    /**
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarMultiGetReportExpand()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand start="20110101T000000Z" end="20111231T235959Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>'.
            '</c:calendar-multiget>';

        $request = new HTTP\Request('REPORT', '/calendars/user1', ['Depth' => '1']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Invalid HTTP status received. Full response body: '.$bodyAsString);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal = $expectedIcal->expand(
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

        self::assertXmlStringEqualsXmlString($expected, $bodyAsString);
    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarQueryReport()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<c:filter>'.
            '  <c:comp-filter name="VCALENDAR">'.
            '    <c:comp-filter name="VEVENT" />'.
            '  </c:comp-filter>'.
            '</c:filter>'.
            '</c:calendar-query>';

        $request = new HTTP\Request('REPORT', '/calendars/user1/UUID-123467', ['Depth' => '1']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Received an unexpected status. Full response body: '.$bodyAsString);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal = $expectedIcal->expand(
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

        self::assertXmlStringEqualsXmlString($expected, $bodyAsString);
    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarQueryReportWindowsPhone()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<c:filter>'.
            '  <c:comp-filter name="VCALENDAR">'.
            '    <c:comp-filter name="VEVENT" />'.
            '  </c:comp-filter>'.
            '</c:filter>'.
            '</c:calendar-query>';

        $request = new HTTP\Request('REPORT', '/calendars/user1/UUID-123467', [
            'Depth' => '0',
            'User-Agent' => 'MSFT-WP/8.10.14219 (gzip)',
        ]);

        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Received an unexpected status. Full response body: '.$bodyAsString);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal = $expectedIcal->expand(
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

        self::assertXmlStringEqualsXmlString($expected, $bodyAsString);
    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarQueryReportBadDepth()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<c:filter>'.
            '  <c:comp-filter name="VCALENDAR">'.
            '    <c:comp-filter name="VEVENT" />'.
            '  </c:comp-filter>'.
            '</c:filter>'.
            '</c:calendar-query>';

        $request = new HTTP\Request('REPORT', '/calendars/user1/UUID-123467', [
            'Depth' => '0',
        ]);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(400, $this->response->status, 'Received an unexpected status. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testCalendarQueryReport
     */
    public function testCalendarQueryReportNoCalData()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<c:filter>'.
            '  <c:comp-filter name="VCALENDAR">'.
            '    <c:comp-filter name="VEVENT" />'.
            '  </c:comp-filter>'.
            '</c:filter>'.
            '</c:calendar-query>';

        $request = new HTTP\Request('REPORT', '/calendars/user1/UUID-123467', [
            'Depth' => '1',
        ]);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $bodyAsString = $this->server->httpResponse->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Received an unexpected status. Full response body: '.$bodyAsString);

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

        self::assertXmlStringEqualsXmlString($expected, $bodyAsString);
    }

    /**
     * @depends testCalendarQueryReport
     */
    public function testCalendarQueryReportNoFilters()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data />'.
            '  <d:getetag />'.
            '</d:prop>'.
            '</c:calendar-query>';

        $request = new HTTP\Request('REPORT', '/calendars/user1/UUID-123467');
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(400, $this->response->status, 'Received an unexpected status. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarQueryReport1Object()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand start="20000101T000000Z" end="20101231T235959Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<c:filter>'.
            '  <c:comp-filter name="VCALENDAR">'.
            '    <c:comp-filter name="VEVENT" />'.
            '  </c:comp-filter>'.
            '</c:filter>'.
            '</c:calendar-query>';

        $request = new HTTP\Request('REPORT', '/calendars/user1/UUID-123467/UUID-2345', ['Depth' => '0']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $bodyAsString = $this->server->httpResponse->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Received an unexpected status. Full response body: '.$bodyAsString);

        $expectedIcal = TestUtil::getTestCalendarData();
        $expectedIcal = \Sabre\VObject\Reader::read($expectedIcal);
        $expectedIcal = $expectedIcal->expand(
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

        self::assertXmlStringEqualsXmlString($expected, $bodyAsString);
    }

    /**
     * @depends testSupportedReportSetProperty
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarQueryReport1ObjectNoCalData()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<c:filter>'.
            '  <c:comp-filter name="VCALENDAR">'.
            '    <c:comp-filter name="VEVENT" />'.
            '  </c:comp-filter>'.
            '</c:filter>'.
            '</c:calendar-query>';

        $request = new HTTP\Request('REPORT', '/calendars/user1/UUID-123467/UUID-2345', ['Depth' => '0']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $bodyAsString = $this->server->httpResponse->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'Received an unexpected status. Full response body: '.$bodyAsString);

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

        self::assertXmlStringEqualsXmlString($expected, $bodyAsString);
    }

    public function testHTMLActionsPanel()
    {
        $output = '';
        $r = $this->server->emit('onHTMLActionsPanel', [$this->server->tree->getNodeForPath('calendars/user1'), &$output]);
        self::assertFalse($r);

        self::assertTrue((bool) strpos($output, 'Display name'));
    }

    /**
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarMultiGetReportNoEnd()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand start="20110101T000000Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>'.
            '</c:calendar-multiget>';

        $request = new HTTP\Request('REPORT', '/calendars/user1', ['Depth' => '1']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(400, $this->response->status, 'Invalid HTTP status received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarMultiGetReportNoStart()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand end="20110101T000000Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>'.
            '</c:calendar-multiget>';

        $request = new HTTP\Request('REPORT', '/calendars/user1', ['Depth' => '1']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(400, $this->response->status, 'Invalid HTTP status received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testCalendarMultiGetReport
     */
    public function testCalendarMultiGetReportEndBeforeStart()
    {
        $body =
            '<?xml version="1.0"?>'.
            '<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">'.
            '<d:prop>'.
            '  <c:calendar-data>'.
            '     <c:expand start="20200101T000000Z" end="20110101T000000Z" />'.
            '  </c:calendar-data>'.
            '  <d:getetag />'.
            '</d:prop>'.
            '<d:href>/calendars/user1/UUID-123467/UUID-2345</d:href>'.
            '</c:calendar-multiget>';

        $request = new HTTP\Request('REPORT', '/calendars/user1', ['Depth' => '1']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        self::assertEquals(400, $this->response->status, 'Invalid HTTP status received. Full response body: '.$this->response->getBodyAsString());
    }

    /**
     * @depends testSupportedReportSetPropertyNonCalendar
     */
    public function testCalendarProperties()
    {
        $ns = '{urn:ietf:params:xml:ns:caldav}';
        $props = $this->server->getProperties('calendars/user1/UUID-123467', [
            $ns.'max-resource-size',
            $ns.'supported-calendar-data',
            $ns.'supported-collation-set',
        ]);

        self::assertEquals([
            $ns.'max-resource-size' => 10000000,
            $ns.'supported-calendar-data' => new Xml\Property\SupportedCalendarData(),
            $ns.'supported-collation-set' => new Xml\Property\SupportedCollationSet(),
        ], $props);
    }
}
