<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

class PDOSqliteTest extends AbstractPDOTestCaseCase
{
    public $driver = 'sqlite';
}
