<?php

namespace Sabre\CalDAV\Schedule;

use
    Sabre\DAV\Server,
    Sabre\DAV\ServerPlugin,
    Sabre\DAV\Property\Href,
    Sabre\DAV\Property\HrefList,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface,
    Sabre\VObject,
    Sabre\DAVACL,
    Sabre\CalDAV\ICalendar,
    Sabre\DAV\Exception\NotFound,
    Sabre\DAV\Exception\Forbidden,
    Sabre\DAV\Exception\BadRequest,
    Sabre\DAV\Exception\NotImplemented;

/**
 * CalDAV scheduling plugin.
 * =========================
 *
 * This plugin provides the functionality added by the "Scheduling Extensions
 * to CalDAV" standard, as defined in RFC6638.
 *
 * calendar-auto-schedule largely works by intercepting a users request to
 * update their local calendar. If a user creates a new event with attendees,
 * this plugin is supposed to grab the information from that event, and notify
 * the attendees of this.
 *
 * There's 3 possible transports for this:
 * * local delivery
 * * delivery through email (iMip)
 * * server-to-server delivery (iSchedule)
 *
 * iMip is simply, because we just need to add the iTip message as an email
 * attachment. Local delivery is harder, because we both need to add this same
 * message to a local DAV inbox, as well as live-update the relevant events.
 *
 * iSchedule is something for later.
 *
 *
 * Scheduling object resources
 * ---------------------------
 *
 * We will check for operations on calendar-objects only. That is, VEVENT
 * objects within CalDAV calendars.
 *
 * The scheduling spec calls normal calendar objects "Calendar Object
 * Resource".
 *
 * Some "Calendar Object Resources" are also "Scheduling Object Resources".
 * There's a few things we need to check to make sure that this is the case.
 *
 * Specifically, if an object has an ORGANIZER or an ATTENDEE property and the
 * specified address matches with one of the values for the current users'
 * calendar-user-address-set property, it's a scheduling resource and the
 * special processing kicks in.
 *
 *
 * SCHEDULE-AGENT
 * --------------
 *
 * Every ATTENDEE or ORGANIZER may also have a 'SCHEDULE-AGENT' parameter. The
 * value may be either SERVER or CLIENT. If this is set to CLIENT, the server
 * assumes that the client is responsible for handling the scheduling message.
 *
 * If this parameter is omitted, the default is 'SERVER'. SCHEDULE-AGENT may
 * also be 'NONE', in which case nobody does anything.
 *
 *
 * Organizer
 * ---------
 *
 * If the current user was the organizer of the object, we'll do the following
 * types of operations:
 *
 * 1. Creating a new scheduling resource -> iTIP REQUEST
 * 2. Updating a scheduling resource -> iTIP ADD or REQUEST
 * 3. Deleting a scheduling resource -> iTIP CANCEL
 *
 *
 * Attendee
 * --------
 *
 * An attendee is not allowed to update many items in the event, such as
 * DTSTART, LOCATION or SUMMARY.
 *
 * An Attendee may change the following things though:
 *
 * 1. Their own PARTSTAT -> results in iTIP REPLY
 * 2. TRANSP
 * 3. PERCENT-COMPLETE
 * 4. COMPLETED
 * 5. VALARM
 * 6. CALSCALE (oddly enough)
 * 7. PRODID
 * 8. add EXDATE items, and remove overridden components in recurring events.
 *    This effectively makes the attendee remove itself from one instance of
 *    the recurring event, and should trigger an iTIP REPLY (because they've
 *    declined the instance).
 * 9. CREATED, DTSTAMP, LAST-MODIFIED
 * 10. SCHEDULE-AGENT (but only if it was already set to CLIENT)
 * 11. Add new overridden components for recurring events.
 *
 * If an Attendee DELETEs the object, we will also send an iTIP REPLY
 * (decline).
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Plugin extends ServerPlugin {

    /**
     * This is the official CalDAV namespace
     */
    const NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    /**
     * Reference to main Server object.
     *
     * @var Server
     */
    protected $server;

    /**
     * The email handler for invites and other scheduling messages.
     *
     * @var IMip
     */
    protected $imipHandler;

    /**
     * The backend
     *
     * @var \Sabre\CalDAV\Backend\AbstractBackend
     */
    protected $caldavBackend;

    /**
     * The principal backend
     *
     * @var \Sabre\DAVACL\PrincipalBackend\AbstractBackend
     */
    protected $principalBackend;

    /**
     * Constructor
     *
     * @param \Sabre\CalDAV\Backend\AbstractBackend $caldavBackend
     */
    public function __construct(\Sabre\CalDAV\Backend\AbstractBackend $caldavBackend, \Sabre\DAVACL\PrincipalBackend\AbstractBackend $principalBackend) {

        $this->caldavBackend = $caldavBackend;
        $this->principalBackend = $principalBackend;

    }

    /**
     * Sets the iMIP handler.
     *
     * iMIP = The email transport of iCalendar scheduling messages. Setting
     * this is optional, but if you want the server to allow invites to be sent
     * out, you must set a handler.
     *
     * Specifically iCal will plain assume that the server supports this. If
     * the server doesn't, iCal will display errors when inviting people to
     * events.
     *
     * @param IMip $imipHandler
     * @return void
     */
    public function setIMipHandler(IMip $imipHandler) {

        $this->imipHandler = $imipHandler;

    }

    /**
     * Returns a list of features for the DAV: HTTP header.
     *
     * @return array
     */
    public function getFeatures() {

        return ['calendar-auto-schedule'];

    }

    /**
     * Returns the name of the plugin.
     *
     * Using this name other plugins will be able to access other plugins
     * using Server::getPlugin
     *
     * @return string
     */
    public function getPluginName() {

        return 'caldav-schedule';

    }

    /**
     * Initializes the plugin
     *
     * @param Server $server
     * @return void
     */
    public function initialize(Server $server) {

        $this->server = $server;
        $server->on('method:POST', [$this,'httpPost']);
        $server->on('beforeGetProperties', [$this,'beforeGetProperties']);
        $server->on('beforeCreateFile',    [$this,'beforeCreateFile']);

        /**
         * This information ensures that the {DAV:}resourcetype property has
         * the correct values.
         */
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Schedule\\IOutbox'] = '{urn:ietf:params:xml:ns:caldav}schedule-outbox';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Schedule\\IInbox'] = '{urn:ietf:params:xml:ns:caldav}schedule-inbox';

        /**
         * Properties we protect are made read-only by the server.
         */
        array_push($server->protectedProperties,
            '{' . self::NS_CALDAV . '}schedule-inbox-URL',
            '{' . self::NS_CALDAV . '}schedule-outbox-URL',
            '{' . self::NS_CALDAV . '}calendar-user-address-set',
            '{' . self::NS_CALDAV . '}calendar-user-type'
        );

    }

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are
     * available for the specified uri.
     *
     * @param string $uri
     * @return array
     */
    public function getHTTPMethods($uri) {

        try {
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (NotFound $e) {
            return [];
        }

        if ($node instanceof IOutbox) {
            return ['POST'];
        }

        return [];

    }

    /**
     * This method handles POST request for the outbox.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    public function httpPost(RequestInterface $request, ResponseInterface $response) {

        // Checking if this is a text/calendar content type
        $contentType = $request->getHeader('Content-Type');
        if (strpos($contentType, 'text/calendar')!==0) {
            return;
        }

        $path = $request->getPath();

        // Checking if we're talking to an outbox
        try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (NotFound $e) {
            return;
        }
        if (!$node instanceof IOutbox)
            return;

        $this->server->transactionType = 'post-caldav-outbox';
        $this->outboxRequest($node, $request, $response);

        // Returning false breaks the event chain and tells the server we've
        // handled the request.
        return false;

    }


    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param string $path
     * @param \Sabre\DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, \Sabre\DAV\INode $node, &$requestedProperties, &$returnedProperties) {

        $caldavPlugin = $this->server->getPlugin('caldav');

        if ($node instanceof DAVACL\IPrincipal) {

            $principalUrl = $node->getPrincipalUrl();

            // schedule-outbox-URL property
            $scheduleProp = '{' . self::NS_CALDAV . '}schedule-outbox-URL';
            if (in_array($scheduleProp,$requestedProperties)) {

                $calendarHomePath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl);
                $outboxPath = $calendarHomePath . '/outbox';

                unset($requestedProperties[array_search($scheduleProp, $requestedProperties)]);
                $returnedProperties[200][$scheduleProp] = new Href($outboxPath);

            }

            // schedule-inbox-URL property
            $scheduleProp = '{' . self::NS_CALDAV . '}schedule-inbox-URL';
            if (in_array($scheduleProp,$requestedProperties)) {

                $calendarHomePath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl);
                $inboxPath = $calendarHomePath . '/inbox';

                unset($requestedProperties[array_search($scheduleProp, $requestedProperties)]);
                $returnedProperties[200][$scheduleProp] = new Href($inboxPath);

            }


            // calendar-user-address-set property
            $calProp = '{' . self::NS_CALDAV . '}calendar-user-address-set';
            if (in_array($calProp,$requestedProperties)) {

                $addresses = $node->getAlternateUriSet();
                $addresses[] = $this->server->getBaseUri() . $node->getPrincipalUrl() . '/';
                unset($requestedProperties[array_search($calProp, $requestedProperties)]);
                $returnedProperties[200][$calProp] = new HrefList($addresses, false);

            }

        } // instanceof IPrincipal

    }

    /**
     * This method is called before a new node is created.
     *
     * @param string $path path to new object.
     * @param string|resource $data Contents of new object.
     * @param \Sabre\DAV\INode $parentNode Parent object
     * @param bool $modified Wether or not the item's data was modified/
     * @return bool|null
     */
    public function beforeCreateFile($path, &$data, $parentNode, &$modified) {

        if (!$parentNode instanceof ICalendar) {
            return;
        }

        // It's a calendar, so the contents are most likely an iCalendar
        // object. It's time to start processing this.
        //
        // This step also ensures that $data is re-propagated with a string
        // version of the object.
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        $vObj = VObject\Reader::read($data);

        // At the moment we only support VEVENT. VTODO may come later.
        if (!isset($vObj->VEVENT)) {
            return;
        }

        // Check the main event for an ORGANIZER
        if ($vObj->VEVENT[0]->ORGANIZER) {
            $organizer = $vObj->VEVENT[0]->ORGANIZER;
        }

        // No ORGANIZER
        if (!$organizer) {
            return;
        }

        // Get the calendar-user-address-set for the ORGANIZER
        $addresses = $this->getAddressesForPrincipal(
            $parentNode->getOwner()
        );


        // We're only handling creation of new objects by the ORGANIZER.
        if (!in_array($organizer->getValue(), $addresses)) {
            return;
        }
        
        // Process each VEVENT
        $attendees = $this->getAttendees($vObj, $addresses);

        // If the object doesn't have organizer or attendee information, we can
        // ignore it.
        if (!isset($attendees)) {
            return;
        }
        
        // Insert the new scheduling objects
        $this->insertSchedulingObjects($path, $organizer, $attendees);

        // After all this exciting action we set $data to the updated event
        // that contains all the new status information (if any).
        $newData = $vObj->serialize();
        if ($newData !== $data) {
            $data = $newData;

            // Setting $modified tells sabredav that the object has changed,
            // and that no ETag must be sent back.
            $modified = true;
        }

    }

    /**
     * This method is triggered before a file gets updated with new content.
     *
     * This plugin uses this method to ensure that Scheduling Objects will be
     * updated with PARTSTAT changes and also that when an ORGANIZER updates a
     * Scheduling Object, those changes will be sent to ATTENDEEs as well.
     *
     * @param string $path
     * @param DAV\IFile $node
     * @param resource $data
     * @param bool $modified Should be set to true, if this event handler
     *                       changed &$data.
     * @return void
     */
    public function beforeWriteContent($path, DAV\IFile $node, &$data, &$modified) {

        if (!$node instanceof ICalendarObject)
            return;

        // If this is NOT a scheduling object
            // return

        // If this is an ATTENDEE updating
            // Update PARTSTAT on the ORGANIZER scheduling object
        
        // If this is an ORGANIZER updating
            // If this originally was not a scheduling object
                // Get the attendees and call $this->insertSchedulingObjects()

            // If this was a scheduling object before
                // Update ATTENDEE scheduling objects

    }

    /**
     * This method gets the ATTENDEEs and their scheduling objects from a VCALENDAR.
     *
     * @param VObject $vCalendar
     * @param array $organizerUserAddressSet
     * @return array
     */
    public function getAttendees($vCalendar, $organizerUserAddressSet) {
        $attendees = [];
        
        // Will hold ATTENDEEs from the main VEVENT
        $mainAttendees = [];
        // Check each VEVENT for ATTENDEEs
        foreach ($vCalendar->VEVENT as $key => $vEvent) {
            // ATTENDEEs in the current VEVENT
            $current = [];
            
            // No ATTENDEEs
            if (!isset($vEvent->ATTENDEE)) continue;
            
            foreach ($vEvent->ATTENDEE as $attendee) {
                // Ignore the ATTENDEE if it is the same as the ORGANIZER
                if (in_array($attendee->getValue(), $organizerUserAddressSet)) continue;
                
                // Determine the SCHEDULE-AGENT parameter
                if (!isset($attendee['SCHEDULE-AGENT'])) {
                    $agent = 'SERVER';
                } else {
                    $agent = strtoupper($attendee['SCHEDULE-AGENT']);
                }
                // The SCHEDULE-AGENT parameter is 'SERVER' by default, but if it
                // was set to 'NONE' or 'CLIENT', we are not responsible for
                // delivering the message.
                if ($agent !== 'SERVER') continue;
                
                // Keep track of the current ATTENDEEs for this VEVENT
                $current[] = $attendee->getValue();
                
                // For the main VEVENT, keep track of the ATTENDEEs for determining EXDATEs
                if ($key == 0 && !in_array($attendee->getValue(), $mainAttendees)) {
                    $mainAttendees[] = $attendee->getValue();
                }
                
                // If there is no record of this ATTENDEE, add it to the array
                if (!array_key_exists($attendee->getValue(), $attendees)) {
                    // Create a scheduling object with the VTIMEZONEs of the original event
                    $schedulingObj = VObject\Component::create('VCALENDAR');
                    $schedulingObj->VERSION = "2.0";
                    $schedulingObj->PROID = "-//SabreDAV//";
                    if (isset($vCalendar->VTIMEZONE)) {
                        foreach ($vCalendar->VTIMEZONE as $timezone) {
                            $schedulingObj->add(clone $timezone);
                        }
                    }
                    // Determine the principal uri of this ATTENDEE
                    // If null, then the ATTENDEE is a not a user of the system
                    $principalUri = $this->principalBackend->getPrincipalUriByEmail(strtolower(substr($attendee->getValue(), 7)));
                    // Create an ATTENDEE object
                    $attendees[$attendee->getValue()] = new Attendee(
                        $this->caldavBackend,
                        $principalUri,
                        $attendee,
                        $schedulingObj
                    );
                }
                
                // Add the current VEVENT info to the current ATTENDEE
                $attendees[$attendee->getValue()]->cloneAdd($vEvent);
            }
            
            // For recurring exceptions, see if any main attendees are missing, so they can get an EXDATE
            if ($key > 0) {
                foreach (array_diff($mainAttendees, $current) as $exDateAttendee) {
                    $attendees[$exDateAttendee]->addExDate((string)$vEvent->{'RECURRENCE-ID'});
                }
            }
        }
        
        return $attendees;
    }

    /**
     * Handles first-time creation of scheduling objects and ITip messages.
     *
     * @param string $parentPath
     * @param \Sabre\VObject\Property $organizer
     * @param array $attendees
     */
    public function insertSchedulingObjects($parentPath, $organizer, $attendees) {

        $calendarObjects = array();
        $schedulingObjects = array();

        foreach ($attendees as $mailTo => $attendee) {
            // Check if the ATTENDEE is a user
            if ($attendee->getPrincipalUri()) {
                // Create a calendar object that will be inserted into their default calendar
                $default = $attendee->getDefaultCalendar();
                $calendarObjects[] = array(
                    'uri' => $attendee->getSchedulingObject()->VEVENT[0]->UID . ".ics",
                    'calendardata' => $attendee->getSchedulingObject()->serialize(),
                    'calendarid' => $default['id'],
                );
                
                // Create a scheduling object that will be inserted into their inbox
                $attendee->getSchedulingObject()->METHOD = "REQUEST";
                $schedulingObjects[] = array(
                    'uri' => md5(uniqid()) . ".ics",
                    'principaluri' => $attendee->getPrincipalUri(),
                    'calendardata' => $attendee->getSchedulingObject()->serialize(),
                );
            }
            
            // Create an ITip message
            $iTipMessage = new ITipMessage();

            // Stripping the mailto:
            $iTipMessage->recipient = strtolower(substr($attendee->getEmail(), 7));
            $iTipMessage->recipientName = $attendee->getCn();

            $iTipMessage->sender = strtolower(substr($organizer->getValue(), 7));
            if (isset($organizer['CN'])) $iTipMessage->senderName = $organizer['CN'];

            $iTipMessage->method = 'REQUEST';

            $iTipMessage->message = $attendee->getSchedulingObject();

            $this->deliver($iTipMessage);

            // TODO Update the SCHEDULE-STATUS parameter for the ATTENDEE in each VEVENT
            //$attendee['SCHEDULE-STATUS'] = $iTipMessage->scheduleStatus;
        }
        
        $this->caldavBackend->createCalendarObjects($parentPath, $calendarObjects);
        $this->caldavBackend->createSchedulingObjects($parentPath, $schedulingObjects);

    }

    /**
     * This method is responsible for delivering the ITip message.
     *
     * @param ITipMessage $itipMessage
     * @return void
     */
    public function deliver(ITipMessage $iTipMessage) {

        $iTipMessage->scheduleStatus =
            $this->iMipMessage($iTipMessage->sender, [$iTipMessage->recipient], $iTipMessage->message, '');

    }

    /**
     * Returns a list of addresses that are associated with a principal.
     *
     * @param string $principal
     * @return array
     */
    protected function getAddressesForPrincipal($principal) {

        $CUAS = '{' . self::NS_CALDAV . '}calendar-user-address-set';

        $properties = $this->server->getProperties(
            $principal,
            [$CUAS]
        );

        // If we can't find this information, we'll stop processing
        if (!isset($properties[$CUAS])) {
            return;
        }

        $addresses = $properties[$CUAS]->getHrefs();
        return $addresses;

    }

    /**
     * This method handles POST requests to the schedule-outbox.
     *
     * Currently, two types of requests are support:
     *   * FREEBUSY requests from RFC 6638
     *   * Simple iTIP messages from draft-desruisseaux-caldav-sched-04
     *
     * The latter is from an expired early draft of the CalDAV scheduling
     * extensions, but iCal depends on a feature from that spec, so we
     * implement it.
     *
     * @param IOutbox $outboxNode
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function outboxRequest(IOutbox $outboxNode, RequestInterface $request, ResponseInterface $response) {

        $outboxPath = $request->getPath();

        // Parsing the request body
        try {
            $vObject = VObject\Reader::read($request->getBody());
        } catch (VObject\ParseException $e) {
            throw new BadRequest('The request body must be a valid iCalendar object. Parse error: ' . $e->getMessage());
        }

        // The incoming iCalendar object must have a METHOD property, and a
        // component. The combination of both determines what type of request
        // this is.
        $componentType = null;
        foreach($vObject->getComponents() as $component) {
            if ($component->name !== 'VTIMEZONE') {
                $componentType = $component->name;
                break;
            }
        }
        if (is_null($componentType)) {
            throw new BadRequest('We expected at least one VTODO, VJOURNAL, VFREEBUSY or VEVENT component');
        }

        // Validating the METHOD
        $method = strtoupper((string)$vObject->METHOD);
        if (!$method) {
            throw new BadRequest('A METHOD property must be specified in iTIP messages');
        }

        // So we support two types of requests:
        //
        // REQUEST with a VFREEBUSY component
        // REQUEST, REPLY, ADD, CANCEL on VEVENT components

        $acl = $this->server->getPlugin('acl');

        if ($componentType === 'VFREEBUSY' && $method === 'REQUEST') {

            $acl && $acl->checkPrivileges($outboxPath, '{' . self::NS_CALDAV . '}schedule-query-freebusy');
            $this->handleFreeBusyRequest($outboxNode, $vObject, $request, $response);

        } elseif ($componentType === 'VEVENT' && in_array($method, ['REQUEST','REPLY','ADD','CANCEL'])) {

            $acl && $acl->checkPrivileges($outboxPath, '{' . Plugin::NS_CALDAV . '}schedule-post-vevent');
            $this->handleEventNotification($outboxNode, $vObject, $request, $response);

        } else {

            throw new NotImplemented('SabreDAV supports only VFREEBUSY (REQUEST) and VEVENT (REQUEST, REPLY, ADD, CANCEL)');

        }

    }

    /**
     * This method handles the REQUEST, REPLY, ADD and CANCEL methods for
     * VEVENT iTip messages.
     *
     * @param IOutbox $outboxNode
     * @param VObject\Component $vObject
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    protected function handleEventNotification(IOutbox $outboxNode, VObject\Component $vObject, RequestInterface $request, ResponseInterface $response) {

        $originator = $request->getHeader('Originator');
        $recipients = $request->getHeader('Recipient');

        if (!$originator) {
            throw new BadRequest('The Originator: header must be specified when making POST requests');
        }
        if (!$recipients) {
            throw new BadRequest('The Recipient: header must be specified when making POST requests');
        }

        $recipients = explode(',',$recipients);
        foreach($recipients as $k=>$recipient) {

            $recipient = trim($recipient);
            if (!preg_match('/^mailto:(.*)@(.*)$/i', $recipient)) {
                throw new BadRequest('Recipients must start with mailto: and must be valid email address');
            }
            $recipient = substr($recipient, 7);
            $recipients[$k] = $recipient;
        }

        // We need to make sure that 'originator' matches the currently
        // authenticated user.
        $aclPlugin = $this->server->getPlugin('acl');
        if (is_null($aclPlugin)) throw new DAV\Exception('The ACL plugin must be loaded for scheduling to work');
        $principal = $aclPlugin->getCurrentUserPrincipal();

        $props = $this->server->getProperties($principal, [
            '{' . self::NS_CALDAV . '}calendar-user-address-set',
        ]);

        $addresses = [];
        if (isset($props['{' . self::NS_CALDAV . '}calendar-user-address-set'])) {
            $addresses = $props['{' . self::NS_CALDAV . '}calendar-user-address-set']->getHrefs();
        }

        $found = false;
        foreach($addresses as $address) {

            // Trimming the / on both sides, just in case..
            if (rtrim(strtolower($originator),'/') === rtrim(strtolower($address),'/')) {
                $found = true;
                break;
            }

        }

        if (!$found) {
            throw new Forbidden('The addresses specified in the Originator header did not match any addresses in the owners calendar-user-address-set header');
        }

        // If the Originator header was a url, and not a mailto: address..
        // we're going to try to pull the mailto: from the vobject body.
        if (strtolower(substr($originator,0,7)) !== 'mailto:') {
            $originator = (string)$vObject->VEVENT->ORGANIZER;

        }
        if (strtolower(substr($originator,0,7)) !== 'mailto:') {
            throw new Forbidden('Could not find mailto: address in both the Orignator header, and the ORGANIZER property in the VEVENT');
        }
        $originator = substr($originator,7);

        $result = $this->iMIPMessage($originator, $recipients, $vObject, $principal);
        $response->setStatus(200);
        $response->setHeader('Content-Type','application/xml');
        $response->setBody($this->generateScheduleResponse($result));

    }

    /**
     * Sends an iMIP message by email.
     *
     * This method must return an array with status codes per recipient.
     * This should look something like:
     *
     * [
     *    'user1@example.org' => '2.0;Success'
     * ]
     *
     * Formatting for this status code can be found at:
     * https://tools.ietf.org/html/rfc5545#section-3.8.8.3
     *
     * A list of valid status codes can be found at:
     * https://tools.ietf.org/html/rfc5546#section-3.6
     *
     * @param string $originator
     * @param array $recipients
     * @param VObject\Component $vObject
     * @param string $principal Principal url
     * @return array
     */
    protected function iMIPMessage($originator, array $recipients, VObject\Component $vObject, $principal) {

        if (!$this->imipHandler) {
            $resultStatus = '5.2;This server does not support this operation';
        } else {
            $this->imipHandler->sendMessage($originator, $recipients, $vObject, $principal);
            $resultStatus = '2.0;Success';
        }

        $result = [];
        foreach($recipients as $recipient) {
            $result[$recipient] = $resultStatus;
        }

        return $result;

    }

    /**
     * Generates a schedule-response XML body
     *
     * The recipients array is a key->value list, containing email addresses
     * and iTip status codes. See the iMIPMessage method for a description of
     * the value.
     *
     * @param array $recipients
     * @return string
     */
    public function generateScheduleResponse(array $recipients) {

        $dom = new \DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $xscheduleResponse = $dom->createElement('cal:schedule-response');
        $dom->appendChild($xscheduleResponse);

        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $xscheduleResponse->setAttribute('xmlns:' . $prefix, $namespace);

        }

        foreach($recipients as $recipient=>$status) {
            $xresponse = $dom->createElement('cal:response');

            $xrecipient = $dom->createElement('cal:recipient');
            $xrecipient->appendChild($dom->createTextNode($recipient));
            $xresponse->appendChild($xrecipient);

            $xrequestStatus = $dom->createElement('cal:request-status');
            $xrequestStatus->appendChild($dom->createTextNode($status));
            $xresponse->appendChild($xrequestStatus);

            $xscheduleResponse->appendChild($xresponse);

        }

        return $dom->saveXML();

    }

    /**
     * This method is responsible for parsing a free-busy query request and
     * returning it's result.
     *
     * @param IOutbox $outbox
     * @param VObject\Component $vObject
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return string
     */
    protected function handleFreeBusyRequest(IOutbox $outbox, VObject\Component $vObject, RequestInterface $request, ResponseInterface $response) {

        $vFreeBusy = $vObject->VFREEBUSY;
        $organizer = $vFreeBusy->organizer;

        $organizer = (string)$organizer;

        // Validating if the organizer matches the owner of the inbox.
        $owner = $outbox->getOwner();

        $caldavNS = '{' . self::NS_CALDAV . '}';

        $uas = $caldavNS . 'calendar-user-address-set';
        $props = $this->server->getProperties($owner, [$uas]);

        if (empty($props[$uas]) || !in_array($organizer, $props[$uas]->getHrefs())) {
            throw new Forbidden('The organizer in the request did not match any of the addresses for the owner of this inbox');
        }

        if (!isset($vFreeBusy->ATTENDEE)) {
            throw new BadRequest('You must at least specify 1 attendee');
        }

        $attendees = [];
        foreach($vFreeBusy->ATTENDEE as $attendee) {
            $attendees[]= (string)$attendee;
        }


        if (!isset($vFreeBusy->DTSTART) || !isset($vFreeBusy->DTEND)) {
            throw new BadRequest('DTSTART and DTEND must both be specified');
        }

        $startRange = $vFreeBusy->DTSTART->getDateTime();
        $endRange = $vFreeBusy->DTEND->getDateTime();

        $results = [];
        foreach($attendees as $attendee) {
            $results[] = $this->getFreeBusyForEmail($attendee, $startRange, $endRange, $vObject);
        }

        $dom = new \DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $scheduleResponse = $dom->createElement('cal:schedule-response');
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $scheduleResponse->setAttribute('xmlns:' . $prefix,$namespace);

        }
        $dom->appendChild($scheduleResponse);

        foreach($results as $result) {
            $xresponse = $dom->createElement('cal:response');

            $recipient = $dom->createElement('cal:recipient');
            $recipientHref = $dom->createElement('d:href');

            $recipientHref->appendChild($dom->createTextNode($result['href']));
            $recipient->appendChild($recipientHref);
            $xresponse->appendChild($recipient);

            $reqStatus = $dom->createElement('cal:request-status');
            $reqStatus->appendChild($dom->createTextNode($result['request-status']));
            $xresponse->appendChild($reqStatus);

            if (isset($result['calendar-data'])) {

                $calendardata = $dom->createElement('cal:calendar-data');
                $calendardata->appendChild($dom->createTextNode(str_replace("\r\n","\n",$result['calendar-data']->serialize())));
                $xresponse->appendChild($calendardata);

            }
            $scheduleResponse->appendChild($xresponse);
        }

        $response->setStatus(200);
        $response->setHeader('Content-Type','application/xml');
        $response->setBody($dom->saveXML());

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
     * @param \DateTime $start
     * @param \DateTime $end
     * @param VObject\Component $request
     * @return array
     */
    protected function getFreeBusyForEmail($email, \DateTime $start, \DateTime $end, VObject\Component $request) {

        $caldavNS = '{' . Plugin::NS_CALDAV . '}';

        $aclPlugin = $this->server->getPlugin('acl');
        if (substr($email,0,7)==='mailto:') $email = substr($email,7);

        $result = $aclPlugin->principalSearch(
            ['{http://sabredav.org/ns}email-address' => $email],
            [
                '{DAV:}principal-URL', $caldavNS . 'calendar-home-set',
                '{http://sabredav.org/ns}email-address',
            ]
        );

        if (!count($result)) {
            return [
                'request-status' => '3.7;Could not find principal',
                'href' => 'mailto:' . $email,
            ];
        }

        if (!isset($result[0][200][$caldavNS . 'calendar-home-set'])) {
            return [
                'request-status' => '3.7;No calendar-home-set property found',
                'href' => 'mailto:' . $email,
            ];
        }
        $homeSet = $result[0][200][$caldavNS . 'calendar-home-set']->getHref();

        // Grabbing the calendar list
        $objects = [];
        foreach($this->server->tree->getNodeForPath($homeSet)->getChildren() as $node) {
            if (!$node instanceof ICalendar) {
                continue;
            }
            $aclPlugin->checkPrivileges($homeSet . $node->getName() ,$caldavNS . 'read-free-busy');

            // Getting the list of object uris within the time-range
            $urls = $node->calendarQuery([
                'name' => 'VCALENDAR',
                'comp-filters' => [
                    [
                        'name' => 'VEVENT',
                        'comp-filters' => [],
                        'prop-filters' => [],
                        'is-not-defined' => false,
                        'time-range' => [
                            'start' => $start,
                            'end' => $end,
                        ],
                    ],
                ],
                'prop-filters' => [],
                'is-not-defined' => false,
                'time-range' => null,
            ]);

            $calObjects = array_map(function($url) use ($node) {
                $obj = $node->getChild($url)->get();
                return $obj;
            }, $urls);

            $objects = array_merge($objects,$calObjects);

        }

        $vcalendar = new VObject\Component\VCalendar();
        $vcalendar->METHOD = 'REPLY';

        $generator = new VObject\FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($start, $end);
        $generator->setBaseObject($vcalendar);

        $result = $generator->getResult();

        $vcalendar->VFREEBUSY->ATTENDEE = 'mailto:' . $email;
        $vcalendar->VFREEBUSY->UID = (string)$request->VFREEBUSY->UID;
        $vcalendar->VFREEBUSY->ORGANIZER = clone $request->VFREEBUSY->ORGANIZER;

        return [
            'calendar-data' => $result,
            'request-status' => '2.0;Success',
            'href' => 'mailto:' . $email,
        ];
    }
}
