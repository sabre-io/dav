<?php

declare(strict_types=1);

namespace Sabre\DAV\FSExt;

class FileTest extends \PHPUnit\Framework\TestCase
{
    public function setup(): void
    {
        file_put_contents(SABRE_TEMPDIR.'/file.txt', 'Contents');
    }

    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testPut()
    {
        $filename = SABRE_TEMPDIR.'/file.txt';
        $file = new File($filename);
        $result = $file->put('New contents');

        self::assertEquals('New contents', file_get_contents(SABRE_TEMPDIR.'/file.txt'));
        self::assertEquals(
            '"'.
            sha1(
                fileinode($filename).
                filesize($filename).
                filemtime($filename)
            ).'"',
            $result
        );
    }

    public function testRangeAppend()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        $file->put('0000000');
        $file->patch('111', 1);

        self::assertEquals('0000000111', file_get_contents(SABRE_TEMPDIR.'/file.txt'));
    }

    public function testRangeOffset()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        $file->put('0000000');
        $file->patch('111', 2, 3);

        self::assertEquals('0001110', file_get_contents(SABRE_TEMPDIR.'/file.txt'));
    }

    public function testRangeOffsetEnd()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        $file->put('0000000');
        $file->patch('11', 3, -4);

        self::assertEquals('0001100', file_get_contents(SABRE_TEMPDIR.'/file.txt'));
    }

    public function testRangeOffsetDefault()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        $file->put('0000000');
        $file->patch('11', 0);

        self::assertEquals('000000011', file_get_contents(SABRE_TEMPDIR.'/file.txt'));
    }

    public function testRangeStream()
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '222');
        rewind($stream);

        $file = new File(SABRE_TEMPDIR.'/file.txt');
        $file->put('0000000');
        $file->patch($stream, 2, 3);

        self::assertEquals('0002220', file_get_contents(SABRE_TEMPDIR.'/file.txt'));
    }

    public function testGet()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        self::assertEquals('Contents', stream_get_contents($file->get()));
    }

    public function testDelete()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        $file->delete();

        self::assertFalse(file_exists(SABRE_TEMPDIR.'/file.txt'));
    }

    public function testGetETag()
    {
        $filename = SABRE_TEMPDIR.'/file.txt';
        $file = new File($filename);
        self::assertEquals(
            '"'.
            sha1(
                fileinode($filename).
                filesize($filename).
                filemtime($filename)
            ).'"',
            $file->getETag()
        );
    }

    public function testGetContentType()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        self::assertNull($file->getContentType());
    }

    public function testGetSize()
    {
        $file = new File(SABRE_TEMPDIR.'/file.txt');
        self::assertEquals(8, $file->getSize());
    }
}
