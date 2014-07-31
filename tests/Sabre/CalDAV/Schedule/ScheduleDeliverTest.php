<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\HTTP\Request;

class ScheduleDeliverTest extends \Sabre\DAVServerTest {

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

    function setUp() {

        $this->calendarObjectUri = '/calendars/user1/cal/object.ics';

        parent::setUp();

    }

    function testNewInvite() {

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver(null, $newObject);
        $this->assertItemsInInbox('user2', 1);

    }

    function testNewOnWrongCollection() {

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->calendarObjectUri = '/calendars/user1/object.ics';
        $this->deliver(null, $newObject);
        $this->assertItemsInInbox('user2', 0);

    }
    function testNewInviteSchedulingDisabled() {

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver(null, $newObject, true);
        $this->assertItemsInInbox('user2', 0);

    }
    function testUpdatedInvite() {

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;
        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject);
        $this->assertItemsInInbox('user2', 1);

    }
    function testUpdatedInviteSchedulingDisabled() {

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;
        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject, true);
        $this->assertItemsInInbox('user2', 0);

    }

    function testUpdatedInviteWrongPath() {

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;
        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->calendarObjectUri = '/calendars/user1/inbox/foo.ics';
        $this->deliver($oldObject, $newObject);
        $this->assertItemsInInbox('user2', 0);

    }

    function testDeletedInvite() {

        $newObject = null;

        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject);
        $this->assertItemsInInbox('user2', 1);

    }

    function testDeletedInviteSchedulingDisabled() {

        $newObject = null;

        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->deliver($oldObject, $newObject, true);
        $this->assertItemsInInbox('user2', 0);

    }

    function testDeletedInviteWrongUrl() {

        $newObject = null;

        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->calendarObjectUri = '/calendars/user1/inbox/foo.ics';
        $this->deliver($oldObject, $newObject);
        $this->assertItemsInInbox('user2', 0);

    }

    function testReply() {

        $oldObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user2.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
ATTENDEE:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user3.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $newObject = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:foo
ORGANIZER:mailto:user2.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user2.sabredav@sabredav.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user3.sabredav@sabredav.org
END:VEVENT
END:VCALENDAR
ICS;

        $this->putPath('calendars/user2/cal/foo.ics', $oldObject);

        $this->deliver($oldObject, $newObject);
        $this->assertItemsInInbox('user2', 1);
        $this->assertItemsInInbox('user1', 0);

    }


    protected $calendarObjectUri;

    function deliver($oldObject, $newObject, $disableScheduling = false) {

        if ($disableScheduling) {
            $this->server->httpRequest->setHeader('Schedule-Reply','F');
        }

        if ($oldObject && $newObject) {
            // update
            $this->putPath($this->calendarObjectUri, $oldObject);

            $stream = fopen('php://memory','r+');
            fwrite($stream, $newObject);
            rewind($stream);
            $modified = false;

            $this->caldavSchedulePlugin->beforeWriteContent(
                $this->calendarObjectUri,
                $this->server->tree->getNodeForPath($this->calendarObjectUri),
                $stream,
                $modified
            );

        } elseif ($oldObject && !$newObject) {
            // delete
            $this->putPath($this->calendarObjectUri, $oldObject);

            $this->caldavSchedulePlugin->beforeUnbind(
                $this->calendarObjectUri
            );
        } else {
            // create
            $stream = fopen('php://memory','r+');
            fwrite($stream, $newObject);
            rewind($stream);
            $modified = false;
            $this->caldavSchedulePlugin->beforeCreateFile(
                $this->calendarObjectUri,
                $stream,
                $this->server->tree->getNodeForPath(dirname($this->calendarObjectUri)),
                $modified
            );
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

        list($parent, $base) = \Sabre\HTTP\UrlUtil::splitPath($path);
        $parentNode = $this->server->tree->getNodeForPath($parent);

        /*
        if ($parentNode->childExists($base)) {
            $childNode = $parentNode->getChild($base);
            $childNode->put($data);
        } else {*/
            $parentNode->createFile($base, $data);
        //}

    }

    function assertItemsInInbox($user, $count) {

        $inboxNode = $this->server->tree->getNodeForPath('calendars/'.$user.'/inbox');
        $this->assertEquals($count, count($inboxNode->getChildren()));

    }

}

