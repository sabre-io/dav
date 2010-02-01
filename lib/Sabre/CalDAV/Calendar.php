<?php

/**
 * This object represents a CalDAV calendar.
 *
 * A calendar can contain multiple TODO and or Events. These are represented
 * as Sabre_CalDAV_CalendarObject objects.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Calendar implements Sabre_CalDAV_ICalendar {

    /**
     * This is an array with calendar information 
     * 
     * @var array 
     */
    private $calendarInfo;

    /**
     * CalDAV backend 
     * 
     * @var Sabre_CalDAV_Backend_Abstract 
     */
    private $caldavBackend;

    /**
     * Constructor 
     * 
     * @param Sabre_CalDAV_Backend_Abstract $caldavBackend 
     * @param array $calendarInfo 
     * @return void
     */
    public function __construct(Sabre_CalDAV_Backend_Abstract $caldavBackend,$calendarInfo) {

        $this->calendarInfo = $calendarInfo;
        $this->caldavBackend = $caldavBackend;

    }

    /**
     * Returns the name of the calendar 
     * 
     * @return string 
     */
    public function getName() {

        return $this->calendarInfo['uri'];

    }

    /**
     * Updates properties such as the display name and description 
     * 
     * @param array $mutations 
     * @return array 
     */
    public function updateProperties($mutations) {

        $displayName = $this->calendarInfo['displayname'];
        $description = $this->calendarInfo['description'];

        $response = array();
        foreach($mutations as $mutation) {

            if ($mutation[0] == Sabre_DAV_Server::PROP_REMOVE) {
                $response[] = array($mutation[1],403);
            } else {
                switch($mutation[1]) {

                    case '{DAV:}displayname' :
                        $displayName = $mutation[2];
                        $result = 200;
                        break;
                    case '{urn:ietf:params:xml:ns:caldav}calendar-description' :
                        $description = $mutation[2];
                        $result = 200;
                        break;
                    default :
                        $result = 403;
                        break;

                }

                $response[] = array($mutation[1],$result);

            }

        }

        $this->caldavBackend->updateCalendar($this->calendarInfo['userid'],$this->calendarInfo['uri'],$displayName,$description);

        return $response;

    }

    /**
     * Returns the list of properties 
     * 
     * @param array $properties 
     * @return array 
     */
    public function getProperties($requestedProperties) {

        $response = array();

        foreach($requestedProperties as $prop) switch($prop) {

            case '{DAV:}displayname' : $response[$prop] = $this->calendarInfo['displayname']; break;
            case '{DAV:}resourcetype' : $response[$prop] =  new Sabre_DAV_Property_ResourceType(array('{urn:ietf:params:xml:ns:caldav}calendar','{DAV:}collection')); break;
            case '{urn:ietf:params:xml:ns:caldav}description' : $response[$prop] = $this->calendarInfo['description']; break;
            case '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' :  $response[$prop] = new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT','VTODO')); break;
            case '{urn:ietf:params:xml:ns:caldav}supported-calendar-data' : $response[$prop] = new Sabre_CalDAV_Property_SupportedCalendarData(); break;
            case '{urn:ietf:params:xml:ns:caldav}supported-collation-set' : $response[$prop] =  new Sabre_CalDAV_Property_SupportedCollationSet(); break;

        }
        return $response;

    }

    /**
     * Returns a calendar object
     *
     * The contained calendar objects are for example Events or Todo's.
     * 
     * @param string $name 
     * @return Sabre_DAV_ICalendarObject 
     */
    public function getChild($name) {

        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'],$name);
        if (!$obj) throw new Sabre_DAV_Exception_FileNotFound('Calendar object not found');
        return new Sabre_CalDAV_CalendarObject($this->caldavBackend,$obj);

    }

    /**
     * Returns the full list of calendar objects  
     * 
     * @return array 
     */
    public function getChildren() {

        $objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
        $children = array();
        foreach($objs as $obj) {
            $children[] = new Sabre_CalDAV_CalendarObject($this->caldavBackend,$obj);
        }
        return $children;

    }

    /**
     * Creates a new directory
     *
     * We actually block this, as subdirectories are not allowed in calendars.
     * 
     * @param string $name 
     * @return void
     */
    public function createDirectory($name) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating collections in calendar objects is not allowed');

    }

    /**
     * Creates a new file
     *
     * The contents of the new file must be a valid ICalendar string.
     * 
     * @param string $name 
     * @param resource $calendarData 
     * @return void
     */
    public function createFile($name,$calendarData = null) {

        $calendarData = stream_get_contents($calendarData);
        $this->caldavBackend->createCalendarObject($this->calendarInfo['id'],$name,$calendarData);

    }

    /**
     * Deletes the calendar. 
     * 
     * @return void
     */
    public function delete() {

        $this->caldavBackend->deleteCalendar($this->calendarInfo['id']);

    }

    /**
     * Renames the calendar. Note that most calendars use the 
     * {DAV:}displayname to display a name to display a name. 
     * 
     * @param string $newName 
     * @return void
     */
    public function setName($newName) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Renaming calendars is not yet supported');

    }

    /**
     * Returns the last modification date as a unix timestamp.
     * 
     * @return void
     */
    public function getLastModified() {

        return null;

    }

}
