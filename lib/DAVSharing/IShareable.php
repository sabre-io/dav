<?php

namespace Sabre\DAVSharing;

use Sabre\DAVSharing\IACL;

/**
 * If a Node class implements this interface, it may be 'shared' with other
 * principals. When a node is shared, multiple people may be able to edit it.
 *
 * A few use-cases:
 * * shared calendars
 * * shared address books
 * * shared files
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class IShareable extends IACL {

    /**
     * Returns the list of people who are invites to share this resource.
     *
     * This list contains people who have only been invites, but also the
     * people who have accepted or declined to share the resource.
     *
     * This method returns an array, each element contains the following
     * properties:
     *   * href - A reference to the person who's the sharee. This is either
     *     a URI or a relative URI. If it's a relative URI it always refers to
     *     a local principal. If it's a URI it may be a mailto: address.
     *   * commonName - A 'display name' for the invitee.
     *   * status - 1 = invited, 2 = accepted, 3 = declined, 4 = deleted.
     *   * access - 1 = read-only, 2 = read-write.
     *
     * @return array
     */
    function getInvitees();

    /**
     * Updates the list of people who are sharing this resource.
     *
     * The list of mutations is similar to the list of invitees, with a few
     * differences:
     *
     * 1. status is never specified
     * 2. access may also be 3 = deleted
     *
     * The third access level is used to remove invitees from this resource.
     */
    function updateInvitees(array $mutations);

}


