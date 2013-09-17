<?php

namespace Sabre\CalDAV;

/**
 * The SchedulingObject represents a scheduling object in the Inbox collection 
 *
 * @author Brett (https://github.com/bretten)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 * @copyright Copyright (C) 2007-2013 fruux GmbH. All rights reserved.
 */
class SchedulingObject extends \Sabre\DAV\File implements ISchedulingObject, \Sabre\DAVACL\IACL {

    /**
     * Sabre\CalDAV\Backend\BackendInterface
     *
     * @var Sabre\CalDAV\Backend\AbstractBackend
     */
    protected $caldavBackend;

    /**
     * Array with information about this SchedulingObject
     *
     * @var array
     */
    protected $objectData;

    /**
     * Array with information about the containing calendar
     *
     * @var array
     */
    protected $calendarInfo;

    /**
     * Constructor
     *
     * The following properties may be passed within $objectData:
     *
     *   * uri - A unique uri. Only the 'basename' must be passed.
     *   * calendardata (optional) - The iCalendar data
     *   * etag - (optional) The etag for this object, MUST be encloded with
     *            double-quotes.
     *   * size - (optional) The size of the data in bytes.
     *   * lastmodified - (optional) format as a unix timestamp.
     *   * acl - (optional) Use this to override the default ACL for the node.
     *
     * @param Backend\BackendInterface $caldavBackend
     * @param array $calendarInfo
     * @param array $objectData
     */
    public function __construct(Backend\BackendInterface $caldavBackend,array $calendarInfo,array $objectData) {

        $this->caldavBackend = $caldavBackend;

        if (!isset($objectData['uri'])) {
            throw new \InvalidArgumentException('The objectData argument must contain an \'uri\' property');
        }

        $this->calendarInfo = $calendarInfo;
        $this->objectData = $objectData;

    }

    /**
     * Returns the uri for this object
     *
     * @return string
     */
    public function getName() {

        return $this->objectData['uri'];

    }

    /**
     * Returns the ICalendar-formatted object
     *
     * @return string
     */
    public function get() {

        // Pre-populating the 'calendardata' is optional, if we don't have it
        // already we fetch it from the backend.
        if (!isset($this->objectData['calendardata'])) {
            $this->objectData = $this->caldavBackend->getSchedulingObject($this->calendarInfo['principaluri'], $this->objectData['uri']);
        }
        return $this->objectData['calendardata'];

    }

    /**
     * Updates the ICalendar-formatted object
     *
     * @param string|resource $calendarData
     * @return string
     */
    public function put($calendarData) {

    }

    /**
     * Deletes the scheduling message
     *
     * @return void
     */
    public function delete() {

        $this->caldavBackend->deleteSchedulingObject($this->calendarInfo['principaluri'],$this->objectData['uri']);

    }

    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType() {

        $mime = 'text/calendar; charset=utf-8';
        if ($this->objectData['component']) {
            $mime.='; component=' . $this->objectData['component'];
        }
        return $mime;

    }

    /**
     * Returns an ETag for this object.
     *
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * @return string
     */
    public function getETag() {

        if (isset($this->objectData['etag'])) {
            return $this->objectData['etag'];
        } else {
            return '"' . md5($this->get()). '"';
        }

    }

    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return int
     */
    public function getLastModified() {

        return $this->objectData['lastmodified'];

    }

    /**
     * Returns the size of this object in bytes
     *
     * @return int
     */
    public function getSize() {

        if (array_key_exists('size',$this->objectData)) {
            return $this->objectData['size'];
        } else {
            return strlen($this->get());
        }

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner() {

        return $this->calendarInfo['principaluri'];

    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup() {

        return null;

    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL() {

        // An alternative acl may be specified in the object data.
        if (isset($this->objectData['acl'])) {
            return $this->objectData['acl'];
        }

        // The default ACL
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->calendarInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-read',
                'protected' => true,
            ),

        );

    }

    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl) {

        throw new \Sabre\DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');

    }

    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See \Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    public function getSupportedPrivilegeSet() {

        return null;

    }

}

