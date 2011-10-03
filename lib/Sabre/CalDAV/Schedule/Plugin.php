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
        $acl->principalSearchPropertySet[$ns . 'calendar-user-address-set'] =
            'Calendar user addresses';

        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));
        $server->subscribeEvent('afterBind',array($this,'afterBind'));

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

        return array('calendar-auto-schedule');

    }

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
            if (in_array($inboxProp,$requestedProperties)) {
                $principalId = $node->getName(); 
                $outboxPath = self::SCHEDULE_ROOT . '/' . $principalId . '/outbox';
                unset($requestedProperties[$outboxProp]);
                $returnedProperties[200][$outboxProp] = new Sabre_DAV_Property_Href($outboxPath);
            }

        }

    }

    /**
     * The 'afterBind' event is invoked after any new file or collection is 
     * created.
     *
     * We are intercepting it, because we need to look at newly created 
     * calendar objects and do additional processing if they are also 
     * scheduling object resources.
     * 
     * @param string $uri 
     * @return void
     */
    public function afterBind($uri) {

        $node = $this->server->tree->getNodeForPath($uri);
        if (!$node instanceof Sabre_CalDAV_ICalendarObject)
            return;

        // It was indeed a calendar object.
        list($parentUri) = Sabre_DAV_URLUtil::splitPath($uri);
        $parentCalendar = $this->server->tree->getNodeForPath($uri);

        // We need to figure out the owner principal
        $owner = $parentCalendar->getOwner();

        // No owner ??
        if (is_null($owner)) return;

        $uas = '{' . Sabre_CalDAV_Plugin::NS . '}calendar-user-address-set';
        // Figure out the users' addresses
        $properties = $this->tree->getProperties($owner, array($uas));

        $addresses = $properties[$uas]?$properties[$uas]:null;
        if ($addresses instanceof Sabre_DAV_Property_HrefList) {
            $addresses = $addresses->getHrefs();
        }

        // No addresses?
        if (!$addresses) return;

        $iCalendarData = $node->get();
        if (is_resource($icalendarData))
            $icalendarData = stream_get_contents($iCalendarData);

        $vObject = Sabre_VObject_Reader::read($icalendarData);
        if(!$this->isSchedulingObject($vObject, $addresses, $attendeeType)) {
            return;
        }
        $this->createSchedulingResource($vObject, $attendeeType);


    }

    /**
     * This method is responsible for cases where a new scheduling resource 
     * was created. This is either through a PUT of a new object, or a 
     * modification from an existing object so it becomes a scheduling 
     * resource.
     * 
     * @param Sabre_VObject_Component $vObject
     * @param int $attendeeType 1 or 2 if it's a organizer or attendee
     * @return void
     */
    protected function createSchedulingResource(Sabre_VObject_Component $vObject, $attendeeType) {

        switch($attendeeType) {

            // Organizer
            case 1 :
                $attendees = array();

                if (!isset($vObject->SEQUENCE)) {
                    $vObject->SEQUENCE = 1;
                }
                if (!isset($vObject->DTSTAMP)) {
                    $dtStamp = new Sabre_VObject_Element_DateTime('DTSTAMP');
                    $dtStamp->setDateTime(new DateTime('NOW'), Sabre_VObject_Element_DateTime::UTC);
                    $vObject->add($dtStamp);
                } 

                foreach($vObject->selectComponents() as $vComponent) {
                    foreach($vComponent->attendee as $vAttendee) {

                        // We are only supposed to handle attendees that have 
                        // the schedule agent set to server (or not set at, 
                        // which also means 'server'.
                        if (isset($vAttendee['SCHEDULE-AGENT']) && strtoupper($vAttendee['SCHEDULE-AGENT']) !== 'SERVER') {
                            continue;
                        }

                        $attendees[] = $vAttendee;

                    } 

                }
                break;

            // Attendee
            case 2 :
                throw new Sabre_DAV_Exception_NotImplemented('This part of the specification has not yet been implemented');
                break;

        }


    }

    /**
     * Checks if a VObject is also a scheduling object resource.
     *
     * This method will also pass 1 or 2 to the attendeeType argument, 
     * depending on if it matched a user with the organizer, or attendee 
     * respectively.
     *
     * The compType argument is filled with the type of iCalendar component, 
     * which may either be VEVENT, VTODO or VJOURNAL.
     * 
     * @param Sabre_VObject_Component $vObject
     * @param array $userAddresses
     * @param int $attendeeType
     * @return bool 
     */
    protected function isSchedulingObject(Sabre_VObject_Component $vObject, array $userAddresses, &$attendeeType = null) {

        $vComponent = null;
        foreach($vObject->getComponents() as $vComponent) {

            if ($vComponent->name = 'VTIMEZONE')
                continue;

            break;

        }

        // This should actually not happen for valid iCalendar objects.
        if (is_null($vComponent))
            return false; 

        $organizer = (string)$vComponent->organizer;
        if (!$organizer)
            continue;

        if (in_array((string)$organizer, $userAddresses)) {
            $attendeeType = 1;
            return true;
        }

        foreach($vComponent->attendee as $attendee) {

            $attendee = (string)$attendee;

            if (in_array((string)$organizer, $userAddresses)) {
                $attendeeType = 2;
                return true;
            }

        }

        return false; 

    }

}

?>
