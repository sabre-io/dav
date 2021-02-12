<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

class ValidateFilterTest extends AbstractPluginTest
{
    /**
     * @param string      $input
     * @param array       $filters
     * @param string      $test
     * @param bool        $result
     * @param string|null $message
     * @dataProvider data
     */
    public function testFilter($input, $filters, $test, $result, $message = '')
    {
        if ($result) {
            $this->assertTrue($this->plugin->validateFilters($input, $filters, $test), $message);
        } else {
            $this->assertFalse($this->plugin->validateFilters($input, $filters, $test), $message);
        }
    }

    public function data()
    {
        $body1 = <<<HELLO
BEGIN:VCARD
VERSION:3.0
ORG:Company;
TITLE:Title
ITEM4.TEL:(111) 11 11 11
ITEM5.TEL:(6) 66 66 66 66
ITEM6.TEL:(77) 777 77 77
TEL;TYPE=IPHONE;TYPE=pref:(222) 22 22 22
TEL;TYPE=HOME:(33) 333 66 66
TEL;TYPE=WORK:(444) 44 44 44
TEL;TYPE=MAIN:(55) 555 55 55
UID:3151DE6A-BC35-4612-B340-B53A034A2B27
ITEM1.EMAIL:1111@111.com
ITEM2.EMAIL:bbbbb@bbbb.com
ITEM3.EMAIL:ccccc@ccccc.com
FN:First Last
N:Last;First;Middle;Dr
BDAY:1985-07-20
ADR;TYPE=HOME:;;Street;City;;3556;Montenegro
ADR;TYPE=WORK:;;Street\\nStreet2;Harkema;;35444;Australia
URL:http://google.com
END:VCARD
HELLO;

        // Check if TITLE is defined
        $filter1 =
            ['name' => 'title', 'is-not-defined' => false, 'param-filters' => [], 'text-matches' => []];

        // Check if FOO is defined
        $filter2 =
            ['name' => 'foo', 'is-not-defined' => false, 'param-filters' => [], 'text-matches' => []];

        // Check if TITLE is not defined
        $filter3 =
            ['name' => 'title', 'is-not-defined' => true, 'param-filters' => [], 'text-matches' => []];

        // Check if FOO is not defined
        $filter4 =
            ['name' => 'foo', 'is-not-defined' => true, 'param-filters' => [], 'text-matches' => []];

        // Check if TEL[TYPE] is defined
        $filter5 =
            [
                'name' => 'tel',
                'is-not-defined' => false,
                'test' => 'anyof',
                'param-filters' => [
                    [
                        'name' => 'type',
                        'is-not-defined' => false,
                        'text-match' => null,
                    ],
                ],
                'text-matches' => [],
            ];

        // Check if TEL[FOO] is defined
        $filter6 = $filter5;
        $filter6['param-filters'][0]['name'] = 'FOO';

        // Check if TEL[TYPE] is not defined
        $filter7 = $filter5;
        $filter7['param-filters'][0]['is-not-defined'] = true;

        // Check if TEL[FOO] is not defined
        $filter8 = $filter5;
        $filter8['param-filters'][0]['name'] = 'FOO';
        $filter8['param-filters'][0]['is-not-defined'] = true;

        // Combining property filters
        $filter9 = $filter5;
        $filter9['param-filters'][] = $filter6['param-filters'][0];

        $filter10 = $filter5;
        $filter10['param-filters'][] = $filter6['param-filters'][0];
        $filter10['test'] = 'allof';

        // Check if URL contains 'google'
        $filter11 =
            [
                'name' => 'url',
                'is-not-defined' => false,
                'test' => 'anyof',
                'param-filters' => [],
                'text-matches' => [
                    [
                        'match-type' => 'contains',
                        'value' => 'google',
                        'negate-condition' => false,
                        'collation' => 'i;octet',
                    ],
                ],
            ];

        // Check if URL contains 'bing'
        $filter12 = $filter11;
        $filter12['text-matches'][0]['value'] = 'bing';

        // Check if URL does not contain 'google'
        $filter13 = $filter11;
        $filter13['text-matches'][0]['negate-condition'] = true;

        // Check if URL does not contain 'bing'
        $filter14 = $filter11;
        $filter14['text-matches'][0]['value'] = 'bing';
        $filter14['text-matches'][0]['negate-condition'] = true;

        // Check if there is an EMAIL address that does not have the 111.com domain
        $filterEmailWithoutSpecificDomain = $filter11;
        $filterEmailWithoutSpecificDomain['name'] = 'email';
        $filterEmailWithoutSpecificDomain['text-matches'][0]['value'] = '@111.com';
        $filterEmailWithoutSpecificDomain['text-matches'][0]['negate-condition'] = true;

        // Param filter with text
        // Check there is a TEL;TYPE that contains WORK
        $filter15 = $filter5;
        $filter15['param-filters'][0]['text-match'] = [
            'match-type' => 'contains',
            'value' => 'WORK',
            'collation' => 'i;octet',
            'negate-condition' => false,
        ];

        // Check if there is a TEL;TYPE that does not contain WORK
        $filter16 = $filter15;
        $filter16['param-filters'][0]['text-match']['negate-condition'] = true;

        // Check there is a TEL;TYPE that does not contain OTHER
        // All TEL properties with a TYPE parameter match this
        $filterNoTelWithTypeOther = $filter16;
        $filterNoTelWithTypeOther['param-filters'][0]['text-match']['value'] = 'OTHER';

        // Param filter + text filter
        $filter17 = $filter5;

        // Matches if the VCard contains a TEL property that either has a TYPE property defined (-> true),
        // or that has a value containing 444 (true)
        $filter17['test'] = 'anyof';
        $filter17['text-matches'][] = [
            'match-type' => 'contains',
            'value' => '444',
            'collation' => 'i;octet',
            'negate-condition' => false,
        ];

        // Matches if the VCard contains a TEL property that has a TYPE property defined
        // AND that has a value NOT containing 444 -> there is 3 properties matching these criteria
        $filter18 = $filter17;
        $filter18['text-matches'][0]['negate-condition'] = true;

        $filter18['test'] = 'allof';

        return [
            // Basic filters
            [$body1, [$filter1], 'anyof', true],
            [$body1, [$filter2], 'anyof', false],
            [$body1, [$filter3], 'anyof', false],
            [$body1, [$filter4], 'anyof', true],

            // Combinations
            [$body1, [$filter1, $filter2], 'anyof', true],
            [$body1, [$filter1, $filter2], 'allof', false],
            [$body1, [$filter1, $filter4], 'anyof', true],
            [$body1, [$filter1, $filter4], 'allof', true],
            [$body1, [$filter2, $filter3], 'anyof', false],
            [$body1, [$filter2, $filter3], 'allof', false],

            // Basic parameters
            [$body1, [$filter5], 'anyof', true, 'TEL;TYPE is defined, so this should return true'],
            [$body1, [$filter6], 'anyof', false, 'TEL;FOO is not defined, so this should return false'],

            [$body1, [$filter7], 'anyof', false, 'TEL;TYPE is defined, so this should return false'],
            [$body1, [$filter8], 'anyof', true, 'TEL;FOO is not defined, so this should return true'],

            // Combined parameters
            [$body1, [$filter9], 'anyof', true],
            [$body1, [$filter10], 'anyof', false],

            // Text-filters
            [$body1, [$filter11], 'anyof', true],
            [$body1, [$filter12], 'anyof', false],
            [$body1, [$filter13], 'anyof', false],
            [$body1, [$filter14], 'anyof', true],
            [$body1, [$filterEmailWithoutSpecificDomain], 'anyof', true, 'EMAIL properties with other domain exists, so this should return true'],

            // Param filter with text-match
            [$body1, [$filter15], 'anyof', true, 'TEL;TYPE with value WORK exists, so this should return true'],
            [$body1, [$filter16], 'anyof', true, 'Some TEL;TYPE that do not match WORK exist. Match result is inverted, so this should return true'],
            [$body1, [$filterNoTelWithTypeOther], 'anyof', true, 'No TEL;TYPE contains OTHER. Match result is inverted, so this should return true'],

            // Param filter + text filter
            [$body1, [$filter17], 'anyof', true],
            [$body1, [$filter18], 'anyof', true],
        ];
    }
}
