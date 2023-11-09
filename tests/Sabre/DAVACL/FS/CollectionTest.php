<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

class CollectionTest extends FileTest
{
    public function setup(): void
    {
        parent::setup();
        $this->path = SABRE_TEMPDIR;
        $this->sut = new Collection($this->path, $this->acl, $this->owner);
    }

    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testGetChildFile()
    {
        file_put_contents(SABRE_TEMPDIR.'/file.txt', 'hello');
        $child = $this->sut->getChild('file.txt');
        self::assertInstanceOf('Sabre\\DAVACL\\FS\\File', $child);

        self::assertEquals('file.txt', $child->getName());
        self::assertEquals($this->acl, $child->getACL());
        self::assertEquals($this->owner, $child->getOwner());
    }

    public function testGetChildDirectory()
    {
        mkdir(SABRE_TEMPDIR.'/dir');
        $child = $this->sut->getChild('dir');
        self::assertInstanceOf('Sabre\\DAVACL\\FS\\Collection', $child);

        self::assertEquals('dir', $child->getName());
        self::assertEquals($this->acl, $child->getACL());
        self::assertEquals($this->owner, $child->getOwner());
    }
}
