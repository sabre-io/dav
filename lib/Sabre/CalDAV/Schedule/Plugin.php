<?php

/**
 * SabreDAV CalDAV scheduling plugin
 *
 * This plugin is responsible for registering all the features required for the
 * CalDAV Scheduling extension.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Schedule_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * The scheduling root node
     */
    const SCHEDULE_ROOT = 'schedule';

    /**
     * Reference to Server object
     *
     * @var Sabre_DAV_Server
     */
    protected $server;

    /**
     * Initializes the plugin
     *
     * Registers all required events and features.
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $ns = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}';

        $this->server = $server;
        $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IOutbox'] = $ns . 'schedule-outbox';
        $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IInbox'] = $ns . 'schedule-inbox';

        // This ensures that a users' addresses are all searchable.
        $aclPlugin = $this->server->getPlugin('acl');
        if (!$aclPlugin) {
            throw new Sabre_DAV_Exception('ACL plugin must be loaded for the Scheduling plugin to work. We\'re doooomed');
        }
        $aclPlugin->principalSearchPropertySet[$ns . 'calendar-user-address-set'] =
            'Calendar user addresses';

        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));
        $server->subscribeEvent('unknownMethod', array($this,'unknownMethod'));
        // $server->subscribeEvent('afterBind',array($this,'afterBind'));

    }

    /**
     * Returns a list of features
     *
     * This is used in the DAV: header, which appears in responses to both the
     * OPTIONS request and the PROPFIND request.
     *
     * @return array
     */
    public function getFeatures() {

        return array();
     //   return array('calendar-auto-schedule');

    }

    // {{{ Event handlers

    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param string $path
     * @param Sabre_DAV_INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal || $node instanceof Sabre_CalDAV_UserCalendars) {

            // schedule-inbox-URL property
            $inboxProp = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL';
            if (in_array($inboxProp,$requestedProperties)) {
                $principalId = $node->getName();
                $inboxPath = self::SCHEDULE_ROOT . '/' . $principalId . '/inbox';
                unset($requestedProperties[$inboxProp]);
                $returnedProperties[200][$inboxProp] = new Sabre_DAV_Property_Href($inboxPath);
            }

            // schedule-outbox-URL property
            $outboxProp = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL';
            if (in_array($outboxProp,$requestedProperties)) {
                $principalId = $node->getName();
                $outboxPath = self::SCHEDULE_ROOT . '/' . $principalId . '/outbox';
                unset($requestedProperties[$outboxProp]);
                $returnedProperties[200][$outboxProp] = new Sabre_DAV_Property_Href($outboxPath);
            }

        }

    }

    /**
     * This is the handler for the 'unknownMethod' event.
     *
     * We are intercepting this event to add support for the POST method on the
     * schedule-outbox.
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function unknownMethod($method, $uri) {

        if ($method!=='POST') return;
        if ($this->server->httpRequest->getHeader('Content-Type') !== 'text/calendar')
            return;

        try {
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (Sabre_DAV_Exception_FileNotFound $e) {
            return;
        }

        if (!$node instanceof Sabre_CalDAV_Schedule_IOutbox)
            return;

        // Checking permission
        $acl = $this->server->getPlugin('acl');
        $privileges = array(
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy',
        );
        $acl->checkPrivileges($uri,$privileges);

        $response = $this->handleFreeBusyRequest($node, $this->server->httpRequest->getBody(true));

        $this->server->httpResponse->setHeader('Content-Type','application/xml');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody($response);

        return false;

    }

    // }}}

    /**
     * This method is responsible for parsing a free-busy query request and
     * returning it's result.
     *
     * @param Sabre_DAV_INode $node
     * @param string $request
     * @return string
     */
    protected function handleFreeBusyRequest(Sabre_CalDAV_Schedule_IOutbox $outbox, $request) {

        $vObject = Sabre_VObject_Reader::read($request);
     
        $method = (string)$vObject->method;
        if ($method!=='REQUEST') {
            throw new Sabre_DAV_Exception_BadRequest('The iTip object must have a METHOD:REQUEST property');
        }

        $vFreeBusy = $vObject->VFREEBUSY;
        if (!$vFreeBusy) {
            throw new Sabre_DAV_Exception_BadRequest('The iTip object must have a VFREEBUSY component');
        }

        $organizer = $vFreeBusy->organizer;

        $organizer = (string)$organizer;

        // Validating if the organizer matches the owner of the inbox.
        $owner = $outbox->getOwner();

        $caldavNS = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}';

        $uas = $caldavNS . 'calendar-user-address-set';
        $props = $this->server->getProperties($owner,array($uas));

        if (empty($props[$uas]) || !in_array($organizer, $props[$uas]->getHrefs())) {
            throw new Sabre_DAV_Exception_Forbidden('The organizer in the request did not match any of the addresses for the owner of this inbox');
        }

        if (!isset($vFreeBusy->ATTENDEE)) {
            throw new Sabre_DAV_Exception_BadRequest('You must at least specify 1 attendee');
        }

        $attendees = array();
        foreach($vFreeBusy->ATTENDEE as $attendee) {
            $attendees[]= (string)$attendee; 
        }


        if (!isset($vFreeBusy->DTSTART) || !isset($vFreeBusy->DTEND)) {
            throw new Sabre_DAV_Exception_BadRequest('DTSTART and DTEND must both be specified');
        }

        $startRange = $vFreeBusy->DTSTART->getDateTime();
        $endRange = $vFreeBusy->DTEND->getDateTime();

        $results = array();
        foreach($attendees as $k=>$attendee) {
            $results[] = $this->getFreeBusyForEmail($attendee, $startRange, $endRange);
        }

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $scheduleResponse = $dom->createElementNS(Sabre_CalDAV_Plugin::NS_CALDAV, 'cal:schedule-response');
        $dom->appendChild($scheduleResponse);

        foreach($results as $result) {
            $response = $dom->createElement('cal:response');

            $recipient = $dom->createElement('cal:recipient');
            $recipient->appendChild($dom->createTextNode($result['href']));
            $response->appendChild($recipient);

            $reqStatus = $dom->createElement('cal:request-status');
            $reqStatus->appendChild($dom->createTextNode($result['request-status'])); 
            $response->appendChild($reqStatus);

            if (isset($result['calendar-data'])) {

                $calendardata = $dom->createElement('cal:calendar-data');
                $calendardata->appendChild($dom->createTextNode(str_replace("\r\n","\n",$result['calendar-data']->serialize())));
                $response->appendChild($calendardata);

            }
            $scheduleResponse->appendChild($response);
        }

        return $dom->saveXML();

    }

    /**
     * Returns free-busy information for a specific address. The returned 
     * data is an array containing the following properties:
     *
     * calendar-data : A VFREEBUSY VObject
     * request-status : an iTip status code.
     * href: The principal's email address, as requested
     *
     * The following request status codes may be returned:
     *   * 2.0;description
     *   * 3.7;description
     *
     * @param string $email address 
     * @return Sabre_VObject_Component 
     */
    protected function getFreeBusyForEmail($email, $start, $end) {

        $caldavNS = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}';
        $uas = $caldavNS . 'calendar-user-address-set';

        $aclPlugin = $this->server->getPlugin('acl');
        $result = $aclPlugin->principalSearch(
            array($uas => $email),
            array('{DAV:}principal-URL', $caldavNS . 'calendar-home-set')
        );

        if (!count($result)) {
            return array(
                'request-status' => '3.7;Could not find principal',
                'href' => $email,
            );
        }

        if (!isset($result[0][200][$caldavNS . 'calendar-home-set'])) {
            return array(
                'request-status' => '3.7;No calendar-home-set property found',
                'href' => $email,
            );
        }
        $homeSet = $result[0][200][$caldavNS . 'calendar-home-set']->getHref();
        
        $calendars = array();

        // Grabbing the calendar list
        $props = array(
            '{DAV:}resourcetype',
        );
        $objects = array();
        foreach($this->server->tree->getNodeForPath($homeSet)->getChildren() as $node) {
            if (!$node instanceof Sabre_CalDAV_ICalendar) {
                continue;
            }
            $aclPlugin->checkPrivileges($homeSet . $node->getName() ,$caldavNS . 'read-free-busy');
             
            $calObjects = array_map(function($child) {
                $obj = $child->get();
                return $obj;
            }, $node->getChildren());

            $objects = array_merge($objects,$calObjects);

        }

        $vcalendar = new Sabre_VObject_Component('VCALENDAR');
        $vcalendar->VERSION = '2.0';
        $vcalendar->METHOD = 'REPLY';
        $vcalendar->CALSCALE = 'GREGORIAN';
        $vcalendar->PRODID = '-//SabreDAV//SabreDAV ' . Sabre_DAV_Version::VERSION . '//EN';

        $generator = new Sabre_VObject_FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($start, $end);
        $generator->setBaseObject($vcalendar);

        $result = $generator->getResult();

        $vcalendar->VFREEBUSY->ATTENDEE = $email;

        return array(
            'calendar-data' => $result,
            'request-status' => '2.0;Success',
            'href' => $email,
        );
    }

}

?>
