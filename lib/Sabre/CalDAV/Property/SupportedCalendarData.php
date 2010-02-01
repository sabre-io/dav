<?php

class Sabre_CalDAV_Property_SupportedCalendarData extends Sabre_DAV_Property {

    function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;

        $prefix = $node->lookupPrefix('urn:ietf:params:xml:ns:caldav');

        $caldata = $doc->createElement($prefix . ':calendar-data');
        $caldata->setAttribute('content-type','text/calendar');
        $caldata->setAttribute('version','2.0');

        $node->appendChild($caldata); 

    }

}
