<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * DAV Server Mock object.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ServerMock extends Server
{
    /**
     * Starts the DAV Server making sure the mocked response state is reset.
     */
    public function start()
    {
        $this->httpResponse->reset();

        parent::start();
    }
}
