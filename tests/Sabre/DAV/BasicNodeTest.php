<?php

declare(strict_types=1);

namespace Sabre\DAV;

class BasicNodeTest extends \PHPUnit\Framework\TestCase
{
    public function testPut()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $file = new FileMock();
        $file->put('hi');
    }

    public function testGet()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $file = new FileMock();
        $file->get();
    }

    public function testGetSize()
    {
        $file = new FileMock();
        self::assertEquals(0, $file->getSize());
    }

    public function testGetETag()
    {
        $file = new FileMock();
        self::assertNull($file->getETag());
    }

    public function testGetContentType()
    {
        $file = new FileMock();
        self::assertNull($file->getContentType());
    }

    public function testDelete()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $file = new FileMock();
        $file->delete();
    }

    public function testSetName()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $file = new FileMock();
        $file->setName('hi');
    }

    public function testGetLastModified()
    {
        $file = new FileMock();
        // checking if lastmod is within the range of a few seconds
        $lastMod = $file->getLastModified();
        $compareTime = ($lastMod + 1) - time();
        self::assertTrue($compareTime < 3);
    }

    public function testGetChild()
    {
        $dir = new DirectoryMock();
        $file = $dir->getChild('mockfile');
        self::assertTrue($file instanceof FileMock);
    }

    public function testChildExists()
    {
        $dir = new DirectoryMock();
        self::assertTrue($dir->childExists('mockfile'));
    }

    public function testChildExistsFalse()
    {
        $dir = new DirectoryMock();
        self::assertFalse($dir->childExists('mockfile2'));
    }

    public function testGetChild404()
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);
        $dir = new DirectoryMock();
        $file = $dir->getChild('blabla');
    }

    public function testCreateFile()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $dir = new DirectoryMock();
        $dir->createFile('hello', 'data');
    }

    public function testCreateDirectory()
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $dir = new DirectoryMock();
        $dir->createDirectory('hello');
    }
}

class DirectoryMock extends Collection
{
    public function getName()
    {
        return 'mockdir';
    }

    public function getChildren()
    {
        return [new FileMock()];
    }
}

class FileMock extends File
{
    public function getName()
    {
        return 'mockfile';
    }
}
