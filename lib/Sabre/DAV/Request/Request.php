<?php

namespace Sabre\DAV\Request;

use Sabre\HTTP;

/**
 * This class reperesents a request.
 *
 * Individual request-types should extend this with more information
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Request {

    /**
     * Reference to the real HTTP request
     *
     * @var HTTP\Request
     */
    public $headers;

    /**
     * Creates the request object.
     *
     * @param string $path
     * @return void
     */
    public function __construct(array $headers = []) {

        foreach($headers as $k=>$v) {
            $this->headers[strtolower($k)] = $v;
        }

    }

    /**
     * Returns the contents of a HTTP request header.
     *
     * If the header was not supplied, null is returned.
     *
     * @param string $name
     * @return null|string
     */
    public function getHeader($name) {

        $name = strtolower($name);
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }

    }

}
