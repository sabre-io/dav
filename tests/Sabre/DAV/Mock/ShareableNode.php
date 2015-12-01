<?php

namespace Sabre\DAV\Mock;

use Sabre\DAV\Sharing\IShareableNode;

class ShareableNode extends \Sabre\DAV\Node implements IShareableNode {

    protected $name;

    function __construct($name) {

        $this->name = $name;

    }

    function getName() {

        return $this->name;

    }

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
    function updateShares(array $add, array $remove) {

    }

    /**
     * Returns the list of people whom this resource is shared with.
     *
     * Every element in this array should have the following properties:
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first + last name
     *   * status - See the Sabre\DAV\Sharing\Plugin::STATUS_ constants.
     *   * readOnly - boolean
     *
     * @return array
     */
    function getShares();


} 
