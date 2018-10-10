<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

class PDOPgSqlTest extends PDOTest
{
    public $driver = 'pgsql';
}
