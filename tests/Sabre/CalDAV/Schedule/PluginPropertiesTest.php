<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\DAV;

class PluginPropertiesTest extends \Sabre\DAVServerTest
{
    protected $setupCalDAV = true;
    protected $setupCalDAVScheduling = true;
    protected $setupPropertyStorage = true;

    public function setup(): void
    {
        parent::setUp();
        $this->caldavBackend->createCalendar(
            'principals/user1',
            'default',
            [
            ]
        );
        $this->principalBackend->addPrincipal([
            'uri' => 'principals/user1/calendar-proxy-read',
        ]);
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

    public function testPrincipalPropertiesBadPrincipal()
    {
        $props = $this->server->getPropertiesForPath('principals/user1/calendar-proxy-read', [
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL',
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-type',
            '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(200, $props[0]);
        self::assertArrayHasKey(404, $props[0]);

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL', $props[0][404]);
        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL', $props[0][404]);

        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'];
        self::assertTrue($prop instanceof DAV\Xml\Property\Href);
        self::assertEquals(['/principals/user1/calendar-proxy-read/'], $prop->getHrefs());

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-type', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-type'];
        self::assertEquals('INDIVIDUAL', $prop);

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL', $props[0][404]);
    }

    public function testNoDefaultCalendar()
    {
        foreach ($this->caldavBackend->getCalendarsForUser('principals/user1') as $calendar) {
            $this->caldavBackend->deleteCalendar($calendar['id']);
        }
        $props = $this->server->getPropertiesForPath('/principals/user1', [
            '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
        ]);

        self::assertArrayHasKey(0, $props);
        self::assertArrayHasKey(404, $props[0]);

        self::assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL', $props[0][404]);
    }

    /**
     * There are two properties for availability. The server should
     * automatically map the old property to the standard property.
     */
    public function testAvailabilityMapping()
    {
        $path = 'calendars/user1/inbox';
        $oldProp = '{http://calendarserver.org/ns/}calendar-availability';
        $newProp = '{urn:ietf:params:xml:ns:caldav}calendar-availability';
        $value1 = 'first value';
        $value2 = 'second value';

        // Storing with the old name
        $this->server->updateProperties($path, [
            $oldProp => $value1,
        ]);

        // Retrieving with the new name
        self::assertEquals(
            [$newProp => $value1],
            $this->server->getProperties($path, [$newProp])
        );

        // Storing with the new name
        $this->server->updateProperties($path, [
            $newProp => $value2,
        ]);

        // Retrieving with the old name
        self::assertEquals(
            [$oldProp => $value2],
            $this->server->getProperties($path, [$oldProp])
        );
    }
}
