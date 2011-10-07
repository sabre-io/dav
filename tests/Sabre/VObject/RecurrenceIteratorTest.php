<?php

class Sabre_VObject_RecurrenceIteratorTest extends PHPUnit_Framework_TestCase {

    function testWeekly() {

        $ev = new Sabre_VObject_Component('VEVENT');
        $ev->RRULE = 'FREQ=WEEKLY;INTERVAL=2;COUNT=10';
        $dtStart = new Sabre_VObject_Element_DateTime('DTSTART');
        $dtStart->setDateTime(new DateTime('2011-10-07'),Sabre_VObject_Element_DateTime::UTC);

        $ev->add($dtStart);

        $it = new Sabre_VObject_RecurrenceIterator($ev);

        $this->assertEquals('weekly', $it->frequency);
        $this->assertEquals(2, $it->interval);
        $this->assertEquals(10, $it->count);

        // Max is to prevent overflow 
        $max = 12;
        $result = array();
        foreach($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            array(
                new DateTime('2011-10-07', $tz),
                new DateTime('2011-10-21', $tz),
                new DateTime('2011-11-04', $tz),
                new DateTime('2011-11-18', $tz),
                new DateTime('2011-12-02', $tz),
                new DateTime('2011-12-16', $tz),
                new DateTime('2011-12-30', $tz),
                new DateTime('2012-01-13', $tz),
                new DateTime('2012-01-27', $tz),
                new DateTime('2012-02-10', $tz),
            ),
            $result
        );

    }

    function testWeeklyByDay() {

        $ev = new Sabre_VObject_Component('VEVENT');
        $ev->RRULE = 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,WE,FR;WKST=SU';
        $dtStart = new Sabre_VObject_Element_DateTime('DTSTART');
        $dtStart->setDateTime(new DateTime('2011-10-07'),Sabre_VObject_Element_DateTime::UTC);

        $ev->add($dtStart);

        $it = new Sabre_VObject_RecurrenceIterator($ev);

        $this->assertEquals('weekly', $it->frequency);
        $this->assertEquals(2, $it->interval);
        $this->assertEquals(array('TU','WE','FR'), $it->byDay);
        $this->assertEquals('SU', $it->weekStart); 

        // Grabbing the next 12 items
        $max = 12;
        $result = array();
        foreach($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            array(
                new DateTime('2011-10-07', $tz),
                new DateTime('2011-10-18', $tz),
                new DateTime('2011-10-19', $tz),
                new DateTime('2011-10-21', $tz),
                new DateTime('2011-11-01', $tz),
                new DateTime('2011-11-02', $tz),
                new DateTime('2011-11-04', $tz),
                new DateTime('2011-11-15', $tz),
                new DateTime('2011-11-16', $tz),
                new DateTime('2011-11-18', $tz),
                new DateTime('2011-11-29', $tz),
                new DateTime('2011-11-30', $tz),
            ),
            $result
        );

    }

}

