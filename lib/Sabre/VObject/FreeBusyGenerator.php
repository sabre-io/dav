<?php

/**
 * This class helps with generating FREEBUSY reports based on existing sets of 
 * objects.
 *
 * It only looks at VEVENT and VFREEBUSY objects from the sourcedata, and 
 * generates a single VFREEBUSY object.
 *
 * VFREEBUSY components are described in RFC5545, The rules for what should 
 * go in a single freebusy report is taken from RFC4791, section 7.10.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_FreeBusyGenerator {

    /**
     * Input objects 
     * 
     * @var array 
     */
    protected $objects;

    /**
     * Start of range 
     * 
     * @var DateTime|null 
     */
    protected $start;

    /**
     * End of range 
     * 
     * @var DateTime|null 
     */
    protected $end;

    /**
     * Sets the input objects
     *
     * Every object must either be a string or a Sabre_VObject_Component. 
     * 
     * @param array $objects 
     * @return void 
     */
    public function setObjects(array $objects) {

        $this->objects = array();
        foreach($objects as $object) {

            if (is_string($object)) {
                $this->objects[] = Sabre_VObject_Reader::read($object);
            } elseif ($object instanceof Sabre_VObject_Component) {
                $this->objects[] = $object;
            } else {
                throw new InvalidArgumentException('You can only pass strings or Sabre_VObject_Component arguments to setObjects');
            }

        }

    }

    /**
     * Sets the time range
     *
     * Any freebusy object falling outside of this time range will be ignored. 
     * 
     * @param DateTime $start 
     * @param DateTime $end 
     * @return void
     */
    public function setTimeRange(DateTime $start = null, DateTime $end = null) {
        
        $this->start = $start;
        $this->end = $end;

    } 

    /**
     * Parses the input data and returns a correct VFREEBUSY object, wrapped in 
     * a VCALENDAR.
     * 
     * @return Sabre_VObject_Component 
     */
    public function getResult() {

        $busyTimes = array();

        foreach($this->objects as $object) {

            foreach($object->getComponents() as $component) {

                switch($component->name) {

                    case 'VEVENT' :

                        $FBTYPE = 'BUSY';
                        if (isset($component->TRANSP) && (strtoupper($component->TRANSP) === 'TRANSPARENT')) {
                            break;
                        }
                        if (isset($component->STATUS)) {
                            $status = strtoupper($component->STATUS);
                            if ($status==='CANCELLED') {
                                break;
                            }
                            if ($status==='TENTATIVE') {
                                $FBTYPE = 'BUSY-TENTATIVE';
                            }
                        }
                        $startTime = $component->DTSTART->getDateTime();
                        if ($this->end && $startTime > $this->end) {
                            break;
                        }
                        $endTime = null;
                        if (isset($component->DTEND)) {
                            $endTime = $component->DTEND->getDateTime();
                        } elseif (isset($component->DURATION)) {
                            $duration = Sabre_VObject_DateTimeParser::parseDuration((string)$component->DURATION);
                            $endTime = clone $startTime;
                            $endTime->add($duration);
                        } elseif ($component->DTSTART->getDateType() === Sabre_VObject_Element_DateTime::DATE) {
                            $endTime = clone $startTime;
                            $endTime->modify('+1 day');
                        } else {
                            // The event had no duration (0 seconds)
                            break;
                        }

                        if ($this->start && $endTime < $this->start) {
                            break;
                        }
                        $startTime->setTimeZone('UTC');
                        $endTime->setTimeZone('UTC');

                        $busyTimes[] = array(
                            $startTime,
                            $endTime,
                            $FBTYPE,
                        ); 
                        break;

                    case 'VFREEBUSY' :

                        break;



                }


            } 

        }

        $calendar = new Sabre_VObject_Calendar();
        $calendar = new Sabre_VObject_Component('VCALENDAR');
        $calendar->version = '2.0';
        $calendar->prodid = '-//SabreDAV//Sabre VObject ' . Sabre_VObject_Version::VERSION . '//EN';
        $calendar->calscale = 'GREGORIAN';

        $vfreebusy = new Sabre_VObject_Component('VFREEBUSY');
        $calendar->add($vfreebusy);

        if ($this->start) {
            $dtstart = new Sabre_VObject_Element_DateTime('DTSTART');
            $dtstart->setDateTime($this->start,Sabre_VObject_Element_DateTime::UTC);
            $vfreebusy->add($dtstart);
        }
        if ($this->end) {
            $dtend = new Sabre_VObject_Element_DateTime('DTEND');
            $dtend->setDateTime($this->start,Sabre_VObject_Element_DateTime::UTC);
            $vfreebusy->add($dtend);
        }
        $dtstamp = new Sabre_VObject_Element_DateTime('DTSTAMP');
        $dtstamp->setDateTime('now');
        $vfreebusy->add($dtstamp, Sabre_VObject_Element_DateTime::UTC);

        foreach($busyTimes as $busyTime) {

            $prop = new Sabre_VObject_Property(
                'FREEBUSY',
                $busyTime[0]->format('Ymd\\THis\\Z') . '/' . $busyTime[1]->format('Ymd\\THis\\Z')
            );
            $prop['FBTYPE'] = $busyTime[2];
            $vfreebusy->add($prop);

        }

        return $calendar;

    }

}

