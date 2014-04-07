<?php

namespace Sabre\HTTP;

/**
 * HTTP Response Mock object
 *
 * This class exists to make the transition to sabre/http easier.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SapiMock extends Sapi {

    /**
     * Overriding this so nothing is ever echo'd.
     *
     * @return void
     */
    static public function sendResponse(\Sabre\HTTP\ResponseInterface $r) {

    }

}
