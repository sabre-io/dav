<?php

class Sabre_CalDAV_CalendarQueryVAlarmTest extends PHPUnit_Framework_TestCase {

    /**
     * This test is specifically for a time-range query on a VALARM, contained 
     * in a VEVENT that's recurring
     */
    function testValarm() {

        $vevent = Sabre_VObject_Component::create('VEVENT');
        $vevent->RRULE = 'FREQ=MONTHLY';
        $vevent->DTSTART = '20120101T120000Z';
        $vevent->UID = 'bla';

        $valarm = Sabre_VObject_Component::create('VALARM');
        $valarm->TRIGGER = '-P15D';
        $vevent->add($valarm);

        $vcalendar = Sabre_VObject_Component::create('VCALENDAR');
        $vcalendar->add($vevent);

        $filter = array(
            'name' => 'VCALENDAR',
            'is-not-defined' => false, 
            'time-range' => null,
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'is-not-defined' => false,
                    'time-range' => null,
                    'comp-filters' => array(
                        array(
                            'name' => 'VALARM',
                            'is-not-defined' => false,
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

    }

}

?>
