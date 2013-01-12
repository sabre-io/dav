<?php

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * TooMuchMatches
 *
 * This exception is emited for the {DAV:}number-of-matches-within-limits
 * post-condition, as defined in rfc6578, section 3.2.
 *
 * http://tools.ietf.org/html/rfc6578#section-3.2
 *
 * This is emitted in cases where the response to a {DAV:}sync-collection would
 * generate more results than the implementation is willing to send back.
 *
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class TooMuchMatches extends DAV\Forbidden {

    /**
     * This method allows the exception to include additional information into the WebDAV error response
     *
     * @param DAV\Server $server
     * @param \DOMElement $errorNode
     * @return void
     */
    public function serialize(DAV\Server $server,\DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:','d:number-of-matches-within-limits');
        $errorNode->appendChild($error);

    }

}
