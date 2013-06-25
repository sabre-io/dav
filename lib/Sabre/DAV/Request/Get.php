<?php

namespace Sabre\DAV\Request;

/**
 * This class represents a GET request.
 *
 * It is passed throughout the system to handle these.
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Get extends Request {

    /**
     * Returns the HTTP range header
     *
     * This method returns null if there is no well-formed HTTP range request
     * header or array($start, $end).
     *
     * The first number is the offset of the first byte in the range.
     * The second number is the offset of the last byte in the range.
     *
     * If the second offset is null, it should be treated as the offset of the last byte of the entity
     * If the first offset is null, the second offset should be used to retrieve the last x bytes of the entity
     *
     * @return array|null
     */
    public function getRange() {

        $range = $this->getHeader('range');
        if (is_null($range)) return null;

        // Matching "Range: bytes=1234-5678: both numbers are optional

        if (!preg_match('/^bytes=([0-9]*)-([0-9]*)$/i',$range,$matches)) return null;

        if ($matches[1]==='' && $matches[2]==='') return null;

        return [
            $matches[1]!==''?$matches[1]:null,
            $matches[2]!==''?$matches[2]:null,
        ];

    }

    /**
     * This method looks at the HTTP Accept: header, and which formats are
     * available, and then elects the most suitable format for a response.
     *
     * If no matching mimetype was found, null is returned.
     *
     * @param string[] $options an array of mimetypes.
     * @return string
     */
    public function negotiateContentType(array $available) {

        $acceptHeader = $this->getHeader('Accept');
        if (!$acceptHeader) {
            // Grabbing the first in the list.
            return reset($available);
        }

        $proposals = explode(',' , $acceptHeader);

        /**
         * This function loops through every element, and creates a new array
         * with 3 elements per item:
         * 1. mimeType
         * 2. quality (contents of q= parameter)
         * 3. index (the original order in the array)
         */
        array_walk(
            $proposals,

            function(&$value, $key) {

                $parts = explode(';', $value);
                $mimeType = trim($parts[0]);
                if (isset($parts[1]) && substr(trim($parts[1]),0,2)==='q=') {
                    $quality = substr(trim($parts[1]),2);
                } else {
                    $quality = 1;
                }

                $value = [$mimeType, $quality, $key];

            }
        );

        /**
         * This sorts the array based on quality first, and key-index second.
         */
        usort(
            $proposals,

            function($a, $b) {

                // Comparing quality
                $result = $a[1] - $b[1];
                if ($result === 0) {
                    // Comparing original index
                    $result = $a[2] - $b[2];
                }

                return $result;

            }

        );

        // Now we're left with a correctly ordered Accept: header, so we can
        // compare it to the available mimetypes.
        foreach($proposals as $proposal) {

            // If it's */* it means 'anything will wdo'
            if ($proposal[0] === '*/*') {
                return reset($available);
            }

            foreach($available as $availableItem) {
                if ($availableItem===$proposal[0]) {
                    return $availableItem;
                }
            }

        }


    }

}
