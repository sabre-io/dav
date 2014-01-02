<?php

namespace Sabre\HTTP;

/**
 * HTTP Response Mock object
 *
 * This class exists to make the transition to sabre/http easier.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ResponseMock extends Response {

    /**
     * Making these public.
     */
    public $body;
    public $status;
    public $headers = [];

    /**
     * Overriding this so nothing is ever echo'd.
     *
     * @return void
     */
    public function send() {

    }

}
