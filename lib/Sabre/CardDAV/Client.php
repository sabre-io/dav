<?php

namespace Sabre\CardDAV;

use Sabre\DAV\Client as BaseClient;

class Client extends BaseClient
{
    public function addressBookMultiGet($url, array $contacts)
    {
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->setIndent(4);
        $xml->startDocument('1.0', 'utf-8');
            $xml->startElement('a:addressbook-multiget');
                $xml->writeAttribute('xmlns:d', 'DAV:');
                $xml->writeAttribute('xmlns:a', 'urn:ietf:params:xml:ns:carddav');
                $xml->startElement('d:prop');
                    $xml->writeElement('d:getetag');
                    $xml->writeElement('a:address-data');
                $xml->endElement();

                foreach ($contacts as $contact) {
                    $xml->writeElement('d:href', $contact);
                }

            $xml->endElement();
        $xml->endDocument();

        $response = $this->request('REPORT', $url, $xml->outputMemory(), array(
            'Content-Type' => 'application/xml'
        ));

        $result = $this->parseMultiStatus($response['body']);

        $newResult = array();

        foreach ($result as $href => $statusList) {
            $newResult[$href] = isset($statusList[200]) ? $statusList[200] : array();
        }

        return $newResult;
    }
}
