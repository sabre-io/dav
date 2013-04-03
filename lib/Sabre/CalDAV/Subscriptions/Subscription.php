<?php

namespace Sabre\CalDAV\Subscriptions;

use
    Sabre\DAV\Collection,
    Sabre\CalDAV\Backend\SupportsSubscriptions;

/**
 * Subscription Node
 *
 * This node represents a subscription.
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Subscription extends Collection implements ISubscription {

    /**
     * caldavBackend
     *
     * @var SupportsSubscriptions
     */
    protected $caldavBackend;

    /**
     * subscriptionInfo
     *
     * @var array
     */
    protected $subscriptionInfo;

    /**
     * Constructor
     *
     * @param SupportsSubscriptions $caldavBackend
     * @param array $calendarInfo
     */
    public function __construct(SupportsSubscriptions $caldavBackend, array $subscriptionInfo) {

        $this->caldavBackend = $caldavBackend;
        $this->subscriptionInfo = $subscriptionInfo;

        $required = [
            'id',
            'uri',
            'principaluri',
            'source',
            ];

        foreach($required as $r) {
            if (!isset($subscriptionInfo[$required])) {
                throw new \InvalidArgumentException('The ' . $r . ' field is required when creating a subscription node');
            }
        }

    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName() {

        return $this->subscriptionInfo['uri'];

    }

    /**
     * Returns the last modification time
     *
     * @return int
     */
    public function getLastModified() {

        if (isset($this->subscriptionInfo['modified'])) {
            return $this->subscriptionInfo['modified'];
        }

    }

    /**
     * Deletes the current node
     *
     * @return void
     */
    public function delete() {

        $this->caldavServer->deleteSubscription(
            $this->subscriptionInfo['id']
        );

    }

    /**
     * Returns an array with all the child nodes
     *
     * @return DAV\INode[]
     */
    public function getChildren() {

        return [];

    }

    /**
     * Updates properties on this node,
     *
     * The properties array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existent property is always successful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname.
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param array $mutations
     * @return bool|array
     */
    public function updateProperties($mutations) {

        $this->caldavBackend->updateSubscription(
            $this->subscriptionInfo['id'],
            $mutations
        );

    }

    /**
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested,
     * encoded in clark-notation {xmlnamespace}tagname
     *
     * If the array is empty, it means 'all properties' were requested.
     *
     * Note that it's fine to liberally give properties back, instead of
     * conforming to the list of requested properties.
     * The Server class will filter out the extra.
     *
     * @param array $properties
     * @return void
     */
    public function getProperties($properties) {

        $r = [];

        foreach($properties as $prop) {

            if (isset($this->subscriptionInfo[$prop])) {
                $r[$prop] = $this->subscriptionInfo[$prop];
            }

        }

        return $r;

    }

}
