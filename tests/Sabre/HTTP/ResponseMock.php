<?php

namespace Sabre\HTTP;

/**
 * HTTP Response Mock object
 *
 * This class exists to make the transition to sabre/http easier.
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ResponseMock extends Response {

    /**
     * Making this one public.
     */
    public $body;

}
