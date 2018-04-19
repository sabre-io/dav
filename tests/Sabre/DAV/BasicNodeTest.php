<?php declare (strict_types=1);

namespace Sabre\DAV;

class BasicNodeTest extends \PHPUnit\Framework\TestCase {

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testPut() {

        $file = new FileMock();
        $file->put('hi');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testGet() {

        $file = new FileMock();
        $file->get();

    }

    function testGetSize() {

        $file = new FileMock();
        $this->assertEquals(0, $file->getSize());

    }


    function testGetETag() {

        $file = new FileMock();
        $this->assertNull($file->getETag());

    }

    function testGetContentType() {

        $file = new FileMock();
        $this->assertNull($file->getContentType());

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testDelete() {

        $file = new FileMock();
        $file->delete();

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testSetName() {

        $file = new FileMock();
        $file->setName('hi');

    }

    function testGetLastModified() {

        $file = new FileMock();
        // checking if lastmod is within the range of a few seconds
        $lastMod = $file->getLastModified();
        $compareTime = ($lastMod + 1) - time();
        $this->assertTrue($compareTime < 3);

    }

    function testGetChild() {

        $dir = new DirectoryMock();
        $file = $dir->getChild('mockfile');
        $this->assertTrue($file instanceof FileMock);

    }

    function testChildExists() {

        $dir = new DirectoryMock();
        $this->assertTrue($dir->childExists('mockfile'));

    }

    function testChildExistsFalse() {

        $dir = new DirectoryMock();
        $this->assertFalse($dir->childExists('mockfile2'));

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     */
    function testGetChild404() {

        $dir = new DirectoryMock();
        $file = $dir->getChild('blabla');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testCreateFile() {

        $dir = new DirectoryMock();
        $dir->createFile('hello', 'data');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testCreateDirectory() {

        $dir = new DirectoryMock();
        $dir->createDirectory('hello');

    }

}

class DirectoryMock extends Collection {

    function getName() {

        return 'mockdir';

    }

    function getChildren() {

        return [new FileMock()];

    }

}

class FileMock extends File {

    function getName() {

        return 'mockfile';

    }

}
