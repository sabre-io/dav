<?php

namespace Sabre\CalDAV\Backend;

/**
 * Every CalDAV backend must at least implement this interface.
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface SubscriptionSupport extends BackendInterface {

    /**
     * Returns a list of subscriptions for a principal.
     *
     * Every subscription is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    subscription. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the subscription is
     *    accessed.
     *  * principaluri. The owner of the subscription. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore, all the subscription info must be returned too:
     *
     * 1. {DAV:}displayname
     * 2. {http://apple.com/ns/ical/}refreshrate
     * 3. {http://calendarserver.org/ns/}subscribed-strip-todos (omit if todos
     *    should not be stripped).
     * 4. {http://calendarserver.org/ns/}subscribed-strip-alarms (omit if alarms
     *    should not be stripped).
     * 5. {http://calendarserver.org/ns/}subscribed-strip-attachments (omit if
     *    attachments should not be stripped).
     * 6. {http://calendarserver.org/ns/}source (Must be a
     *     Sabre\DAV\Property\Href).
     * 7. {http://apple.com/ns/ical/}calendar-color
     * 8. {http://apple.com/ns/ical/}calendar-order
     *
     * @param string $principalUri
     * @return array
     */
    public function getSubscriptionsForUser($principalUri); 

    /**
     * Creates a new subscription for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this subscription in other methods, such as updateSubscription.
     *
     * @param string $principalUri
     * @param string $uri
     * @param array $properties
     * @return void
     */
    public function createSubscription($principalUri, $uri, array $properties);

    /**
     * Updates a subscription
     *
     * The mutations array uses the propertyName in clark-notation as key,
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
     * @param mixed $subscriptionId
     * @param array $mutations
     * @return bool|array
     */
    public function updateSubscription($subscriptionId, array $mutations);

    /**
     * Deletes a subscription
     *
     * @param mixed $subscriptionId
     * @return void
     */
    public function deleteSubscription($subscriptionId);

}
