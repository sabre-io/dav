<?php

class Sabre_VObject_Component_VTodoTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider timeRangeTestData
     */
    public function testInTimeRange(Sabre_VObject_Component_VTodo $vtodo,$start,$end,$outcome) {

        $this->assertEquals($outcome, $vtodo->isInTimeRange($start, $end));

    }

    public function timeRangeTestData() {

        $tests = array();

        $vtodo = Sabre_VObject_Component::createByName('VTODO');
        $vtodo->DTSTART = '20111223T120000Z';
        $tests[] = array($vtodo, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo, new DateTime('2011-01-01'), new DateTime('2011-11-01'), false);

        $vtodo2 = clone $vtodo;
        $vtodo2->DURATION = 'P1D';
        $tests[] = array($vtodo2, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo2, new DateTime('2011-01-01'), new DateTime('2011-11-01'), false);

        $vtodo3 = clone $vtodo;
        $vtodo3->DUE = '20111225';
        $tests[] = array($vtodo3, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo3, new DateTime('2011-01-01'), new DateTime('2011-11-01'), false);

        $vtodo4 = Sabre_VObject_Component::createByName('VTODO');
        $vtodo4->DUE = '20111225';
        $tests[] = array($vtodo4, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo4, new DateTime('2011-01-01'), new DateTime('2011-11-01'), false);

        $vtodo5 = Sabre_VObject_Component::createByName('VTODO');
        $vtodo5->COMPLETED = '20111225';
        $tests[] = array($vtodo5, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo5, new DateTime('2011-01-01'), new DateTime('2011-11-01'), false);

        $vtodo6 = Sabre_VObject_Component::createByName('VTODO');
        $vtodo6->CREATED = '20111225';
        $tests[] = array($vtodo6, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo6, new DateTime('2011-01-01'), new DateTime('2011-11-01'), false);

        $vtodo7 = Sabre_VObject_Component::createByName('VTODO');
        $vtodo7->CREATED = '20111225';
        $vtodo7->COMPLETED = '20111226';
        $tests[] = array($vtodo7, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo7, new DateTime('2011-01-01'), new DateTime('2011-11-01'), false);

        $vtodo7 = Sabre_VObject_Component::createByName('VTODO');
        $tests[] = array($vtodo7, new DateTime('2011-01-01'), new DateTime('2012-01-01'), true);
        $tests[] = array($vtodo7, new DateTime('2011-01-01'), new DateTime('2011-11-01'), true);

        return $tests;

    }

}

?>
