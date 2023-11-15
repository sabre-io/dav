<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

class AbstractPDOPgSqlTest extends AbstractPDOTestCase
{
    public $driver = 'pgsql';
}
