<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\INode;

/**
 * This interface represents a resource that has sharing capabilities, either
 * because it's possible for an owner to share the resource, or because this is
 * an instance of a shared resource.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ISharedNode extends INode {

    /**
     * Returns the 'access level' for the instance of this shared resource.
     *
     * The value should be one of the Sabre\DAV\Sharing\Plugin::ACCESS_
     * constants.
     *
     * @return int
     */
    function getShareAccess();

    /**
     * Updates the list of shares.
     *
     * The first array is a list of people that are to be added to the
     * shared resource.
     *
     * Every element in the add array has the following properties:
     *   * href - A url. Usually a mailto: address
     *   * summary - A description of the share, can also be false
     *   * readOnly - A boolean value
     *
     * In addition to that, the array might have any additional properties,
     * specified in clark-notation, such as '{DAV:}displayname'.
     *
     * Every element in the remove array is just the url of the sharee that's
     * to be removed.
     *
     * @param array $add
     * @param array $remove
     * @return void
     */
    function updateShares(array $add, array $remove);

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
    function getShares();

}
