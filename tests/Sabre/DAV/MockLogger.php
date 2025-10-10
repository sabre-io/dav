<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Psr\Log\AbstractLogger;

/**
 * The MockLogger is a simple PSR-3 implementation that we can use to test
 * whether things get logged correctly.
 *
 * @copyright Copyright (C) fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class MockLogger extends AbstractLogger
{
    public $logs = [];

    /**
     * Logs with an arbitrary level.
     *
     * @param string $message
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            $level,
            $message,
            $context,
        ];
    }
}
