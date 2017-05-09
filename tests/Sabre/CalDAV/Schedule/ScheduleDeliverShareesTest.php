<?php declare (strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\HTTP\Request;
use Sabre\Uri;
use Sabre\VObject;

class ScheduleDeliverShareesTest extends \Sabre\DAVServerTest {

    use VObject\PHPUnitAssertions;

    public $setupCalDAV = true;
    public $setupCalDAVScheduling = true;
    public $setupACL = true;
    public $setupCalDAVSharing = true;
    public $setupMockAll = true;

    public $caldavCalendars = [
        [
            'principaluri' => 'principals/user1',
            'uri'          => 'cal',
        ],
        [
            'principaluri' => 'principals/user2',
            'uri'          => 'cal',
        ]
    ];

    function customSetUp() {


        parent::setUp();
        $this->principalBackend->addPrincipal(
            [
                'uri'                                   => 'principals/user3',
                '{DAV:}displayname'                     => 'User 3',
                '{http://sabredav.org/ns}email-address' => 'user3.sabredav@sabredav.org',
            ]
        );
        $this->caldavBackend->createCalendar('principals/user3', 'cal', []);
        $this->principalBackend->addPrincipal(
            [
                'uri'                                   => 'principals/user4',
                '{DAV:}displayname'                     => 'User 4',
                '{http://sabredav.org/ns}email-address' => 'user4.sabredav@sabredav.org',
            ]
        );
        $this->caldavBackend->createCalendar('principals/user4', 'cal', []);
        $this->principalBackend->addPrincipal(
            [
                'uri'                                   => 'principals/user5',
                '{DAV:}displayname'                     => 'User 5',
                '{http://sabredav.org/ns}email-address' => 'user5.sabredav@sabredav.org',
            ]
        );
        $this->caldavBackend->createCalendar('principals/user5', 'cal', []);
        list($parent, $base) = Uri\split($this->calendarObjectUri);
        $parentNode = $this->server->tree->getNodeForPath($parent);
        $this->getShareesList($parentNode);
    }

    function testReply() {

        $this->calendarObjectUri = '/calendars/user1/cal/object.ics';
        $this->autoLogin = 'user1';
        $this->customSetUp();

        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
DTSTART:20140811T230000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
ATTENDEE:mailto:user1.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
DTSTART:20140811T230000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user1.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->putPath('calendars/user2/cal/foo.ics', $oldObject);
        $this->putPath('calendars/user3/cal/foo.ics', $oldObject);
        $this->putPath('calendars/user4/cal/foo.ics', $oldObject);
        $this->putPath('calendars/user5/cal/foo.ics', $oldObject);

        $this->deliver($oldObject, $newObject);
        $this->assertItemsInInbox('user5', 0);
        $this->assertItemsInInbox('user4', 0);
        $this->assertItemsInInbox('user3', 0);
        $this->assertItemsInInbox('user2', 1);
        $this->assertItemsInInbox('user1', 0);

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20140811T230000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED;SCHEDULE-STATUS=1.2:mailto:user2.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user1.sabredav@sabredav.org
DTSTAMP:**ANY**
END:VEVENT
END:VCALENDAR
ICS;

        $this->assertVObjectEqualsVObject(
            $expected,
            $newObject
        );

    }

    function testMoveReadWriteUser() {

        $this->calendarObjectUri = '/calendars/user3/cal/object.ics';
        $this->autoLogin = 'user3';
        $this->customSetUp();

        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
DTSTART:20140811T230000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
ATTENDEE:mailto:user1.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
DTSTART:20150811T230000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
ATTENDEE:mailto:user1.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->putPath('calendars/user2/cal/foo.ics', $oldObject);
        $this->putPath('calendars/user3/cal/foo.ics', $oldObject);
        $this->putPath('calendars/user4/cal/foo.ics', $oldObject);
        $this->putPath('calendars/user5/cal/foo.ics', $oldObject);

        $this->deliver($oldObject, $newObject);
        $this->assertItemsInInbox('user5', 0);
        $this->assertItemsInInbox('user4', 0);
        $this->assertItemsInInbox('user3', 0);
        $this->assertItemsInInbox('user2', 1);
        $this->assertItemsInInbox('user1', 1);

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20150811T230000Z
ORGANIZER;SCHEDULE-STATUS=1.2:mailto:user1.sabredav@sabredav.org
ATTENDEE;SCHEDULE-STATUS=1.2:mailto:user2.sabredav@sabredav.org
ATTENDEE:mailto:user1.sabredav@sabredav.org
DTSTAMP:**ANY**
END:VEVENT
END:VCALENDAR
ICS;

        $this->assertVObjectEqualsVObject(
            $expected,
            $newObject
        );

    }

    function testNewInvite() {
        
        $this->calendarObjectUri = '/calendars/user3/cal/object.ics';
        $this->autoLogin = 'user3';
        $this->customSetUp();

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
DTSTART:20140811T230000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver(null, $newObject);
        $this->assertItemsInInbox('user5', 0);
        $this->assertItemsInInbox('user4', 0);
        $this->assertItemsInInbox('user3', 0);
        $this->assertItemsInInbox('user2', 1);
        $this->assertItemsInInbox('user1', 0);

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART:20140811T230000Z
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE;SCHEDULE-STATUS=1.2:mailto:user2.sabredav@sabredav.org
DTSTAMP:**ANY**
END:VEVENT
END:VCALENDAR
ICS;

        $this->assertVObjectEqualsVObject(
            $expected,
            $newObject
        );

    }

    protected $calendarObjectUri;

    function getShareesList($calendar) {
        $newSharees = [
            new \Sabre\DAV\Xml\Element\Sharee(),
            new \Sabre\DAV\Xml\Element\Sharee(),
            new \Sabre\DAV\Xml\Element\Sharee()
        ];

        $newSharees[0]->principal = '/principals/user3';
        $newSharees[0]->href = 'mailto:user3.sabredav@sabredav.org';
        $newSharees[0]->access = \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE;
        $newSharees[0]->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED;

        $newSharees[1]->principal = '/principals/user4';
        $newSharees[1]->href = 'mailto:user4.sabredav@sabredav.org';
        $newSharees[1]->access = \Sabre\DAV\Sharing\Plugin::ACCESS_READ;
        $newSharees[1]->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED;

        $newSharees[2]->principal = '/principals/user5';
        $newSharees[2]->href = 'mailto:user5.sabredav@sabredav.org';
        $newSharees[2]->access = \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE;
        $newSharees[2]->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_DECLINED;

        $calendar->updateInvites($newSharees);
    }

    function deliver($oldObject, &$newObject, $disableScheduling = false, $method = 'PUT') {

        $this->server->httpRequest->setMethod($method);
        $this->server->httpRequest->setUrl($this->calendarObjectUri);
        if ($disableScheduling) {
            $this->server->httpRequest->setHeader('Schedule-Reply', 'F');
        }

        if ($oldObject && $newObject) {
            // update
            $this->putPath($this->calendarObjectUri, $oldObject);

            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $newObject);
            rewind($stream);
            $modified = false;

            $this->server->emit('beforeWriteContent', [
                $this->calendarObjectUri,
                $this->server->tree->getNodeForPath($this->calendarObjectUri),
                &$stream,
                &$modified
            ]);
            if ($modified) {
                $newObject = $stream;
            }

        } elseif ($oldObject && !$newObject) {
            // delete
            $this->putPath($this->calendarObjectUri, $oldObject);

            $this->caldavSchedulePlugin->beforeUnbind(
                $this->calendarObjectUri
            );
        } else {

            // create
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $newObject);
            rewind($stream);
            $modified = false;
            $this->server->emit('beforeCreateFile', [
                $this->calendarObjectUri,
                &$stream,
                $this->server->tree->getNodeForPath(dirname($this->calendarObjectUri)),
                &$modified
            ]);

            if ($modified) {
                $newObject = $stream;
            }
        }

    }


    /**
     * Creates or updates a node at the specified path.
     *
     * This circumvents sabredav's internal server apis, so all events and
     * access control is skipped.
     *
     * @param string $path
     * @param string $data
     * @return void
     */
    function putPath($path, $data) {

        list($parent, $base) = Uri\split($path);
        $parentNode = $this->server->tree->getNodeForPath($parent);
        $parentNode->createFile($base, $data);
    }

    function assertItemsInInbox($user, $count) {

        $inboxNode = $this->server->tree->getNodeForPath('calendars/' . $user . '/inbox');
        $this->assertEquals($count, count($inboxNode->getChildren()));

    }

}
