<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\Uri;
use Sabre\VObject;

/**
 * When the organizer moves an event to another time, attendees have to
 * accept or decline it again (RFC 6638, Section 3.2.8).
 *
 * See https://github.com/sabre-io/dav/issues/1282
 */
class ScheduleRescheduleTest extends \Sabre\AbstractDAVServerTestCase
{
    use VObject\PHPUnitAssertions;

    public $setupCalDAV = true;
    public $setupCalDAVScheduling = true;
    public $setupACL = true;
    public $autoLogin = 'user1';

    public $caldavCalendars = [
        [
            'principaluri' => 'principals/user1',
            'uri' => 'cal',
        ],
        [
            'principaluri' => 'principals/user2',
            'uri' => 'cal',
        ],
    ];

    protected $calendarObjectUri = '/calendars/user1/cal/object.ics';

    public function testRescheduleResetsPartStat()
    {
        $oldObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20261005T100000Z
DTEND:20261005T110000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $newObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20261005T120000Z
DTEND:20261005T130000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject);

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20261005T120000Z
DTEND:20261005T130000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=NEEDS-ACTION;SCHEDULE-STATUS=1.2:mailto:user2.sabredav@sabredav.org
DTSTAMP:**ANY**
END:VEVENT
END:VCALENDAR
ICS;

        self::assertVObjectEqualsVObject($expected, $newObject);
        self::assertPartStat('NEEDS-ACTION', $this->getInboxMessage('user2'));
        self::assertPartStat('NEEDS-ACTION', $this->getCalendarObject('user2'));
    }

    public function testMovedOccurrenceResetsOnlyThatOccurrence()
    {
        $oldObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20261005T100000Z
DTEND:20261005T110000Z
RRULE:FREQ=WEEKLY;COUNT=5
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $newObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20261005T100000Z
DTEND:20261005T110000Z
RRULE:FREQ=WEEKLY;COUNT=5
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
BEGIN:VEVENT
UID:foo
RECURRENCE-ID:20261012T100000Z
DTSTART:20261012T140000Z
DTEND:20261012T150000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject);

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20261005T100000Z
DTEND:20261005T110000Z
RRULE:FREQ=WEEKLY;COUNT=5
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED;SCHEDULE-STATUS=1.2:mailto:user2.sabredav@sabredav.org
DTSTAMP:**ANY**
END:VEVENT
BEGIN:VEVENT
UID:foo
RECURRENCE-ID:20261012T100000Z
DTSTART:20261012T140000Z
DTEND:20261012T150000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=NEEDS-ACTION:mailto:user2.sabredav@sabredav.org
DTSTAMP:**ANY**
END:VEVENT
END:VCALENDAR
ICS;

        self::assertVObjectEqualsVObject($expected, $newObject);
        foreach ([$this->getInboxMessage('user2'), $this->getCalendarObject('user2')] as $copy) {
            self::assertPartStat('ACCEPTED', $copy);
            self::assertPartStat('NEEDS-ACTION', $copy, 'mailto:user2.sabredav@sabredav.org', '20261012T100000Z');
        }
    }

    public function testUnmovedOccurrenceAcrossSummerTimeKeepsPartStat()
    {
        // DURATION:P1D is 25 hours long on the day summer time ends.
        $oldObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART;TZID=Europe/Berlin:20261020T100000
DURATION:P1D
RRULE:FREQ=DAILY;COUNT=10
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $newObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART;TZID=Europe/Berlin:20261020T100000
DURATION:P1D
RRULE:FREQ=DAILY;COUNT=10
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
BEGIN:VEVENT
UID:foo
RECURRENCE-ID;TZID=Europe/Berlin:20261024T100000
DTSTART;TZID=Europe/Berlin:20261024T100000
DURATION:P1D
LOCATION:Room B
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject);

        self::assertPartStat('ACCEPTED', $this->readStream($newObject), 'mailto:user2.sabredav@sabredav.org', '20261024T100000');
    }

    public function testTodoIsNotTouched()
    {
        // Only events are scheduled, so tasks keep their PARTSTAT.
        $oldObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VTODO
UID:foo
DUE:20261005T100000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VTODO
END:VCALENDAR
ICS;

        $newObject = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VTODO
UID:foo
DUE:20261006T100000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
END:VTODO
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject);

        $vcal = VObject\Reader::read($this->readStream($newObject));
        self::assertSame('ACCEPTED', (string) $vcal->VTODO->ATTENDEE['PARTSTAT']);
    }

    /**
     * @dataProvider timeChanges
     */
    public function testTimeChanges($old, $new, $expected)
    {
        $result = $this->reschedule($this->event(...$old), $this->event(...$new));

        self::assertPartStat($expected, $result['organizer']);
        self::assertPartStat($expected, $result['inbox']);
    }

    public function timeChanges()
    {
        $start = 'DTSTART:20261005T100000Z';
        $end = 'DTEND:20261005T110000Z';

        return [
            'end changed' => [[$start, $end], [$start, 'DTEND:20261005T113000Z'], 'NEEDS-ACTION'],
            'duration changed' => [[$start, 'DURATION:PT1H'], [$start, 'DURATION:PT2H'], 'NEEDS-ACTION'],
            'same duration written differently' => [[$start, 'DURATION:PT1H'], [$start, 'DURATION:PT60M'], 'ACCEPTED'],
            'same time in other timezone' => [
                ['DTSTART;TZID=Europe/Berlin:20261005T100000', 'DTEND;TZID=Europe/Berlin:20261005T110000'],
                ['DTSTART;TZID=Europe/London:20261005T090000', 'DTEND;TZID=Europe/London:20261005T100000'],
                'ACCEPTED',
            ],
            'other timezone with other time' => [
                ['DTSTART;TZID=Europe/Berlin:20261005T100000', 'DTEND;TZID=Europe/Berlin:20261005T110000'],
                ['DTSTART;TZID=Europe/London:20261005T100000', 'DTEND;TZID=Europe/London:20261005T110000'],
                'NEEDS-ACTION',
            ],
            'all-day end written out' => [['DTSTART;VALUE=DATE:20261005'], ['DTSTART;VALUE=DATE:20261005', 'DTEND;VALUE=DATE:20261006'], 'ACCEPTED'],
            'timed end written out' => [[$start], [$start, 'DTEND:20261005T100000Z'], 'ACCEPTED'],
            'summary changed' => [[$start, $end, 'SUMMARY:Old'], [$start, $end, 'SUMMARY:New'], 'ACCEPTED'],
            'location changed' => [[$start, $end, 'LOCATION:Room A'], [$start, $end, 'LOCATION:Room B'], 'ACCEPTED'],
            'status changed' => [[$start, $end, 'STATUS:CONFIRMED'], [$start, $end, 'STATUS:TENTATIVE'], 'ACCEPTED'],
        ];
    }

    public function testOrganizerAsAttendeeKeepsPartStat()
    {
        $organizer = 'ATTENDEE;PARTSTAT=ACCEPTED:mailto:user1.sabredav@sabredav.org';
        $result = $this->reschedule(
            $this->event('DTSTART:20261005T100000Z', $organizer),
            $this->event('DTSTART:20261005T120000Z', $organizer)
        );

        self::assertPartStat('ACCEPTED', $result['organizer'], 'mailto:user1.sabredav@sabredav.org');
        self::assertPartStat('NEEDS-ACTION', $result['organizer']);
    }

    /**
     * @dataProvider scheduleAgents
     */
    public function testScheduleAgent($agent, $expected)
    {
        $other = 'ATTENDEE;SCHEDULE-AGENT='.$agent.';PARTSTAT=ACCEPTED:mailto:other@example.org';
        $result = $this->reschedule(
            $this->event('DTSTART:20261005T100000Z', $other),
            $this->event('DTSTART:20261005T120000Z', $other)
        );

        self::assertPartStat($expected, $result['organizer'], 'mailto:other@example.org');
    }

    public function scheduleAgents()
    {
        return [
            'server' => ['SERVER', 'NEEDS-ACTION'],
            'server in lower case' => ['server', 'NEEDS-ACTION'],
            'client' => ['CLIENT', 'ACCEPTED'],
            'client in lower case' => ['client', 'ACCEPTED'],
            'none' => ['NONE', 'ACCEPTED'],
        ];
    }

    /**
     * @dataProvider occurrenceChanges
     */
    public function testOccurrenceChanges($master, $oldOccurrence, $newOccurrence, $expected)
    {
        $old = $this->event(...$master);
        if ($oldOccurrence) {
            $old .= $this->event(...$oldOccurrence);
        }
        $new = $this->event(...$master).$this->event(...$newOccurrence);

        $result = $this->reschedule($old, $new);

        self::assertPartStat('ACCEPTED', $result['organizer']);
        self::assertPartStat($expected, $result['organizer'], 'mailto:user2.sabredav@sabredav.org', '20261012T100000Z');
    }

    public function occurrenceChanges()
    {
        $master = ['DTSTART:20261005T100000Z', 'DTEND:20261005T110000Z', 'RRULE:FREQ=WEEKLY;COUNT=5'];
        $id = 'RECURRENCE-ID:20261012T100000Z';
        $moved = [$id, 'DTSTART:20261012T140000Z', 'DTEND:20261012T150000Z'];

        return [
            'new, longer' => [$master, null, [$id, 'DTSTART:20261012T100000Z', 'DTEND:20261012T120000Z'], 'NEEDS-ACTION'],
            'new, starts later' => [$master, null, [$id, 'DTSTART:20261012T103000Z', 'DTEND:20261012T110000Z'], 'NEEDS-ACTION'],
            'new, same time' => [$master, null, [$id, 'DTSTART:20261012T100000Z', 'DTEND:20261012T110000Z', 'LOCATION:Room B'], 'ACCEPTED'],
            'new, same time, main event without end' => [
                ['DTSTART:20261005T100000Z', 'RRULE:FREQ=WEEKLY;COUNT=5'],
                null,
                [$id, 'DTSTART:20261012T100000Z', 'LOCATION:Room B'],
                'ACCEPTED',
            ],
            'existing, moved again' => [$master, $moved, [$id, 'DTSTART:20261012T160000Z', 'DTEND:20261012T170000Z'], 'NEEDS-ACTION'],
            'existing, unchanged' => [$master, $moved, $moved, 'ACCEPTED'],
        ];
    }

    /**
     * @dataProvider recurrenceChanges
     */
    public function testRecurrenceChanges($old, $new, $expected)
    {
        $start = ['DTSTART:20261005T100000Z', 'DTEND:20261005T110000Z'];
        $result = $this->reschedule($this->event(...$start, ...$old), $this->event(...$start, ...$new));

        self::assertPartStat($expected, $result['organizer']);
        self::assertPartStat($expected, $result['inbox']);
    }

    public function recurrenceChanges()
    {
        $weekly = 'RRULE:FREQ=WEEKLY;COUNT=10';
        $endless = 'RRULE:FREQ=WEEKLY';

        return [
            'rule added' => [[], [$weekly], 'NEEDS-ACTION'],
            'rule removed' => [[$weekly], [], 'ACCEPTED'],
            'rule unchanged, summary changed' => [[$weekly, 'SUMMARY:Old'], [$weekly, 'SUMMARY:New'], 'ACCEPTED'],
            'endless rule unchanged, summary changed' => [[$endless, 'SUMMARY:Old'], [$endless, 'SUMMARY:New'], 'ACCEPTED'],
            'frequency changed' => [['RRULE:FREQ=DAILY'], [$endless], 'NEEDS-ACTION'],
            'interval changed' => [['RRULE:FREQ=WEEKLY;INTERVAL=2'], ['RRULE:FREQ=WEEKLY;INTERVAL=4'], 'NEEDS-ACTION'],
            'weekday changed' => [[$weekly], ['RRULE:FREQ=WEEKLY;COUNT=10;BYDAY=TU'], 'NEEDS-ACTION'],
            'weekdays in other order' => [['RRULE:FREQ=WEEKLY;BYDAY=MO,WE'], ['RRULE:FREQ=WEEKLY;BYDAY=WE,MO'], 'ACCEPTED'],
            'default interval written out' => [[$weekly], ['RRULE:FREQ=WEEKLY;INTERVAL=1;COUNT=10'], 'ACCEPTED'],
            'fewer occurrences' => [[$weekly], ['RRULE:FREQ=WEEKLY;COUNT=5'], 'ACCEPTED'],
            'more occurrences' => [[$weekly], ['RRULE:FREQ=WEEKLY;COUNT=12'], 'NEEDS-ACTION'],
            'earlier end' => [['RRULE:FREQ=WEEKLY;UNTIL=20261231T000000Z'], ['RRULE:FREQ=WEEKLY;UNTIL=20261101T000000Z'], 'ACCEPTED'],
            'later end' => [['RRULE:FREQ=WEEKLY;UNTIL=20261101T000000Z'], ['RRULE:FREQ=WEEKLY;UNTIL=20261231T000000Z'], 'NEEDS-ACTION'],
            'endless series now ends' => [[$endless], [$weekly], 'ACCEPTED'],
            'series is now endless' => [[$weekly], [$endless], 'NEEDS-ACTION'],
            'count replaced by until' => [[$weekly], ['RRULE:FREQ=WEEKLY;UNTIL=20261101T000000Z'], 'NEEDS-ACTION'],
            'until replaced by count' => [['RRULE:FREQ=WEEKLY;UNTIL=20261231T000000Z'], [$weekly], 'NEEDS-ACTION'],
            'exdate added' => [[$weekly], [$weekly, 'EXDATE:20261012T100000Z'], 'ACCEPTED'],
            'exdate removed' => [[$weekly, 'EXDATE:20261012T100000Z'], [$weekly], 'NEEDS-ACTION'],
            'rdate added' => [[$weekly], [$weekly, 'RDATE:20261008T100000Z'], 'NEEDS-ACTION'],
            'rdate removed' => [[$weekly, 'RDATE:20261008T100000Z'], [$weekly], 'ACCEPTED'],
            'excluded rdate comes back' => [
                [$weekly, 'RDATE:20261008T100000Z', 'EXDATE:20261008T100000Z'],
                [$weekly, 'RDATE:20261008T100000Z'],
                'NEEDS-ACTION',
            ],
            'rdate period unchanged' => [
                [$weekly, 'RDATE;VALUE=PERIOD:20261008T100000Z/20261008T120000Z', 'SUMMARY:Old'],
                [$weekly, 'RDATE;VALUE=PERIOD:20261008T100000Z/20261008T120000Z', 'SUMMARY:New'],
                'ACCEPTED',
            ],
            'rdate written as period' => [
                [$weekly, 'RDATE:20261008T100000Z'],
                [$weekly, 'RDATE;VALUE=PERIOD:20261008T100000Z/20261008T120000Z'],
                'ACCEPTED',
            ],
            'rdate period added' => [
                [$weekly],
                [$weekly, 'RDATE;VALUE=PERIOD:20261008T100000Z/20261008T120000Z'],
                'NEEDS-ACTION',
            ],
            'unreadable rule' => [['RRULE:FREQ=WEEKLY;UNTIL=20261231T000000Z'], ['RRULE:FREQ=WEEKLY;UNTIL=nonsense'], 'NEEDS-ACTION'],
        ];
    }

    public function testAttendeeChangeDoesNotReset()
    {
        // user2 is an attendee and changes its own copy. Only the organizer
        // can reschedule, so the other attendee keeps its PARTSTAT.
        $this->autoLogin('user2');
        $this->calendarObjectUri = '/calendars/user2/cal/object.ics';
        $other = 'ATTENDEE;PARTSTAT=ACCEPTED:mailto:other@example.org';
        $old = $this->wrap($this->event('DTSTART:20261005T100000Z', $other));
        $new = $this->wrap($this->event('DTSTART:20261005T120000Z', $other));

        $this->deliver($old, $new);

        self::assertPartStat('ACCEPTED', $this->readStream($new), 'mailto:other@example.org');
    }

    /**
     * Returns a VEVENT owned by user1 with user2 as an accepted attendee.
     */
    public function event(string ...$properties)
    {
        return implode("\n", array_merge(
            ['BEGIN:VEVENT', 'UID:foo'],
            $properties,
            [
                'ORGANIZER:mailto:user1.sabredav@sabredav.org',
                'ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org',
                'END:VEVENT',
            ]
        ))."\n";
    }

    public function wrap($events)
    {
        return "BEGIN:VCALENDAR\nVERSION:2.0\n".$events."END:VCALENDAR\n";
    }

    /**
     * Saves $old as user1's event, then updates it with $new. Returns the
     * organizer's object, the message in user2's inbox and user2's copy.
     */
    public function reschedule($old, $new)
    {
        $new = $this->wrap($new);
        $this->deliver($this->wrap($old), $new);

        return [
            'organizer' => $this->readStream($new),
            'inbox' => $this->getInboxMessage('user2'),
            'attendee' => $this->getCalendarObject('user2'),
        ];
    }

    /**
     * Saves $oldObject and updates it with $newObject, the same way as
     * ScheduleDeliverTest::deliver().
     */
    public function deliver($oldObject, &$newObject)
    {
        $this->server->httpRequest->setMethod('PUT');
        $this->server->httpRequest->setUrl($this->calendarObjectUri);

        list($parent, $base) = Uri\split($this->calendarObjectUri);
        $this->server->tree->getNodeForPath($parent)->createFile($base, $oldObject);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $newObject);
        rewind($stream);
        $modified = false;

        $this->server->emit('beforeWriteContent', [
            $this->calendarObjectUri,
            $this->server->tree->getNodeForPath($this->calendarObjectUri),
            &$stream,
            &$modified,
        ]);
        if ($modified) {
            $newObject = $stream;
        }
    }

    public function getInboxMessage($user)
    {
        $messages = $this->server->tree->getNodeForPath('calendars/'.$user.'/inbox')->getChildren();

        return $messages ? end($messages)->get() : null;
    }

    public function getCalendarObject($user)
    {
        $objects = $this->server->tree->getNodeForPath('calendars/'.$user.'/cal')->getChildren();

        return $objects ? end($objects)->get() : null;
    }

    public function readStream($data)
    {
        if (is_resource($data)) {
            rewind($data);

            return stream_get_contents($data);
        }

        return $data;
    }

    public function assertPartStat($expected, $ics, $attendee = 'mailto:user2.sabredav@sabredav.org', $recurrenceId = 'master')
    {
        self::assertNotNull($ics, 'The calendar object was not found.');
        $vcal = VObject\Reader::read($ics);
        foreach ($vcal->VEVENT as $vevent) {
            $id = isset($vevent->{'RECURRENCE-ID'}) ? $vevent->{'RECURRENCE-ID'}->getValue() : 'master';
            if ($id !== $recurrenceId) {
                continue;
            }
            foreach ($vevent->ATTENDEE as $property) {
                if ($property->getNormalizedValue() === $attendee) {
                    self::assertSame($expected, (string) $property['PARTSTAT'], "PARTSTAT of $attendee in $recurrenceId:\n$ics");

                    return;
                }
            }
        }
        self::fail("$attendee not found in $recurrenceId:\n$ics");
    }
}
