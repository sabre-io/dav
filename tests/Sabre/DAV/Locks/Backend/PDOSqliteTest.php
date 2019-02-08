<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

class PDOSqliteTest extends PDOTest
{
    public $driver = 'sqlite';
}
