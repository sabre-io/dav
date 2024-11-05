<?php

declare(strict_types=1);

namespace Sabre\DAV\FSExt;

class DirectoryTest extends \PHPUnit\Framework\TestCase
{
    public function create()
    {
        return new Directory(SABRE_TEMPDIR);
    }

    public function testCreate()
    {
        $dir = $this->create();
        self::assertEquals(basename(SABRE_TEMPDIR), $dir->getName());
    }

    public function testChildExistDot()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $dir = $this->create();
        $dir->childExists('..');
    }
}
