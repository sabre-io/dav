<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

class AbstractPDOMySQLTest extends AbstractPDOTestCase
{
    public $driver = 'mysql';
}
