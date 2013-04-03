<?php

namespace Sabre\CalDAV\Subscriptions;

use
    Sabre\CalDAV\ICollection,
    Sabre\CalDAV\IProperties;

/**
 * ISubscription
 *
 * Nodes implementing this interface represent calendar subscriptions.
 *
 * The calenar subscription node doesn't do much, other than returning and
 * updating subscription-related properties.
 *
 * The following properties should be supported:
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
 * It is recommended to support every property.
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface ISubscription extends ICollection, IProperties {


}
