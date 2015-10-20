<?php

namespace Sabre\CalDAV;

use Sabre\DAV\Sharing\Plugin as SPlugin;

/**
 * This object represents a CalDAV calendar that is shared by a different user.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SharedCalendar extends Calendar implements ISharedCalendar {

    /**
     * Returns the 'access level' for the instance of this shared resource.
     *
     * The value should be one of the Sabre\DAV\Sharing\Plugin::ACCESS_
     * constants.
     *
     * @return int
     */
    function getShareAccess() {

        return isset($this->calendarInfo['share-access']) ? $this->calendarInfo['share-access'] : SPlugin::ACCESS_NOTSHARED;

    }

    /**
     * Returns the list of people whom this resource is shared with.
     *
     * Every element in this array should have the following properties:
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first + last name
     *   * status - See the Sabre\DAV\Sharing\Plugin::STATUS_ constants.
     *   * access - one of the Sabre\DAV\Sharing\Plugin::ACCESS_ constants. 
     *
     * @return array
     */
    function getShares() {

        return $this->caldavBackend->getShares($this->calendarInfo['id']);

    }

    /**
     * Marks this calendar as published.
     *
     * Publishing a calendar should automatically create a read-only, public,
     * subscribable calendar.
     *
     * @param bool $value
     * @return void
     */
    function setPublishStatus($value) {

        $this->caldavBackend->setPublishStatus($this->calendarInfo['id'], $value);

    }

    /**
     * Updates the list of shares.
     *
     * The first array is a list of people that are to be added to the
     * calendar.
     *
     * Every element in the add array has the following properties:
     *   * href - A url. Usually a mailto: address
     *   * commonName - Usually a first and last name, or false
     *   * summary - A description of the share, can also be false
     *   * readOnly - A boolean value
     *
     * Every element in the remove array is just the address string.
     *
     * @param array $add
     * @param array $remove
     * @return void
     */
    function updateShares(array $add, array $remove) {

        $this->caldavBackend->updateShares($this->calendarInfo['id'], $add, $remove);

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    function getOwner() {

        return $this->calendarInfo['{http://sabredav.org/ns}owner-principal'];

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
    function getACL() {

        // The top-level ACL only contains access information for the true
        // owner of the calendar, so we need to add the information for the
        // sharee.
        $acl = parent::getACL();

        switch ($this->getShareAccess()) {
            case SPlugin::ACCESS_NOTSHARED :
            case SPlugin::ACCESS_OWNER :
                $acl[] = [
                    'privilege' => '{DAV:}share',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                // No break intentional!
            case SPlugin::ACCESS_READWRITE :
                $acl[] = [
                    'privilege' => '{DAV:}write',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                // No break intentional!
            case SPlugin::ACCESS_READONLY :
                $acl[] = [
                    'privilege' => '{DAV:}write-properties',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                $acl[] = [
                    'privilege' => '{DAV:}read',
                    'principal' => $this->calendarInfo['principaluri'],
                    'protected' => true,
                ];
                break;
        }
        return $acl;

    }

    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    function getSupportedPrivilegeSet() {

        $default = parent::getSupportedPrivilegeSet();
        $default['aggregates'][] = [
            'privilege' => '{DAV:}share',
        ];

        return $default;

    }

    /**
     * This method returns the ACL's for calendar objects in this calendar.
     * The result of this method automatically gets passed to the
     * calendar-object nodes in the calendar.
     *
     * @return array
     */
    function getChildACL() {

        $acl = parent::getChildACL();
        $acl[] = [
            'privilege' => '{DAV:}read',
            'principal' => $this->calendarInfo['principaluri'],
            'protected' => true,
        ];

        switch ($this->getShareAccess()) {
            case SPlugin::ACCESS_NOTSHARED :
                // No break intentional
            case SPlugin::ACCESS_OWNER :
                // No break intentional
            case SPlugin::ACCESS_READWRITE:
                if (!$this->calendarInfo['{http://sabredav.org/ns}read-only']) {
                    $acl[] = [
                        'privilege' => '{DAV:}write',
                        'principal' => $this->calendarInfo['principaluri'],
                        'protected' => true,
                    ];
                }
                break;
        }

        return $acl;

    }

}
