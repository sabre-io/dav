<?php

class Sabre_CalDAV_CalendarQueryValidatorTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider provider
     */
    function testValid($icalObject, $filters, $outcome) {

        $validator = new Sabre_CalDAV_CalendarQueryValidator();

        // Wrapping filter in a VCALENDAR component filter, as this is always 
        // there anyway.
        $filters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array($filters),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );

        switch($outcome) {
            case 0 : 
                $this->assertFalse($validator->validate($icalObject, $filters));
                break;
            case 1 :
                $this->assertTrue($validator->validate($icalObject, $filters));
                break;
            case -1 :
                try { 
                    $validator->validate($icalObject, $filters);
                } catch (Sabre_DAV_Exception $e) {
                    // Success
                }
                break;

        }

    }

    function provider() {

        $blob1 = <<<yow
BEGIN:VCALENDAR
BEGIN:VEVENT
SUMMARY:hi
END:VEVENT
END:VCALENDAR
yow;

        $blob2 = <<<yow
BEGIN:VCALENDAR
BEGIN:VEVENT
SUMMARY:hi
BEGIN:VALARM
ACTION:DISPLAY
END:VALARM
END:VEVENT
END:VCALENDAR
yow;

        $blob3 = <<<yow
BEGIN:VCALENDAR
BEGIN:VEVENT
SUMMARY:hi
DTSTART;VALUE=DATE:2011-07-04
END:VEVENT
END:VCALENDAR
yow;

        $filter1 = array(
            'name' => 'VEVENT',
            'comp-filters' => array(),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        );
        $filter2 = $filter1;
        $filter2['name'] = 'VTODO';

        $filter3 = $filter1;
        $filter3['is-not-defined'] = true;

        $filter4 = $filter1;
        $filter4['name'] = 'VTODO';
        $filter4['is-not-defined'] = true;

        $filter5 = $filter1;
        $filter5['comp-filters'] = array(
            array(
                'name' => 'VALARM',
                'is-not-defined' => false,
                'comp-filters' => array(),
                'prop-filters' => array(),
                'time-range' => null,
            ), 
        );
        $filter6 = $filter1;
        $filter6['prop-filters'] = array(
            array(
                'name' => 'SUMMARY',
                'is-not-defined' => false,
                'param-filters' => array(),
                'time-range' => null,
                'text-match' => null,
            ), 
        );
        $filter7 = $filter6;
        $filter7['prop-filters'][0]['name'] = 'DESCRIPTION';

        $filter8 = $filter6;
        $filter8['prop-filters'][0]['is-not-defined'] = true;

        $filter9 = $filter7;
        $filter9['prop-filters'][0]['is-not-defined'] = true;

        $filter10 = $filter5;
        $filter10['prop-filters'] = $filter6['prop-filters'];

        // Param filters
        $filter11 = $filter1;
        $filter11['prop-filters'] = array(
            array(
                'name' => 'DTSTART',
                'is-not-defined' => false,
                'param-filters' => array(
                    array(
                        'name' => 'VALUE',
                        'is-not-defined' => false,
                        'text-match' => null,
                    ),
                ),
                'time-range' => null,
                'text-match' => null,
            ),
        );

        $filter12 = $filter11;
        $filter12['prop-filters'][0]['param-filters'][0]['name'] = 'TZID';

        $filter13 = $filter11;
        $filter13['prop-filters'][0]['param-filters'][0]['is-not-defined'] = true;

        $filter14 = $filter12;
        $filter14['prop-filters'][0]['param-filters'][0]['is-not-defined'] = true; 

        // Param text filter
        $filter15 = $filter11;
        $filter15['prop-filters'][0]['param-filters'][0]['text-match'] = array(
            'collation' => 'i;ascii-casemap',
            'value' => 'dAtE',
            'negate-condition' => false,
        ); 
        $filter16 = $filter15;
        $filter16['prop-filters'][0]['param-filters'][0]['text-match']['collation'] = 'i;octet'; 
        
        $filter17 = $filter15;
        $filter17['prop-filters'][0]['param-filters'][0]['text-match']['negate-condition'] = true; 

        $filter18 = $filter15;
        $filter18['prop-filters'][0]['param-filters'][0]['text-match']['negate-condition'] = true; 
        $filter18['prop-filters'][0]['param-filters'][0]['text-match']['collation'] = 'i;octet'; 

        // prop + text
        $filter19 = $filter5;
        $filter19['comp-filters'][0]['prop-filters'] = array(
            array(
                'name' => 'action',
                'is-not-defined' => false,
                'time-range' => null,
                'param-filters' => array(),
                'text-match' => array( 
                    'collation' => 'i;ascii-casemap',
                    'value' => 'display',
                    'negate-condition' => false,
                ),
            ),
        ); 

        return array(
            // Component check
            
            array($blob1, $filter1, 1),
            array($blob1, $filter2, 0),
            array($blob1, $filter3, 0),
            array($blob1, $filter4, 1),

            // Subcomponent check
            array($blob1, $filter5, 0),
            array($blob2, $filter5, 1),

            // Property check
            array($blob1, $filter6, 1),
            array($blob1, $filter7, 0),
            array($blob1, $filter8, 0),
            array($blob1, $filter9, 1),
            
            // Subcomponent + property
            array($blob2, $filter10, 1),

            // Param filter
            array($blob3, $filter11, 1),
            array($blob3, $filter12, 0),
            array($blob3, $filter13, 0),
            array($blob3, $filter14, 1),

            // Param + text
            array($blob3, $filter15, 1),
            array($blob3, $filter16, 0),
            array($blob3, $filter17, 0),
            array($blob3, $filter18, 1),

            // Prop + text
            array($blob2, $filter19, 1),
        );

    } 

}
