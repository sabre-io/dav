<?php

declare(strict_types=1);

namespace Sabre\DAV\Property;

use Sabre\DAV;
use Sabre\HTTP;

class SupportedReportSetTest extends DAV\AbstractServer
{
    public function sendPROPFIND($body)
    {
        $serverVars = [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'PROPFIND',
            'HTTP_DEPTH' => '0',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($body);

        $this->server->httpRequest = ($request);
        $this->server->exec();
    }

    public function testNoReports()
    {
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supported-report-set />
  </d:prop>
</d:propfind>';

        $this->sendPROPFIND($xml);

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'We expected a multi-status response. Full response body: '.$bodyAsString);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", 'xmlns\\1="urn:DAV"', $bodyAsString);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop');
        self::assertEquals(1, count($data), 'We expected 1 \'d:prop\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set');
        self::assertEquals(1, count($data), 'We expected 1 \'d:supported-report-set\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        self::assertEquals(1, count($data), 'We expected 1 \'d:status\' element');

        self::assertEquals('HTTP/1.1 200 OK', (string) $data[0], 'The status for this property should have been 200');
    }

    /**
     * @depends testNoReports
     */
    public function testCustomReport()
    {
        // Intercepting the report property
        $this->server->on('propFind', function (DAV\PropFind $propFind, DAV\INode $node) {
            if ($prop = $propFind->get('{DAV:}supported-report-set')) {
                $prop->addReport('{http://www.rooftopsolutions.nl/testnamespace}myreport');
                $prop->addReport('{DAV:}anotherreport');
            }
        }, 200);

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supported-report-set />
  </d:prop>
</d:propfind>';

        $this->sendPROPFIND($xml);

        $bodyAsString = $this->response->getBodyAsString();
        self::assertEquals(207, $this->response->status, 'We expected a multi-status response. Full response body: '.$bodyAsString);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", 'xmlns\\1="urn:DAV"', $bodyAsString);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');
        $xml->registerXPathNamespace('x', 'http://www.rooftopsolutions.nl/testnamespace');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop');
        self::assertEquals(1, count($data), 'We expected 1 \'d:prop\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set');
        self::assertEquals(1, count($data), 'We expected 1 \'d:supported-report-set\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report');
        self::assertEquals(2, count($data), 'We expected 2 \'d:supported-report\' elements');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report/d:report');
        self::assertEquals(2, count($data), 'We expected 2 \'d:report\' elements');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report/d:report/x:myreport');
        self::assertEquals(1, count($data), 'We expected 1 \'x:myreport\' element. Full body: '.$bodyAsString);

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report/d:report/d:anotherreport');
        self::assertEquals(1, count($data), 'We expected 1 \'d:anotherreport\' element. Full body: '.$bodyAsString);

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        self::assertEquals(1, count($data), 'We expected 1 \'d:status\' element');

        self::assertEquals('HTTP/1.1 200 OK', (string) $data[0], 'The status for this property should have been 200');
    }
}
