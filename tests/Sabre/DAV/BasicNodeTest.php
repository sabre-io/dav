<?php

declare(strict_types=1);

namespace Sabre\DAV;

class BasicNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testPut()
    {
        $file = new FileMock();
        $file->put('hi');
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testGet()
    {
        $file = new FileMock();
        $file->get();
    }

    public function testGetSize()
    {
        $file = new FileMock();
        $this->assertEquals(0, $file->getSize());
    }

    public function testGetETag()
    {
        $file = new FileMock();
        $this->assertNull($file->getETag());
    }

    public function testGetContentType()
    {
        $file = new FileMock();
        $this->assertNull($file->getContentType());
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testDelete()
    {
        $file = new FileMock();
        $file->delete();
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testSetName()
    {
        $file = new FileMock();
        $file->setName('hi');
    }

    public function testGetLastModified()
    {
        $file = new FileMock();
        // checking if lastmod is within the range of a few seconds
        $lastMod = $file->getLastModified();
        $compareTime = ($lastMod + 1) - time();
        $this->assertTrue($compareTime < 3);
    }

    public function testGetChild()
    {
        $dir = new DirectoryMock();
        $file = $dir->getChild('mockfile');
        $this->assertTrue($file instanceof FileMock);
    }

    public function testChildExists()
    {
        $dir = new DirectoryMock();
        $this->assertTrue($dir->childExists('mockfile'));
    }

    public function testChildExistsFalse()
    {
        $dir = new DirectoryMock();
        $this->assertFalse($dir->childExists('mockfile2'));
    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotFound
     */
    public function testGetChild404()
    {
        $dir = new DirectoryMock();
        $file = $dir->getChild('blabla');
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testCreateFile()
    {
        $dir = new DirectoryMock();
        $dir->createFile('hello', 'data');
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testCreateDirectory()
    {
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
