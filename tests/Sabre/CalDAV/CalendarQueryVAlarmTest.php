<?php

use Sabre\VObject;

class Sabre_CalDAV_CalendarQueryVAlarmTest extends PHPUnit_Framework_TestCase {

    /**
     * This test is specifically for a time-range query on a VALARM, contained
     * in a VEVENT that's recurring
     */
    function testValarm() {

        $vevent = VObject\Component::create('VEVENT');
        $vevent->RRULE = 'FREQ=MONTHLY';
        $vevent->DTSTART = '20120101T120000Z';
        $vevent->UID = 'bla';

        $valarm = VObject\Component::create('VALARM');
        $valarm->TRIGGER = '-P15D';
        $vevent->add($valarm);

        $vcalendar = VObject\Component::create('VCALENDAR');
        $vcalendar->add($vevent);

        $filter = array(
            'name' => 'VCALENDAR',
            'is-not-defined' => false,
            'time-range' => null,
            'prop-filters' => array(),
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'is-not-defined' => false,
                    'time-range' => null,
                    'prop-filters' => array(),
                    'comp-filters' => array(
                        array(
                            'name' => 'VALARM',
                            'is-not-defined' => false,
                            'prop-filters' => array(),
                            'comp-filters' => array(),
                            'time-range' => array(
                                'start' => new DateTime('2012-05-10'),
                                'end' => new DateTime('2012-05-20'),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $validator = new Sabre_CalDAV_CalendarQueryValidator();
        $this->assertTrue($validator->validate($vcalendar, $filter));


        // A limited recurrence rule, should return false
        $vevent = VObject\Component::create('VEVENT');
        $vevent->RRULE = 'FREQ=MONTHLY;COUNT=1';
        $vevent->DTSTART = '20120101T120000Z';
        $vevent->UID = 'bla';

        $valarm = VObject\Component::create('VALARM');
        $valarm->TRIGGER = '-P15D';
        $vevent->add($valarm);

        $vcalendar = VObject\Component::create('VCALENDAR');
        $vcalendar->add($vevent);

        $this->assertFalse($validator->validate($vcalendar, $filter));
    }

    function testAlarmWayBefore() {

        $vevent = VObject\Component::create('VEVENT');
        $vevent->DTSTART = '20120101T120000Z';
        $vevent->UID = 'bla';

        $valarm = VObject\Component::create('VALARM');
        $valarm->TRIGGER = '-P2W1D';
        $vevent->add($valarm);

        $vcalendar = VObject\Component::create('VCALENDAR');
        $vcalendar->add($vevent);

        $filter = array(
            'name' => 'VCALENDAR',
            'is-not-defined' => false,
            'time-range' => null,
            'prop-filters' => array(),
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'is-not-defined' => false,
                    'time-range' => null,
                    'prop-filters' => array(),
                    'comp-filters' => array(
                        array(
                            'name' => 'VALARM',
                            'is-not-defined' => false,
                            'prop-filters' => array(),
                            'comp-filters' => array(),
                            'time-range' => array(
                                'start' => new DateTime('2011-12-10'),
                                'end' => new DateTime('2011-12-20'),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $validator = new Sabre_CalDAV_CalendarQueryValidator();
        $this->assertTrue($validator->validate($vcalendar, $filter));

    }

}
