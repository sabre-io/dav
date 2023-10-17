<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\DAV;

class PluginPropertiesWithSharedCalendarTest extends \Sabre\DAVServerTest
{
    protected $setupCalDAV = true;
    protected $setupCalDAVScheduling = true;
    protected $setupCalDAVSharing = true;

    public function setup(): void
    {
        parent::setUp();
        $this->caldavBackend->createCalendar(
            'principals/user1',
            'shared',
            [
                'share-access' => DAV\Sharing\Plugin::ACCESS_READWRITE,
            ]
        );
        $this->caldavBackend->createCalendar(
            'principals/user1',
            'default',
            [
            ]
        );
    }

    public function testPrincipalProperties()
    {
        $props = $this->server->getPropertiesForPath('/principals/user1', [
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL',
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-type',
            '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(200, $props[0]);

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL'];
        self::assertTrue($prop instanceof DAV\Xml\Property\Href);
        self::assertEquals('calendars/user1/outbox/', $prop->getHref());

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL'];
        self::assertTrue($prop instanceof DAV\Xml\Property\Href);
        self::assertEquals('calendars/user1/inbox/', $prop->getHref());

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-address-set', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'];
        self::assertTrue($prop instanceof DAV\Xml\Property\Href);
        self::assertEquals(['mailto:user1.sabredav@sabredav.org', '/principals/user1/'], $prop->getHrefs());

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-type', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-type'];
        self::assertEquals('INDIVIDUAL', $prop);

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'];
        self::assertEquals('calendars/user1/default/', $prop->getHref());
    }
}
