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
     * Starts the DAV Server making sure the response state is reset.
     */
    public function start(): void
    {
        // TODO: Set statusText property via setStatus() or Reflection
        $this->httpResponse->setStatus(500);
        $this->httpResponse->setBody('');

        foreach (array_keys($this->httpResponse->getHeaders()) as $header) {
            $this->httpResponse->removeHeader($header);
        }

        parent::start();
    }
}
