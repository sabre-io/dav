<?php

declare(strict_types=1);

namespace Sabre\DAV;

class ObjectTreeTest extends \PHPUnit\Framework\TestCase
{
    protected $tree;

    public function setup(): void
    {
        \Sabre\TestUtil::clearTempDir();
        mkdir(\Sabre\TestUtil::SABRE_TEMPDIR.'/root');
        mkdir(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir');
        file_put_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/file.txt', 'contents');
        file_put_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir/subfile.txt', 'subcontents');
        $rootNode = new FSExt\Directory(\Sabre\TestUtil::SABRE_TEMPDIR.'/root');
        $this->tree = new Tree($rootNode);
    }

    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testGetRootNode()
    {
        $root = $this->tree->getNodeForPath('');
        self::assertInstanceOf('Sabre\\DAV\\FSExt\\Directory', $root);
    }

    public function testGetSubDir()
    {
        $root = $this->tree->getNodeForPath('subdir');
        self::assertInstanceOf('Sabre\\DAV\\FSExt\\Directory', $root);
    }

    public function testCopyFile()
    {
        $this->tree->copy('file.txt', 'file2.txt');
        self::assertTrue(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/file2.txt'));
        self::assertEquals('contents', file_get_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/file2.txt'));
    }

    /**
     * @depends testCopyFile
     */
    public function testCopyDirectory()
    {
        $this->tree->copy('subdir', 'subdir2');
        self::assertTrue(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir2'));
        self::assertTrue(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
        self::assertEquals('subcontents', file_get_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
    }

    /**
     * @depends testCopyFile
     */
    public function testMoveFile()
    {
        $this->tree->move('file.txt', 'file2.txt');
        self::assertTrue(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/file2.txt'));
        self::assertFalse(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/file.txt'));
        self::assertEquals('contents', file_get_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/file2.txt'));
    }

    /**
     * @depends testMoveFile
     */
    public function testMoveFileNewParent()
    {
        $this->tree->move('file.txt', 'subdir/file2.txt');
        self::assertTrue(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir/file2.txt'));
        self::assertFalse(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/file.txt'));
        self::assertEquals('contents', file_get_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir/file2.txt'));
    }

    /**
     * @depends testCopyDirectory
     */
    public function testMoveDirectory()
    {
        $this->tree->move('subdir', 'subdir2');
        self::assertTrue(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir2'));
        self::assertTrue(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
        self::assertFalse(file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir'));
        self::assertEquals('subcontents', file_get_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
    }
}
