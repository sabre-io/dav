<?php

namespace Sabre\CalDAV\Backend;
use Sabre\DAV;
use Sabre\CalDAV;

class MockSubscriptionSupport extends Mock implements SubscriptionSupport {

    protected $subs = [];

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
     *  * source. Url to the actual feed
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
     * 7. {http://apple.com/ns/ical/}calendar-color
     * 8. {http://apple.com/ns/ical/}calendar-order
     *
     * @param string $principalUri
     * @return array
     */
    public function getSubscriptionsForUser($principalUri) {

        if (isset($this->subs[$principalUri])) {
            return $this->subs[$principalUri];
        }
        return [];

    }

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
    public function createSubscription($principalUri, $uri, array $properties) {

        $properties['uri'] = $uri;
        $properties['principaluri'] = $principalUri;

        if (!isset($this->subs[$principalUri])) {
            $this->subs[$principalUri] = [];
        }

        $id = [$principalUri, count($this->subs[$principalUri])+1]; 

        $properties['id'] = $id;

        $this->subs[$principalUri][] = array_merge($properties, [
            'id' => $id,
        ]); 

        return $id;

    }

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
    public function updateSubscription($subscriptionId, array $mutations) {

        $found = null;
        foreach($this->subs[$subscriptionId[0]] as &$sub) {

            if ($sub['id'][1] === $subscriptionId[1]) {
                $found =& $sub;
                break;
            }

        }

        if (!$found) return false;

        foreach($mutations as $k=>$v) {
            $found[$k] = $v;
        }

        return true;

    }

    /**
     * Deletes a subscription
     *
     * @param mixed $subscriptionId
     * @return void
     */
    public function deleteSubscription($subscriptionId) {

        $found = null;
        foreach($this->subs[$subscriptionId[0]] as $index=>$sub) {

            if ($sub['id'][1] === $subscriptionId[1]) {
                unset($this->subs[$subscriptionId[0]][$index]);
                return true;
            }

        }

        return false;

    }

}
