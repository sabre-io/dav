<?php

namespace Sabre\CalDAV;

use Sabre\HTTP\Request;

class JCalTransformTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $caldavCalendars = [
        [
            'id' => 1,
            'principaluri' => 'principals/user1',
            'uri' => 'foo',
        ] 
    ];
    protected $caldavCalendarObjects = [
        1 => [
            'bar.ics' => [
                'uri' => 'bar.ics',
                'calendarid' => 1,
                'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
            ]
        ],
    ];

    function testMultiGet() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
    <d:prop>
        <c:calendar-data content-type="application/calendar+json" />
    </d:prop>
    <d:href>/calendars/user1/foo/bar.ics</d:href>
</c:calendar-multiget>
XML;

        $headers = [];
        $request = new Request('REPORT', '/calendars/foo', $headers, $xml);

        $response = $this->request($request);

        $this->assertEquals(207, $response->getStatus());

        $body = $response->getBodyAsString();

        // Getting from the xml body to the actual returned data is 
        // unfortunately very convoluted.
        $responses = \Sabre\DAV\Property\ResponseList::unserialize(
            \Sabre\DAV\XMLUtil::loadDOMDocument($body)->firstChild
        , $this->server->propertyMap);

        $responses = $responses->getResponses();
        $this->assertEquals(1, count($responses));

        $response = $responses[0]->getResponseProperties()[200]["{urn:ietf:params:xml:ns:caldav}calendar-data"];
        
        $response = json_decode($response,true);
        if (json_last_error()) {
            $this->fail('Json decoding error: ' . json_last_error_msg());
        }
        $this->assertEquals(
            [
                'vcalendar',
                [],
                [
                    [
                        'vevent',
                        [],
                        [],
                    ],
                ],
            ],
            $response
        );

    }

}
