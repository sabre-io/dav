<?php declare (strict_types=1);

namespace Sabre\CalDAV;

use GuzzleHttp\Psr7\ServerRequest;
use Sabre\DAV;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\HTTP\Response;

class SharingPluginTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupCalDAVSharing = true;
    protected $setupACL = true;
    protected $autoLogin = 'user1';

    function setUp() {

        $this->caldavCalendars = [
            [
                'principaluri' => 'principals/user1',
                'id'           => 1,
                'uri'          => 'cal1',
            ],
            [
                'principaluri' => 'principals/user1',
                'id'           => 2,
                'uri'          => 'cal2',
                'share-access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE,
            ],
            [
                'principaluri' => 'principals/user1',
                'id'           => 3,
                'uri'          => 'cal3',
            ],
        ];

        parent::setUp();

        // Making the logged in user an admin, for full access:
        $this->aclPlugin->adminPrincipals[] = 'principals/user2';

    }

    function testSimple() {

        $this->assertInstanceOf('Sabre\\CalDAV\\SharingPlugin', $this->server->getPlugin('caldav-sharing'));
        $this->assertEquals(
            'caldav-sharing',
            $this->caldavSharingPlugin->getPluginInfo()['name']
        );

    }

    /**
     * @expectedException \LogicException
     */
    function testSetupWithoutCoreSharingPlugin() {

        $server = new DAV\Server(null, null, null, function(){});
        $server->addPlugin(
            new SharingPlugin()
        );

    }

    function testGetFeatures() {

        $this->assertEquals(['calendarserver-sharing'], $this->caldavSharingPlugin->getFeatures());

    }

    function testBeforeGetShareableCalendar() {

        $request = new ServerRequest('GET', '/');
        // Forcing the server to authenticate:
        $this->authPlugin->beforeMethod(new DAV\Psr7RequestWrapper($request), new Response());
        $props = $this->server->getProperties('calendars/user1/cal1', [
            '{' . Plugin::NS_CALENDARSERVER . '}invite',
            '{' . Plugin::NS_CALENDARSERVER . '}allowed-sharing-modes',
        ]);

        $this->assertInstanceOf('Sabre\\CalDAV\\Xml\\Property\\Invite', $props['{' . Plugin::NS_CALENDARSERVER . '}invite']);
        $this->assertInstanceOf('Sabre\\CalDAV\\Xml\\Property\\AllowedSharingModes', $props['{' . Plugin::NS_CALENDARSERVER . '}allowed-sharing-modes']);

    }

    function testBeforeGetSharedCalendar() {

        $props = $this->server->getProperties('calendars/user1/cal2', [
            '{' . Plugin::NS_CALENDARSERVER . '}shared-url',
            '{' . Plugin::NS_CALENDARSERVER . '}invite',
        ]);

        $this->assertInstanceOf('Sabre\\CalDAV\\Xml\\Property\\Invite', $props['{' . Plugin::NS_CALENDARSERVER . '}invite']);
        //$this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $props['{' . Plugin::NS_CALENDARSERVER . '}shared-url']);

    }

    function testUpdateResourceType() {

        $this->caldavBackend->updateInvites(1,
            [
                new Sharee([
                    'href' => 'mailto:joe@example.org',
                ])
            ]
        );
        $result = $this->server->updateProperties('calendars/user1/cal1', [
            '{DAV:}resourcetype' => new DAV\Xml\Property\ResourceType(['{DAV:}collection'])
        ]);

        $this->assertEquals([
            '{DAV:}resourcetype' => 200
        ], $result);

        $this->assertEquals(0, count($this->caldavBackend->getInvites(1)));

    }

    function testUpdatePropertiesPassThru() {

        $result = $this->server->updateProperties('calendars/user1/cal3', [
            '{DAV:}foo' => 'bar',
        ]);

        $this->assertEquals([
            '{DAV:}foo' => 200,
        ], $result);

    }

    function testUnknownMethodNoPOST() {

        $request = new ServerRequest('PATCH','/');

        $response = $this->request($request);

        $this->assertEquals(501, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testUnknownMethodNoXML() {

        $request = new ServerRequest('POST','/',['Content-Type'   => 'text/plain']);

        $response = $this->request($request);

        $this->assertEquals(501, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testUnknownMethodNoNode() {

        $request = new ServerRequest('POST', '/foo', ['Content-Type'   => 'text/xml']);

        $response = $this->request($request);

        $this->assertEquals(501, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testShareRequest() {



        $xml = <<<RRR
<?xml version="1.0"?>
<cs:share xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <cs:set>
        <d:href>mailto:joe@example.org</d:href>
        <cs:common-name>Joe Shmoe</cs:common-name>
        <cs:read-write />
    </cs:set>
    <cs:remove>
        <d:href>mailto:nancy@example.org</d:href>
    </cs:remove>
</cs:share>
RRR;
        $request = new ServerRequest('POST', '/calendars/user1/cal1', ['Content-Type' => 'text/xml'], $xml);
        $response = $this->request($request, 200);

        $this->assertEquals(
            [
                new Sharee([
                    'href'       => 'mailto:joe@example.org',
                    'properties' => [
                        '{DAV:}displayname' => 'Joe Shmoe',
                    ],
                    'access'       => \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE,
                    'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE,
                    'comment'      => '',
                ]),
            ],
            $this->caldavBackend->getInvites(1)
        );

        // Wiping out tree cache
        $this->server->tree->markDirty('');

        // Verifying that the calendar is now marked shared.
        $props = $this->server->getProperties('calendars/user1/cal1', ['{DAV:}resourcetype']);
        $this->assertTrue(
            $props['{DAV:}resourcetype']->is('{http://calendarserver.org/ns/}shared-owner')
        );

    }

    function testShareRequestNoShareableCalendar() {


        $xml = '<?xml version="1.0"?>
<cs:share xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
    <cs:set>
        <d:href>mailto:joe@example.org</d:href>
        <cs:common-name>Joe Shmoe</cs:common-name>
        <cs:read-write />
    </cs:set>
    <cs:remove>
        <d:href>mailto:nancy@example.org</d:href>
    </cs:remove>
</cs:share>
';

        $request = new ServerRequest(
            'POST',
            '/calendars/user1/cal2',
            ['Content-Type' => 'text/xml'],
            $xml
        );

        $this->request($request, 403);

    }

    function testInviteReply() {



        $xml = '<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
    <cs:hosturl><d:href>/principals/owner</d:href></cs:hosturl>
    <cs:invite-accepted />
</cs:invite-reply>
';

        $request = new ServerRequest('POST', '/calendars/user1', [
            'Content-Type' => 'text/xml',

        ], $xml);

        $response = $this->request($request);
        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testInviteBadXML() {
        $xml = '<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
</cs:invite-reply>
';

        $request = new ServerRequest('POST', '/calendars/user1', [
            'Content-Type'   => 'text/xml',
        ], $xml);

        $response = $this->request($request);
        $this->assertEquals(400, $response->getStatusCode(), $response->getBody()->getContents());

    }

    function testInviteWrongUrl() {

        $xml = '<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:">
    <cs:hosturl><d:href>/principals/owner</d:href></cs:hosturl>
</cs:invite-reply>
';
        $request = new ServerRequest(
            'POST',
            '/calendars/user1/cal1',
            ['Content-Type' => 'text/xml'],
            $xml
        );

        $response = $this->request($request, 501);

        // If the plugin did not handle this request, it must ensure that the
        // body is still accessible by other plugins.
        $this->assertEquals($xml, $this->server->httpRequest->getBodyAsString());
    }

    function testPublish() {



        $xml = '<?xml version="1.0"?>
<cs:publish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';
        $request = new ServerRequest('POST', '/calendars/user1/cal1', ['Content-Type' => 'text/xml'], $xml);
        $response = $this->request($request);
        $this->assertEquals(202, $response->getStatusCode(), $response->getBody()->getContents());

    }


    function testUnpublish() {



        $xml = '<?xml version="1.0"?>
<cs:unpublish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';
        $request = new ServerRequest(
            'POST',
            '/calendars/user1/cal1',
            ['Content-Type' => 'text/xml'],
            $xml
        );
        $this->request($request, 200);
    }

    function testPublishWrongUrl() {
        $xml = '<?xml version="1.0"?>
<cs:publish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';

        $request = new ServerRequest(
            'POST',
            '/calendars/user1',
            ['Content-Type' => 'text/xml'],
            $xml
        );
        $this->request($request, 501);

    }

    function testUnpublishWrongUrl() {
        $xml = '<?xml version="1.0"?>
<cs:unpublish-calendar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />
';

        $request = new ServerRequest(
            'POST',
            '/calendars/user1',
            ['Content-Type' => 'text/xml'],
            $xml
        );

        $this->request($request, 501);

    }

    function testUnknownXmlDoc() {
        $xml = '<?xml version="1.0"?>
<cs:foo-bar xmlns:cs="' . Plugin::NS_CALENDARSERVER . '" xmlns:d="DAV:" />';
        $request = new ServerRequest(
            'POST',
            '/calendars/user1/cal2',
            ['Content-Type' => 'text/xml'],
            $xml
        );

        $this->request($request, 501);
    }
}
