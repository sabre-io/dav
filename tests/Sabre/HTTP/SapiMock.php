<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * HTTP Response Mock object.
 *
 * This class exists to make the transition to sabre/http easier.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SapiMock extends Sapi
{
    public static $sent = 0;

    /**
     * Overriding this so nothing is ever echo'd.
     */
    public static function sendResponse(ResponseInterface $response)
    {
        ++self::$sent;
    }
}
