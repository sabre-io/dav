<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\VObject;

/**
 * The Attendee class represents an ATTENDEE.
 *
 * @author Brett (https://github.com/bretten)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 * @copyright Copyright (C) 2007-2013 fruux GmbH. All rights reserved.
 */
class Attendee {

    /**
     * The Attendee's mailto: address.
     *
     * @var string
     */
    protected $email;

    /**
     * The Attendee's CN Parameter.
     *
     * @var string
     */
    protected $cn;

    /**
     * The principal uri that this Attendee is associated to.
     *
     * @var string
     */
    protected $principalUri;

    /**
     * The default calendar that the scheduling objects will be
     * saved to (schedule-default-calendar-URL).
     *
     * @var string
     */
    protected $defaultCalendar;

    /**
     * The Attendee's scheduling object.
     *
     * @var VObject
     */
    protected $schedulingObject;

    /**
     * The backend
     *
     * @var \Sabre\CalDAV\Backend\AbstractBackend
     */
    protected $caldavBackend;

    /**
     * Constructor
     *
     * @param string \Sabre\CalDAV\Backend\AbstractBackend $caldavBackend
     * @param string $principalUri
     * @param \Sabre\VObject\Propertyd $attendee
     * @return void 
     */
    public function __construct(\Sabre\CalDAV\Backend\AbstractBackend $caldavBackend, $principalUri, $attendee, $schedulingObject) {

        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;
        $this->email = strtolower(substr($attendee->getValue(), 7));
        $this->cn = '';
        if (isset($attendee['CN'])) $this->cn = (string)$attendee['CN'];
        $this->schedulingObject = $schedulingObject;
        

    }
    
    /**
     * Returns the principal uri for this Attendee.
     *
     * @return string
     */
    public function getPrincipalUri() {

        return $this->principalUri;

    }

    /**
     * Returns the email for this Attendee.
     *
     * @return string
     */
    public function getEmail() {

        return $this->email;

    }

    /**
     * Returns the CN parameter for this Attendee.
     *
     * @return string
     */
    public function getCn() {

        return $this->cn;

    }

    /**
     * Returns the default calendar for this Attendee.
     *
     * @return string
     */
    public function getDefaultCalendar() {

        if (!$this->defaultCalendar) {
            $this->defaultCalendar = $this->caldavBackend->getDefaultCalendar($this->principalUri);
        }

        return $this->defaultCalendar;

    }

    /**
     * Returns the scheduling object for this Attendee.
     *
     * @return string
     */
    public function getSchedulingObject() {

        return $this->schedulingObject;

    }

    /**
     * Clones the VCOMPONENT and adds it to the Attendee's scheduling object.
     *
     * @param VObject $vObject
     * @return void
     */
    public function cloneAdd($vObject) {

        $this->schedulingObject->add(clone $vObject);

    }

    /**
     * Adds an EXDATE to the Attendee's main VEVENT component.
     *
     * @param string $exDate
     * @return void
     */
    public function addExDate($exDate) {

        // TODO Take into account EXDATEs that have a TZID parameter
        $this->schedulingObject->VEVENT[0]->add("EXDATE", $exDate);

    }
}