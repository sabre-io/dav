<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;

/**
 * ResponseList property
 *
 * This class represents multiple {DAV:}response XML elements.
 * This is used by the Server class to encode items within a multistatus
 * response.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ResponseList extends DAV\Property {

    /**
     * Response objects.
     *
     * @var array
     */
    private $responses;

    /**
     * The only valid argument is a list of Sabre\DAV\Property\Response
     * objects.
     *
     * @param array $responses;
     */
    public function __construct($responses) {

        foreach($responses as $response) {
            if (!($response instanceof Response)) {
                throw new \InvalidArgumentException('You must pass an array of Sabre\DAV\Property\Response objects');
            }
        }
        $this->responses = $responses;

    }

    /**
     * Returns the list of Response properties.
     *
     * @return Response[]
     */
    public function getResponses() {

        return $this->responses;

    }

    /**
     * serialize
     *
     * @param DAV\Server $server
     * @param \DOMElement $dom
     * @return void
     */
    public function serialize(DAV\Server $server,\DOMElement $dom) {

        foreach($this->responses as $response) {
            $response->serialize($server, $dom);
        }

    }

    /**
     * Unserializes the property.
     *
     * This static method should return a an instance of this object.
     *
     * @param \DOMElement $prop
     * @param array $propertyMap
     * @return DAV\IProperty
     */
    public static function unserialize(\DOMElement $prop, array $propertyMap) {

        $xpath = new \DOMXPath( $prop->ownerDocument );
        $xpath->registerNamespace('d','DAV:');

        // Finding the 'response' element
        $xResponses = $xpath->evaluate(
            'd:response',
            $prop
        );

        $result = array();

        for($jj=0; $jj < $xResponses->length; $jj++) {

            $xResponse = $xResponses->item($jj);

            // Parsing 'href'
            $href = Href::unserialize($xResponse, $propertyMap);

            $properties = array();

            // Parsing 'propstat'
            $xPropstat = $xpath->query('d:propstat', $xResponse);

            for($ii=0; $ii < $xPropstat->length; $ii++) {

                // Parsing 'status'
                $status = $xpath->evaluate('string(d:status)', $xPropstat->item($ii));

                list($httpVersion, $statusCode, $statusText) = explode(' ', $status, 3);

                // Parsing 'prop'
                $properties[$statusCode] = DAV\XMLUtil::parseProperties($xPropstat->item($ii), $propertyMap);

            }

            $result[] = new Response($href->getHref(), $properties);

        }

        return new self($result);

    }


}
