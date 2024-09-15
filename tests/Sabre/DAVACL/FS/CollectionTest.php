<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

use Sabre\TestUtil;

class CollectionTest extends FileTest
{
    public function setup(): void
    {
        parent::setup();
        $this->path = TestUtil::SABRE_TEMPDIR;
        $this->sut = new Collection($this->path, $this->acl, $this->owner);
    }

    public function teardown(): void
    {
        TestUtil::clearTempDir();
    }

    public function testGetChildFile()
    {
        file_put_contents(TestUtil::SABRE_TEMPDIR.'/file.txt', 'hello');
        $child = $this->sut->getChild('file.txt');
        self::assertInstanceOf(\Sabre\DAVACL\FS\File::class, $child);

        self::assertEquals('file.txt', $child->getName());
        self::assertEquals($this->acl, $child->getACL());
        self::assertEquals($this->owner, $child->getOwner());
    }

    public function testGetChildDirectory()
    {
        mkdir(TestUtil::SABRE_TEMPDIR.'/dir');
        $child = $this->sut->getChild('dir');
        self::assertInstanceOf(\Sabre\DAVACL\FS\Collection::class, $child);

        self::assertEquals('dir', $child->getName());
        self::assertEquals($this->acl, $child->getACL());
        self::assertEquals($this->owner, $child->getOwner());
    }
}
