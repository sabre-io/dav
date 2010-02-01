<?php

/**
 * The User object represents a single user.
 * 
 * This user contains a CalendarCollection object.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_User implements Sabre_DAV_IProperties, Sabre_DAV_IDirectory, Sabre_CalDAV_ICalendarCollection {

    /**
     * Authentication backend 
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    private $authBackend;

    /**
     * Array with user information 
     * 
     * @var array 
     */
    private $userInfo;

    /**
     * Constructor 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param Sabre_CalDAV_Backend_Abstract $caldavBackend 
     * @param mixed $userInfo 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend, Sabre_CalDAV_Backend_Abstract $caldavBackend, $userInfo) {

        $this->authBackend = $authBackend;
        $this->caldavBackend = $caldavBackend;
        $this->userInfo = $userInfo;
       
    }

    /**
     * Returns the name of this object 
     * 
     * @return string
     */
    public function getName() {
       
        return $this->userInfo['userId'];

    }

    /**
     * Updates the name of this object 
     * 
     * @param string $name 
     * @return void
     */
    public function setName($name) {

        throw new Sabre_DAV_Exception_PermissionDenied();

    }

    /**
     * Deletes this object 
     * 
     * @return void
     */
    public function delete() {

        throw new Sabre_DAV_Exception_PermissionDenied();

    }

    /**
     * Returns the last modification date 
     * 
     * @return int 
     */
    public function getLastModified() {

        return null; 

    }

    /**
     * Returns a list of properties 
     * 
     * @param array $requestedProperties 
     * @return array 
     */
    public function getProperties($requestedProperties) {

        return array(
            '{DAV:}displayname' => $this->userInfo['displayName'],
        );

    }

    /**
     * Updates properties 
     * 
     * @param mixed $properties 
     * @return void
     */
    public function updateProperties($properties) {

        return null;

    }

    /**
     * Creates a new file under this object.
     *
     * This is currently not allowed
     * 
     * @param string $filename 
     * @param resource $data 
     * @return void
     */
    public function createFile($filename, $data=null) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating new files in this collection is not supported');

    }

    /**
     * Creates a new directory under this object.
     *
     * This is currently not allowed.
     * 
     * @param string $filename 
     * @return void
     */
    public function createDirectory($filename) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating new collections in this collection is not supported');

    }

    /**
     * Returns a single calendar, by name 
     * 
     * @param string $name
     * @todo needs optimizing
     * @return Sabre_CalDAV_ICalendar 
     */
    public function getChild($name) {

        foreach($this->getChildren() as $child) {
            if ($name==$child->getName())
                return $child;

        }
        throw new Sabre_DAV_Exception_FileNotFound('Calendar with name \'' . $name . '\' could not be found');

    }

    /**
     * Returns a list of calendars
     * 
     * @return array 
     */
    public function getChildren() {

        $calendars = $this->caldavBackend->getCalendarsForUser($this->userInfo['userId']);
        $objs = array();
        foreach($calendars as $calendar) {
            $objs[] = new Sabre_CalDAV_Calendar($this->caldavBackend,$calendar);
        }
        return $objs;

    }

    /**
     * Creates a new calendar
     * 
     * @param string $name 
     * @param string $properties 
     * @return void
     */
    public function createCalendar($name, $properties) {

        $displayname = isset($properties['{DAV:}displayname'])?$properties['{DAV:}displayname']:$name;
        $description = isset($properties['{urn:ietf:params:xml:ns:caldav}calendar-description'])?$properties['{urn:ietf:params:xml:ns:caldav}calendar-description']:'';
        $this->caldavBackend->createCalendar($this->userInfo['userId'],$name,$displayname,$description);

    }

}
