<?php declare (strict_types=1);

namespace Sabre\CalDAV;

use GuzzleHttp\Psr7\Response;
use Sabre\DAV;
use Sabre\DAVACL;
use GuzzleHttp\Psr7\ServerRequest;
use Sabre\VObject;

class ICSExportPluginTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;

    /** @var ICSExportPlugin */
    protected $icsExportPlugin;

    function setUp() {

        parent::setUp();
        $this->icsExportPlugin = new ICSExportPlugin();
        $this->server->addPlugin(
            $this->icsExportPlugin
        );

        $id = $this->caldavBackend->createCalendar(
            'principals/admin',
            'UUID-123467',
            [
                '{DAV:}displayname'                         => 'Hello!',
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

    function testInit() {

        $this->assertEquals(
            $this->icsExportPlugin,
            $this->server->getPlugin('ics-export')
        );
        $this->assertEquals($this->icsExportPlugin, $this->server->getPlugin('ics-export'));
        $this->assertEquals('ics-export', $this->icsExportPlugin->getPluginInfo()['name']);

    }

    function testBeforeMethod() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export'
        ))->withQueryParams([
            'export' => ''
        ]);

        $old = DAV\Server::$exposeVersion;
        DAV\Server::$exposeVersion = true;
        $response = $this->request($request, 200);
        DAV\Server::$exposeVersion = $old;
        $this->assertEquals('text/calendar', $response->getHeaderLine('Content-Type'));

        $obj = VObject\Reader::read($response->getBody()->getContents());

        $this->assertEquals(8, count($obj->children()));
        $this->assertEquals(1, count($obj->VERSION));
        $this->assertEquals(1, count($obj->CALSCALE));
        $this->assertEquals(1, count($obj->PRODID));
        $this->assertTrue(strpos((string)$obj->PRODID, DAV\Version::VERSION) !== false);
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));
        $this->assertEquals("Hello!", $obj->{"X-WR-CALNAME"});
        $this->assertEquals("#AA0000FF", $obj->{"X-APPLE-CALENDAR-COLOR"});

    }
    function testBeforeMethodNoVersion() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export'
        ))->withQueryParams(['export' => '1']);
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/calendar', $response->getHeaderLine('Content-Type'));

        $obj = VObject\Reader::read($response->getBody()->getContents());

        $this->assertEquals(8, count($obj->children()));
        $this->assertEquals(1, count($obj->VERSION));
        $this->assertEquals(1, count($obj->CALSCALE));
        $this->assertEquals(1, count($obj->PRODID));
        $this->assertFalse(strpos((string)$obj->PRODID, DAV\Version::VERSION) !== false);
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));

    }

    function testBeforeMethodNoExport() {

        $request = new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467'
        );
        $response = new DAV\Psr7ResponseWrapper(function() { return new Response(500); });
        $this->assertNull($this->icsExportPlugin->httpGet(new DAV\Psr7RequestWrapper($request), $response));

    }

    function testACLIntegrationBlocked() {

        $aclPlugin = new DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin(
            $aclPlugin
        );

        $request = new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $this->request($request, 403);

    }

    function testACLIntegrationNotBlocked() {

        $aclPlugin = new DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin(
            $aclPlugin
        );
        $this->server->addPlugin(
            new Plugin()
        );

        $this->autoLogin('admin');

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export'
        ))->withQueryParams([
            'export' => ''
        ]);

        $old = DAV\Server::$exposeVersion;
        DAV\Server::$exposeVersion = true;
        $response = $this->request($request, 200);
        DAV\Server::$exposeVersion = $old;

        $this->assertEquals('text/calendar', $response->getHeaderLine('Content-Type'));

        $obj = VObject\Reader::read($response->getBody()->getContents());

        $this->assertEquals(8, count($obj->children()));
        $this->assertEquals(1, count($obj->VERSION));
        $this->assertEquals(1, count($obj->CALSCALE));
        $this->assertEquals(1, count($obj->PRODID));

        $this->assertTrue(strpos((string)$obj->PRODID, DAV\Version::VERSION) !== false);
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));

    }

    function testBadStartParam() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&start=foo'
        ))->withQueryParams([
            'export' => '',
            'start' => 'foo'
        ]);
        $this->request($request, 400);

    }

    function testBadEndParam() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&end=foo'
        ))->withQueryParams([
            'export' => '',
            'end' => 'foo'
        ]);
        $this->request($request, 400);

    }

    function testFilterStartEnd() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&start=1&end=2'
        ))->withQueryParams([
            'export' => '',
            'start' => '1',
            'end' => '2'
        ]);
        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody()->getContents());

        $this->assertNull($obj->VTIMEZONE);
        $this->assertNull($obj->VEVENT);

    }

    function testExpandNoStart() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&expand=1&end=2'
        ))->withQueryParams([
            'export' => '',
            'expand' => '1',
            'end' => '2'
        ]);
        $this->request($request, 400);

    }

    function testExpand() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&start=1&end=2000000000&expand=1'
        ))->withQueryParams([
            'export' => '',
            'start' => '1',
            'end' => '2000000000',
            'expand' => '1'
        ]);
        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody()->getContents());

        $this->assertNull($obj->VTIMEZONE);
        $this->assertEquals(1, count($obj->VEVENT));

    }

    function testJCal() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'application/calendar+json']
        ))->withQueryParams([
            'export' => ''
        ]);

        $response = $this->request($request, 200);

        $this->assertEquals('application/calendar+json', $response->getHeaderLine('Content-Type'));

    }

    function testJCalInUrl() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&accept=jcal'
        ))->withQueryParams([
            'export' => '',
            'accept' => 'jcal'
        ]);

        $response = $this->request($request, 200);
        $this->assertEquals('application/calendar+json', $response->getHeaderLine('Content-Type'));

    }

    function testNegotiateDefault() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'text/plain']
        ))->withQueryParams([
            'export' => ''
        ]);

        $response = $this->request($request, 200);
        $this->assertEquals('text/calendar', $response->getHeaderLine('Content-Type'));

    }

    function testFilterComponentVEVENT() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VEVENT'
        ))->withQueryParams([
            'export' => '',
            'componentType' => 'VEVENT'
        ]);

        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody()->getContents());
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));
        $this->assertNull($obj->VTODO);

    }

    function testFilterComponentVTODO() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VTODO'
        ))->withQueryParams([
            'export' => '',
            'componentType' => 'VTODO'
        ]);

        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody()->getContents());

        $this->assertNull($obj->VTIMEZONE);
        $this->assertNull($obj->VEVENT);
        $this->assertEquals(1, count($obj->VTODO));

    }

    function testFilterComponentBadComponent() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VVOODOO'
        ))->withQueryParams([
            'export'=> '',
            'componentType' => 'VVOODOO'
        ]);

        $response = $this->request($request, 400);

    }

    function testContentDisposition() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export'
        ))->withQueryParams(['export' => '']);

        $response = $this->request($request, 200);
        $this->assertEquals('text/calendar', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="UUID-123467-' . date('Y-m-d') . '.ics"',
            $response->getHeaderLine('Content-Disposition')
        );

    }

    function testContentDispositionJson() {

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'application/calendar+json']
        ))->withQueryParams(['export' => '']);

        $response = $this->request($request, 200);
        $this->assertEquals('application/calendar+json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="UUID-123467-' . date('Y-m-d') . '.json"',
            $response->getHeaderLine('Content-Disposition')
        );

    }

    function testContentDispositionBadChars() {

        $this->caldavBackend->createCalendar(
            'principals/admin',
            'UUID-b_ad"(ch)ars',
            [
                '{DAV:}displayname'                         => 'Test bad characters',
                '{http://apple.com/ns/ical/}calendar-color' => '#AA0000FF',
            ]
        );

        $request = (new ServerRequest(
            'GET',
            '/calendars/admin/UUID-b_ad"(ch)ars?export',
            ['Accept' => 'application/calendar+json']
        ))->withQueryParams(['export' => '']);

        $response = $this->request($request, 200);
        $this->assertEquals('application/calendar+json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="UUID-b_adchars-' . date('Y-m-d') . '.json"',
            $response->getHeaderLine('Content-Disposition')
        );

    }

}
