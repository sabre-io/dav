<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

abstract class PDOTest extends AbstractTest
{
    use \Sabre\DAV\DbTestHelperTrait;

    public function getBackend()
    {
        $this->dropTables('locks');
        $this->createSchema('locks');

        $pdo = $this->getPDO();

        return new PDO($pdo);
    }
}
