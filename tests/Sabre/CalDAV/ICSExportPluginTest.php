<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;
use Sabre\VObject;

class ICSExportPluginTest extends \Sabre\DAVServerTest
{
    protected $setupCalDAV = true;

    protected $icsExportPlugin;

    public function setup(): void
    {
        parent::setUp();
        $this->icsExportPlugin = new ICSExportPlugin();
        $this->server->addPlugin(
            $this->icsExportPlugin
        );

        $id = $this->caldavBackend->createCalendar(
            'principals/admin',
            'UUID-123467',
            [
                '{DAV:}displayname' => 'Hello!',
                '{http://apple.com/ns/ical/}calendar-color' => '#AA0000FF',
            ]
        );

        $this->caldavBackend->createCalendarObject(
            $id,
            'event-1',
            <<<ICS
BEGIN:VCALENDAR
BEGIN:VTIMEZONE
TZID:Europe/Amsterdam
END:VTIMEZONE
BEGIN:VEVENT
UID:event-1
DTSTART;TZID=Europe/Amsterdam:20151020T000000
END:VEVENT
END:VCALENDAR
ICS
        );
        $this->caldavBackend->createCalendarObject(
            $id,
            'todo-1',
            <<<ICS
BEGIN:VCALENDAR
BEGIN:VTODO
UID:todo-1
END:VTODO
END:VCALENDAR
ICS
        );
    }

    public function testInit()
    {
        self::assertEquals(
            $this->icsExportPlugin,
            $this->server->getPlugin('ics-export')
        );
        self::assertEquals($this->icsExportPlugin, $this->server->getPlugin('ics-export'));
        self::assertEquals('ics-export', $this->icsExportPlugin->getPluginInfo()['name']);
    }

    public function testBeforeMethod()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $response = $this->request($request);

        self::assertEquals(200, $response->getStatus());
        self::assertEquals('text/calendar', $response->getHeader('Content-Type'));

        $obj = VObject\Reader::read($response->getBodyAsString());

        self::assertEquals(8, count($obj->children()));
        self::assertEquals(1, count($obj->VERSION));
        self::assertEquals(1, count($obj->CALSCALE));
        self::assertEquals(1, count($obj->PRODID));
        self::assertTrue(false !== strpos((string) $obj->PRODID, DAV\Version::VERSION));
        self::assertEquals(1, count($obj->VTIMEZONE));
        self::assertEquals(1, count($obj->VEVENT));
        self::assertEquals('Hello!', $obj->{'X-WR-CALNAME'});
        self::assertEquals('#AA0000FF', $obj->{'X-APPLE-CALENDAR-COLOR'});
    }

    public function testBeforeMethodNoVersion()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );
        DAV\Server::$exposeVersion = false;
        $response = $this->request($request);
        DAV\Server::$exposeVersion = true;

        self::assertEquals(200, $response->getStatus());
        self::assertEquals('text/calendar', $response->getHeader('Content-Type'));

        $obj = VObject\Reader::read($response->getBodyAsString());

        self::assertEquals(8, count($obj->children()));
        self::assertEquals(1, count($obj->VERSION));
        self::assertEquals(1, count($obj->CALSCALE));
        self::assertEquals(1, count($obj->PRODID));
        self::assertFalse(false !== strpos((string) $obj->PRODID, DAV\Version::VERSION));
        self::assertEquals(1, count($obj->VTIMEZONE));
        self::assertEquals(1, count($obj->VEVENT));
    }

    public function testBeforeMethodNoExport()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467'
        );
        $response = new HTTP\Response();
        self::assertNull($this->icsExportPlugin->httpGet($request, $response));
    }

    public function testACLIntegrationBlocked()
    {
        $aclPlugin = new DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin(
            $aclPlugin
        );

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $this->request($request, 403);
    }

    public function testACLIntegrationNotBlocked()
    {
        $aclPlugin = new DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin(
            $aclPlugin
        );
        $this->server->addPlugin(
            new Plugin()
        );

        $this->autoLogin('admin');

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $response = $this->request($request, 200);
        self::assertEquals('text/calendar', $response->getHeader('Content-Type'));

        $obj = VObject\Reader::read($response->getBodyAsString());

        self::assertEquals(8, count($obj->children()));
        self::assertEquals(1, count($obj->VERSION));
        self::assertEquals(1, count($obj->CALSCALE));
        self::assertEquals(1, count($obj->PRODID));
        self::assertTrue(false !== strpos((string) $obj->PRODID, DAV\Version::VERSION));
        self::assertEquals(1, count($obj->VTIMEZONE));
        self::assertEquals(1, count($obj->VEVENT));
    }

    public function testBadStartParam()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&start=foo'
        );
        $this->request($request, 400);
    }

    public function testBadEndParam()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&end=foo'
        );
        $this->request($request, 400);
    }

    public function testFilterStartEnd()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&start=1&end=2'
        );
        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody());

        self::assertNull($obj->VTIMEZONE);
        self::assertNull($obj->VEVENT);
    }

    public function testExpandNoStart()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&expand=1&end=2'
        );
        $this->request($request, 400);
    }

    public function testExpand()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&start=1&end=2000000000&expand=1'
        );
        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody());

        self::assertNull($obj->VTIMEZONE);
        self::assertEquals(1, count($obj->VEVENT));
    }

    public function testJCal()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'application/calendar+json']
        );

        $response = $this->request($request, 200);
        self::assertEquals('application/calendar+json', $response->getHeader('Content-Type'));
    }

    public function testJCalInUrl()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&accept=jcal'
        );

        $response = $this->request($request, 200);
        self::assertEquals('application/calendar+json', $response->getHeader('Content-Type'));
    }

    public function testNegotiateDefault()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'text/plain']
        );

        $response = $this->request($request, 200);
        self::assertEquals('text/calendar', $response->getHeader('Content-Type'));
    }

    public function testFilterComponentVEVENT()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VEVENT'
        );

        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBodyAsString());
        self::assertEquals(1, count($obj->VTIMEZONE));
        self::assertEquals(1, count($obj->VEVENT));
        self::assertNull($obj->VTODO);
    }

    public function testFilterComponentVTODO()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VTODO'
        );

        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBodyAsString());

        self::assertNull($obj->VTIMEZONE);
        self::assertNull($obj->VEVENT);
        self::assertEquals(1, count($obj->VTODO));
    }

    public function testFilterComponentBadComponent()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VVOODOO'
        );

        $response = $this->request($request, 400);
    }

    public function testContentDisposition()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $response = $this->request($request, 200);
        self::assertEquals('text/calendar', $response->getHeader('Content-Type'));
        self::assertEquals(
            'attachment; filename="UUID-123467-'.date('Y-m-d').'.ics"',
            $response->getHeader('Content-Disposition')
        );
    }

    public function testContentDispositionJson()
    {
        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'application/calendar+json']
        );

        $response = $this->request($request, 200);
        self::assertEquals('application/calendar+json', $response->getHeader('Content-Type'));
        self::assertEquals(
            'attachment; filename="UUID-123467-'.date('Y-m-d').'.json"',
            $response->getHeader('Content-Disposition')
        );
    }

    public function testContentDispositionBadChars()
    {
        $this->caldavBackend->createCalendar(
            'principals/admin',
            'UUID-b_ad"(ch)ars',
            [
                '{DAV:}displayname' => 'Test bad characters',
                '{http://apple.com/ns/ical/}calendar-color' => '#AA0000FF',
            ]
        );

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-b_ad"(ch)ars?export',
            ['Accept' => 'application/calendar+json']
        );

        $response = $this->request($request, 200);
        self::assertEquals('application/calendar+json', $response->getHeader('Content-Type'));
        self::assertEquals(
            'attachment; filename="UUID-b_adchars-'.date('Y-m-d').'.json"',
            $response->getHeader('Content-Disposition')
        );
    }
}
