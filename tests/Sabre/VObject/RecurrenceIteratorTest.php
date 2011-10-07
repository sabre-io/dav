<?php

class Sabre_VObject_RecurrenceIteratorTest extends PHPUnit_Framework_TestCase {

    function testDaily() {

        $ev = new Sabre_VObject_Component('VEVENT');
        $ev->RRULE = 'FREQ=DAILY;INTERVAL=3;UNTIL=20111025T000000Z';
        $dtStart = new Sabre_VObject_Element_DateTime('DTSTART');
        $dtStart->setDateTime(new DateTime('2011-10-07'),Sabre_VObject_Element_DateTime::UTC);

        $ev->add($dtStart);

        $it = new Sabre_VObject_RecurrenceIterator($ev);

        $this->assertEquals('daily', $it->frequency);
        $this->assertEquals(3, $it->interval);
        $this->assertEquals(new DateTime('2011-10-25', new DateTimeZone('UTC')), $it->until);

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
                new DateTime('2011-10-10', $tz),
                new DateTime('2011-10-13', $tz),
                new DateTime('2011-10-16', $tz),
                new DateTime('2011-10-19', $tz),
                new DateTime('2011-10-22', $tz),
                new DateTime('2011-10-25', $tz),
            ),
            $result
        );

    }

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

    function testMonthly() {

        $ev = new Sabre_VObject_Component('VEVENT');
        $ev->RRULE = 'FREQ=MONTHLY;INTERVAL=3;COUNT=5';
        $dtStart = new Sabre_VObject_Element_DateTime('DTSTART');
        $dtStart->setDateTime(new DateTime('2011-12-05'),Sabre_VObject_Element_DateTime::UTC);

        $ev->add($dtStart);

        $it = new Sabre_VObject_RecurrenceIterator($ev);

        $this->assertEquals('monthly', $it->frequency);
        $this->assertEquals(3, $it->interval);
        $this->assertEquals(5, $it->count); 

        $max = 14;
        $result = array();
        foreach($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            array(
                new DateTime('2011-12-05', $tz),
                new DateTime('2012-03-05', $tz),
                new DateTime('2012-06-05', $tz),
                new DateTime('2012-09-05', $tz),
                new DateTime('2012-12-05', $tz),
            ),
            $result
        );


    }

    function testMonthlyEndOfMonth() {

        $ev = new Sabre_VObject_Component('VEVENT');
        $ev->RRULE = 'FREQ=MONTHLY;INTERVAL=2;COUNT=12';
        $dtStart = new Sabre_VObject_Element_DateTime('DTSTART');
        $dtStart->setDateTime(new DateTime('2011-12-31'),Sabre_VObject_Element_DateTime::UTC);

        $ev->add($dtStart);

        $it = new Sabre_VObject_RecurrenceIterator($ev);

        $this->assertEquals('monthly', $it->frequency);
        $this->assertEquals(2, $it->interval);
        $this->assertEquals(12, $it->count); 

        $max = 14;
        $result = array();
        foreach($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            array(
                new DateTime('2011-12-31', $tz),
                new DateTime('2012-08-31', $tz),
                new DateTime('2012-10-31', $tz),
                new DateTime('2012-12-31', $tz),
                new DateTime('2013-08-31', $tz),
                new DateTime('2013-10-31', $tz),
                new DateTime('2013-12-31', $tz),
                new DateTime('2014-08-31', $tz),
                new DateTime('2014-10-31', $tz),
                new DateTime('2014-12-31', $tz),
                new DateTime('2015-08-31', $tz),
                new DateTime('2015-10-31', $tz),
            ),
            $result
        );


    }

    function testMonthlyByMonthDay() {

        $ev = new Sabre_VObject_Component('VEVENT');
        $ev->RRULE = 'FREQ=MONTHLY;INTERVAL=5;COUNT=9;BYMONTHDAY=1,31,-7';
        $dtStart = new Sabre_VObject_Element_DateTime('DTSTART');
        $dtStart->setDateTime(new DateTime('2011-01-01'),Sabre_VObject_Element_DateTime::UTC);

        $ev->add($dtStart);

        $it = new Sabre_VObject_RecurrenceIterator($ev);

        $this->assertEquals('monthly', $it->frequency);
        $this->assertEquals(5, $it->interval);
        $this->assertEquals(9, $it->count);
        $this->assertEquals(array(1, 31, -7), $it->byMonthDay);

        $max = 14;
        $result = array();
        foreach($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            array(
                new DateTime('2011-01-01', $tz),
                new DateTime('2011-01-25', $tz),
                new DateTime('2011-01-31', $tz),
                new DateTime('2011-06-01', $tz),
                new DateTime('2011-06-24', $tz),
                new DateTime('2011-11-01', $tz),
                new DateTime('2011-11-24', $tz),
                new DateTime('2012-04-01', $tz),
                new DateTime('2012-04-24', $tz),
            ),
            $result
        );

    }

}

