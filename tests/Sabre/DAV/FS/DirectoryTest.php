<?php declare (strict_types=1);

namespace Sabre\DAV\FS;

class DirectoryTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        file_put_contents(SABRE_TEMPDIR . '/file.txt', 'Contents');
        symlink('missing-file', SABRE_TEMPDIR . '/symlink');

    }

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function create() {

        return new Directory(SABRE_TEMPDIR);

    }

    function testCreate() {

        $dir = $this->create();
        $this->assertEquals(basename(SABRE_TEMPDIR), $dir->getName());

    }

    function testChildExists() {

        $dir = $this->create();
        $this->assertFalse($dir->childExists('notfound.txt'));

    }

    function testChildExistsBrokenSymlink() {

        $dir = $this->create();
        $this->assertTrue($dir->childExists('symlink'));

    }

    function testChildExistsNormal() {

        $dir = $this->create();
        $this->assertTrue($dir->childExists('file.txt'));

    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotFound
     */
    function testGetChildNotFound() {

        $dir = $this->create();
        $dir->getChild('notfound.txt');

    }
}
