<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

class PDOPgSqlTest extends AbstractPDOTestCaseCase
{
    public $driver = 'pgsql';
}
