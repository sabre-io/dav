<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

class FileTest extends AbstractTest
{
    public function getBackend()
    {
        \Sabre\TestUtil::clearTempDir();
        $backend = new File(\Sabre\TestUtil::SABRE_TEMPDIR.'/lockdb');

        return $backend;
    }

    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }
}
