<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

class PDOSqliteTest extends AbstractPDOTestCase
{
    public $driver = 'sqlite';
}
