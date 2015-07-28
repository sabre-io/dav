<?php

namespace Sabre\CalDAV;

use Sabre\DAVACL\Plugin as ACLPlugin;

/**
 * This object represents a CalDAV calendar that can be shared with other
 * users.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ShareableCalendar extends Calendar implements IShareableCalendar {

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
     * Returns the list of people whom this calendar is shared with.
     *
     * Every element in this array should have the following properties:
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first + last name
     *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
     *   * readOnly - boolean
     *   * summary - Optional, a description for the share
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
        $acl[] = [
            'privilege' => '{DAV:}share',
            'principal' => $this->calendarInfo['principaluri'],
            'protected' => true,
        ];
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

}
