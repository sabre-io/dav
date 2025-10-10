<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;
use Sabre\CalDAV\Xml\Notification\SystemStatus;
use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;
use Sabre\HTTP\Request;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sabre\DAV\Server
     */
    protected $server;
    /**
     * @var Sabre\CalDAV\Plugin
     */
    protected $plugin;
    protected $response;
    /**
     * @var Sabre\CalDAV\Backend\PDO
     */
    protected $caldavBackend;

    public function setup(): void
    {
        $this->caldavBackend = new CalDAV\Backend\MockSharing();
        $principalBackend = new DAVACL\PrincipalBackend\Mock();
        $calendars = new CalDAV\CalendarRoot($principalBackend, $this->caldavBackend);
        $principals = new CalDAV\Principal\Collection($principalBackend);

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

        // CalDAV is also required.
        $this->server->addPlugin(new CalDAV\Plugin());
        // Adding Auth plugin, and ensuring that we are logged in.
        $authBackend = new DAV\Auth\Backend\Mock();
        $authPlugin = new DAV\Auth\Plugin($authBackend);
        $this->server->addPlugin($authPlugin);

        // This forces a login
        $authPlugin->beforeMethod(new Request('GET', '/'), new HTTP\Response());

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    public function testSimple()
    {
        self::assertEquals([], $this->plugin->getFeatures());
        self::assertEquals('notifications', $this->plugin->getPluginName());
        self::assertEquals(
            'notifications',
            $this->plugin->getPluginInfo()['name']
        );
    }

    public function testPrincipalProperties()
    {
        $httpRequest = new Request('GET', '/', ['Host' => 'sabredav.org']);
        $this->server->httpRequest = $httpRequest;

        $props = $this->server->getPropertiesForPath('principals/admin', [
            '{'.Plugin::NS_CALENDARSERVER.'}notification-URL',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(200, $props[0]);

        self::assertArrayHasKey('{'.Plugin::NS_CALENDARSERVER.'}notification-URL', $props[0][200]);
        $prop = $props[0][200]['{'.Plugin::NS_CALENDARSERVER.'}notification-URL'];
        self::assertTrue($prop instanceof DAV\Xml\Property\Href);
        self::assertEquals('calendars/admin/notifications/', $prop->getHref());
    }

    public function testNotificationProperties()
    {
        $notification = new Node(
            $this->caldavBackend,
            'principals/user1',
            new SystemStatus('foo', '"1"')
        );
        $propFind = new DAV\PropFind('calendars/user1/notifications', [
            '{'.Plugin::NS_CALENDARSERVER.'}notificationtype',
        ]);

        $this->plugin->propFind($propFind, $notification);

        self::assertEquals(
            $notification->getNotificationType(),
            $propFind->get('{'.Plugin::NS_CALENDARSERVER.'}notificationtype')
        );
    }

    public function testNotificationGet()
    {
        $notification = new Node(
            $this->caldavBackend,
            'principals/user1',
            new SystemStatus('foo', '"1"')
        );

        $server = new DAV\Server([$notification]);
        $caldav = new Plugin();

        $server->httpRequest = new Request('GET', '/foo.xml');
        $httpResponse = new HTTP\ResponseMock();
        $server->httpResponse = $httpResponse;

        $server->addPlugin($caldav);

        $caldav->httpGet($server->httpRequest, $server->httpResponse);

        self::assertEquals(200, $httpResponse->status);
        self::assertEquals([
            'Content-Type' => ['application/xml'],
            'ETag' => ['"1"'],
        ], $httpResponse->getHeaders());

        $expected =
'<?xml version="1.0" encoding="UTF-8"?>
<cs:notification xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cs="http://calendarserver.org/ns/">
 <cs:systemstatus type="high"/>
</cs:notification>
';

        self::assertXmlStringEqualsXmlString($expected, $httpResponse->getBodyAsString());
    }

    public function testGETPassthrough()
    {
        $server = new DAV\Server();
        $caldav = new Plugin();

        $httpResponse = new HTTP\ResponseMock();
        $server->httpResponse = $httpResponse;

        $server->addPlugin($caldav);

        self::assertNull($caldav->httpGet(new Request('GET', '/foozz'), $server->httpResponse));
    }
}
