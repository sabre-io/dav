<?php

class Sabre_VObject_RecurrenceIteratorInfiniteLoopProblemTest extends PHPUnit_Framework_TestCase {

    /**
     * This bug came from a Fruux customer. This would result in a never-ending 
     * request.   
     */
    function testFastForwardTooFar() {

        $ev = Sabre_VObject_Component::create('VEVENT');
        $ev->DTSTART = '20090420T180000Z';
        $ev->RRULE = 'FREQ=WEEKLY;BYDAY=MO;UNTIL=20090704T205959Z;INTERVAL=1';

        $this->assertFalse($ev->isInTimeRange(new DateTime('2012-01-01 12:00:00'),new DateTime('3000-01-01 00:00:00')));

    }

}
