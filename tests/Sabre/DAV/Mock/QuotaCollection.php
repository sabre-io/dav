<?php

declare(strict_types=1);

namespace Sabre\DAV\Mock;

use Sabre\DAV;

/**
 * Mock Collection with Quota support.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Aleksander Machniak
 * @license http://sabre.io/license/ Modified BSD License
 */
class QuotaCollection extends Collection implements DAV\IQuota
{
    public $available = 0;
    public $used = 0;

    /**
     * Returns the quota information.
     *
     * This method MUST return an array with 2 values, the first being the total used space,
     * the second the available space (in bytes)
     */
    public function getQuotaInfo()
    {
        return [$this->used, $this->available];
    }
}
