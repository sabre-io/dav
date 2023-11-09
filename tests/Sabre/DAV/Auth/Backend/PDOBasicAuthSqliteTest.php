<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

class PDOBasicAuthSqliteTest extends AbstractPDOBasicAuthTestCase
{
    public $driver = 'sqlite';
}
