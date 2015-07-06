<?php

namespace Sabre\CalDAV\Backend\PDO;

/**
 * This trait implements Sabre\CalDAV\Backend\SubscriptionSupport for the PDO
 * CalDAV backend.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait SubscriptionSupportTrait {

    /**
     * The table name that will be used for calendar subscriptions.
     *
     * @var string
     */
    public $calendarSubscriptionsTableName = 'calendarsubscriptions';

    /**
     * List of subscription properties, and how they map to database fieldnames.
     *
     * @var array
     */
    public $subscriptionPropertyMap = [
        '{DAV:}displayname'                                           => 'displayname',
        '{http://apple.com/ns/ical/}refreshrate'                      => 'refreshrate',
        '{http://apple.com/ns/ical/}calendar-order'                   => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'                   => 'calendarcolor',
        '{http://calendarserver.org/ns/}subscribed-strip-todos'       => 'striptodos',
        '{http://calendarserver.org/ns/}subscribed-strip-alarms'      => 'stripalarms',
        '{http://calendarserver.org/ns/}subscribed-strip-attachments' => 'stripattachments',
    ];

    /**
     * Returns a list of subscriptions for a principal.
     *
     * Every subscription is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    subscription. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the subscription.
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
     * 9. {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     *    (should just be an instance of
     *    Sabre\CalDAV\Property\SupportedCalendarComponentSet, with a bunch of
     *    default components).
     *
     * @param string $principalUri
     * @return array
     */
    function getSubscriptionsForUser($principalUri) {

        $fields = array_values($this->subscriptionPropertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';
        $fields[] = 'source';
        $fields[] = 'principaluri';
        $fields[] = 'lastmodified';

        // Making fields a comma-delimited list
        $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare("SELECT " . $fields . " FROM " . $this->calendarSubscriptionsTableName . " WHERE principaluri = ? ORDER BY calendarorder ASC");
        $stmt->execute([$principalUri]);

        $subscriptions = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            $subscription = [
                'id'           => $row['id'],
                'uri'          => $row['uri'],
                'principaluri' => $row['principaluri'],
                'source'       => $row['source'],
                'lastmodified' => $row['lastmodified'],

                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
            ];

            foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
                if (!is_null($row[$dbName])) {
                    $subscription[$xmlName] = $row[$dbName];
                }
            }

            $subscriptions[] = $subscription;

        }

        return $subscriptions;

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
     * @return mixed
     */
    function createSubscription($principalUri, $uri, array $properties) {

        $fieldNames = [
            'principaluri',
            'uri',
            'source',
            'lastmodified',
        ];

        if (!isset($properties['{http://calendarserver.org/ns/}source'])) {
            throw new Forbidden('The {http://calendarserver.org/ns/}source property is required when creating subscriptions');
        }

        $values = [
            ':principaluri' => $principalUri,
            ':uri'          => $uri,
            ':source'       => $properties['{http://calendarserver.org/ns/}source']->getHref(),
            ':lastmodified' => time(),
        ];

        foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {

                $values[':' . $dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO " . $this->calendarSubscriptionsTableName . " (" . implode(', ', $fieldNames) . ") VALUES (" . implode(', ', array_keys($values)) . ")");
        $stmt->execute($values);

        return $this->pdo->lastInsertId();

    }

    /**
     * Updates a subscription
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documenation for more info and examples.
     *
     * @param mixed $subscriptionId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateSubscription($subscriptionId, DAV\PropPatch $propPatch) {

        $supportedProperties = array_keys($this->subscriptionPropertyMap);
        $supportedProperties[] = '{http://calendarserver.org/ns/}source';

        $propPatch->handle($supportedProperties, function($mutations) use ($subscriptionId) {

            $newValues = [];

            foreach ($mutations as $propertyName => $propertyValue) {

                if ($propertyName === '{http://calendarserver.org/ns/}source') {
                    $newValues['source'] = $propertyValue->getHref();
                } else {
                    $fieldName = $this->subscriptionPropertyMap[$propertyName];
                    $newValues[$fieldName] = $propertyValue;
                }

            }

            // Now we're generating the sql query.
            $valuesSql = [];
            foreach ($newValues as $fieldName => $value) {
                $valuesSql[] = $fieldName . ' = ?';
            }

            $stmt = $this->pdo->prepare("UPDATE " . $this->calendarSubscriptionsTableName . " SET " . implode(', ', $valuesSql) . ", lastmodified = ? WHERE id = ?");
            $newValues['lastmodified'] = time();
            $newValues['id'] = $subscriptionId;
            $stmt->execute(array_values($newValues));

            return true;

        });

    }

    /**
     * Deletes a subscription
     *
     * @param mixed $subscriptionId
     * @return void
     */
    function deleteSubscription($subscriptionId) {

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarSubscriptionsTableName . ' WHERE id = ?');
        $stmt->execute([$subscriptionId]);

    }

}
