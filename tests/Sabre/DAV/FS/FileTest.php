<?php declare (strict_types=1);

namespace Sabre\DAV\FS;

require_once 'Sabre/TestUtil.php';

class FileTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        file_put_contents(SABRE_TEMPDIR . '/file.txt', 'Contents');
        symlink('missing-file', SABRE_TEMPDIR . '/symlink');

    }

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testPut() {

        $filename = SABRE_TEMPDIR . '/file.txt';
        $file = new File($filename);
        $result = $file->put('New contents');

        $this->assertEquals('New contents', file_get_contents(SABRE_TEMPDIR . '/file.txt'));

    }

    function testGet() {

        $file = new File(SABRE_TEMPDIR . '/file.txt');
        $this->assertEquals('Contents', stream_get_contents($file->get()));

    }

    function testDelete() {

        $file = new File(SABRE_TEMPDIR . '/file.txt');
        $file->delete();

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/file.txt'));

    }

    function testGetETag() {

        $filename = SABRE_TEMPDIR . '/file.txt';
        $file = new File($filename);
        $this->assertEquals(
            '"' .
            sha1(
                fileinode($filename) .
                filesize($filename) .
                filemtime($filename)
            ) . '"',
            $file->getETag()
        );
    }

    function testGetETagBrokenSymlink() {

        $filename = SABRE_TEMPDIR . '/symlink';
        $file = new File($filename);
        $this->assertEquals(
            '"' .
            sha1(
                lstat($filename)['ino'] .
                0 .
                $time = lstat($filename)['mtime']
            ) . '"',
            $file->getETag()
        );
    }

    function testGetContentType() {

        $file = new File(SABRE_TEMPDIR . '/file.txt');
        $this->assertNull($file->getContentType());

    }

    function testGetSize() {

        $file = new File(SABRE_TEMPDIR . '/file.txt');
        $this->assertEquals(8, $file->getSize());

    }

    function testGetSizeBrokenSymlink() {

        $file = new File(SABRE_TEMPDIR . '/symlink');
        $this->assertEquals(0, $file->getSize());

    }

}
