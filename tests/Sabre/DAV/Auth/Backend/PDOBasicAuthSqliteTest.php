<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

class PDOBasicAuthSqliteTest extends AbstractPDOBasicAuthTest
{
    public $driver = 'sqlite';
}
