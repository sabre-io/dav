<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

class AbstractPDOSqliteTest extends AbstractPDOTestCase
{
    public $driver = 'sqlite';
}
