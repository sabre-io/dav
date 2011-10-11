<?php

require_once 'Sabre/DAVACL/MockPrincipalBackend.php';
require_once 'Sabre/CalDAV/Backend/Mock.php';
require_once 'Sabre/DAV/Auth/MockBackend.php';

class Sabre_CalDAV_Schedule_FreeBusyRequestTest extends PHPUnit_Framework_TestCase {

    protected $plugin;
    protected $server;
    protected $aclPlugin;
    protected $request;
    protected $authPlugin;
    protected $caldavPlugin;

    function setUp() {

        $principalBackend = new Sabre_DAVACL_MockPrincipalBackend();
        $caldavBackend = new Sabre_CalDAV_Backend_Mock(array());

        $tree = array(
            new Sabre_CalDAV_Schedule_RootNode($principalBackend, $caldavBackend),
            new Sabre_DAVACL_PrincipalCollection($principalBackend),
            new Sabre_CalDAV_CalendarRootNode($principalBackend, $caldavBackend),
        );

        $this->request = new Sabre_HTTP_Request(array(
            'CONTENT_TYPE' => 'text/calendar',
        ));

        $this->server = new Sabre_DAV_Server($tree);
        $this->server->httpRequest = $this->request;

        $this->aclPlugin = new Sabre_DAVACL_Plugin();
        $this->server->addPlugin($this->aclPlugin);

        $this->plugin = new Sabre_CalDAV_Schedule_Plugin();
        $this->server->addPlugin($this->plugin);

        $authBackend = new Sabre_DAV_Auth_MockBackend();
        $authBackend->setCurrentUser('user1');
        $this->authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
        $this->server->addPlugin($this->authPlugin);

        $this->caldavPlugin = new Sabre_CalDAV_Plugin();
        $this->server->addPlugin($this->caldavPlugin);

    }

    function testWrongMethod() {

        $this->assertNull(
            $this->plugin->unknownMethod('PUT','schedule/user1/outbox')
        );

    }

    function testWrongContentType() {

        $this->server->httpRequest = new Sabre_HTTP_Request(array(
            'CONTENT_TYPE' => 'text/plain',
        ));

        $this->assertNull(
            $this->plugin->unknownMethod('POST','schedule/user1/outbox')
        );

    }

    function testNotFound() {

        $this->assertNull(
            $this->plugin->unknownMethod('POST','schedule/user1/blabla')
        );

    }

    function testNotOutbox() {

        $this->assertNull(
            $this->plugin->unknownMethod('POST','schedule/user1/inbox')
        );

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoItipMethod() {

        $body = <<<ICS
BEGIN:VCALENDAR
BEGIN:VFREEBUSY
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','schedule/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoVFreeBusy() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
END:VEVENT
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','schedule/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_Forbidden
     */
    function testIncorrectOrganizer() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:john@wayne.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','schedule/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoAttendees() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','schedule/user1/outbox');

    }

    /**
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testNoDTStart() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','schedule/user1/outbox');

    }

    function testSucceed() {

        $body = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VFREEBUSY
ORGANIZER:mailto:user1.sabredav@sabredav.org
ATTENDEE:mailto:user2.sabredav@sabredav.org
ATTENDEE:mailto:user3.sabredav@sabredav.org
DTSTART:20110101T080000Z
DTEND:20110101T180000Z
END:VFREEBUSY
END:VCALENDAR
ICS;

        $this->request->setBody($body);
        $this->plugin->unknownMethod('POST','schedule/user1/outbox');

    }

}

?>
