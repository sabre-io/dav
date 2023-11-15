<?php

declare(strict_types=1);

namespace Sabre\DAV\FSExt;

class DirectoryTest extends \PHPUnit\Framework\TestCase
{
    public function create()
    {
        return new Directory(\Sabre\TestUtil::SABRE_TEMPDIR);
    }

    public function testCreate()
    {
        $dir = $this->create();
        self::assertEquals(basename(\Sabre\TestUtil::SABRE_TEMPDIR), $dir->getName());
    }

    public function testChildExistDot()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $dir = $this->create();
        $dir->childExists('..');
    }
}
