<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAV\MkCol;

class CalendarHomeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sabre\CalDAV\CalendarHome
     */
    protected $usercalendars;

    /**
     * @var Backend\BackendInterface
     */
    protected $backend;

    public function setup()
    {
        $this->backend = TestUtil::getBackend();
        $this->usercalendars = new CalendarHome($this->backend, [
            'uri' => 'principals/user1',
        ]);
    }

    public function testSimple()
    {
        $this->assertEquals('user1', $this->usercalendars->getName());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotFound
     * @depends testSimple
     */
    public function testGetChildNotFound()
    {
        $this->usercalendars->getChild('randomname');
    }

    public function testChildExists()
    {
        $this->assertFalse($this->usercalendars->childExists('foo'));
        $this->assertTrue($this->usercalendars->childExists('UUID-123467'));
    }

    public function testGetOwner()
    {
        $this->assertEquals('principals/user1', $this->usercalendars->getOwner());
    }

    public function testGetGroup()
    {
        $this->assertNull($this->usercalendars->getGroup());
    }

    public function testGetACL()
    {
        $expected = [
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
        ];
        $this->assertEquals($expected, $this->usercalendars->getACL());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testSetACL()
    {
        $this->usercalendars->setACL([]);
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     * @depends testSimple
     */
    public function testSetName()
    {
        $this->usercalendars->setName('bla');
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     * @depends testSimple
     */
    public function testDelete()
    {
        $this->usercalendars->delete();
    }

    /**
     * @depends testSimple
     */
    public function testGetLastModified()
    {
        $this->assertNull($this->usercalendars->getLastModified());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
     * @depends testSimple
     */
    public function testCreateFile()
    {
        $this->usercalendars->createFile('bla');
    }

    /**
     * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
     * @depends testSimple
     */
    public function testCreateDirectory()
    {
        $this->usercalendars->createDirectory('bla');
    }

    /**
     * @depends testSimple
     */
    public function testCreateExtendedCollection()
    {
        $mkCol = new MkCol(
            ['{DAV:}collection', '{urn:ietf:params:xml:ns:caldav}calendar'],
            []
        );
        $result = $this->usercalendars->createExtendedCollection('newcalendar', $mkCol);
        $this->assertNull($result);
        $cals = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(3, count($cals));
    }

    /**
     * @expectedException \Sabre\DAV\Exception\InvalidResourceType
     * @depends testSimple
     */
    public function testCreateExtendedCollectionBadResourceType()
    {
        $mkCol = new MkCol(
            ['{DAV:}collection', '{DAV:}blabla'],
            []
        );
        $this->usercalendars->createExtendedCollection('newcalendar', $mkCol);
    }

    /**
     * @expectedException \Sabre\DAV\Exception\InvalidResourceType
     * @depends testSimple
     */
    public function testCreateExtendedCollectionNotACalendar()
    {
        $mkCol = new MkCol(
            ['{DAV:}collection'],
            []
        );
        $this->usercalendars->createExtendedCollection('newcalendar', $mkCol);
    }

    public function testGetSupportedPrivilegesSet()
    {
        $this->assertNull($this->usercalendars->getSupportedPrivilegeSet());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotImplemented
     */
    public function testShareReplyFail()
    {
        $this->usercalendars->shareReply('uri', DAV\Sharing\Plugin::INVITE_DECLINED, 'curi', '1');
    }
}
