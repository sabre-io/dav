<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

class CollectionTest extends FileTest
{
    public function setup(): void
    {
        $this->path = SABRE_TEMPDIR;
        $this->sut = new Collection($this->path, $this->acl, $this->owner);
    }

    public function tearDown()
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testGetChildFile()
    {
        file_put_contents(SABRE_TEMPDIR.'/file.txt', 'hello');
        $child = $this->sut->getChild('file.txt');
        $this->assertInstanceOf('Sabre\\DAVACL\\FS\\File', $child);

        $this->assertEquals('file.txt', $child->getName());
        $this->assertEquals($this->acl, $child->getACL());
        $this->assertEquals($this->owner, $child->getOwner());
    }

    public function testGetChildDirectory()
    {
        mkdir(SABRE_TEMPDIR.'/dir');
        $child = $this->sut->getChild('dir');
        $this->assertInstanceOf('Sabre\\DAVACL\\FS\\Collection', $child);

        $this->assertEquals('dir', $child->getName());
        $this->assertEquals($this->acl, $child->getACL());
        $this->assertEquals($this->owner, $child->getOwner());
    }
}
