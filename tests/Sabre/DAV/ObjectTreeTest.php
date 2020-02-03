<?php

declare(strict_types=1);

namespace Sabre\DAV;

class ObjectTreeTest extends \PHPUnit\Framework\TestCase
{
    protected $tree;

    public function setup()
    {
        \Sabre\TestUtil::clearTempDir();
        mkdir(SABRE_TEMPDIR.'/root');
        mkdir(SABRE_TEMPDIR.'/root/subdir');
        file_put_contents(SABRE_TEMPDIR.'/root/file.txt', 'contents');
        file_put_contents(SABRE_TEMPDIR.'/root/subdir/subfile.txt', 'subcontents');
        $rootNode = new FSExt\Directory(SABRE_TEMPDIR.'/root');
        $this->tree = new Tree($rootNode);
    }

    public function teardown()
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testGetRootNode()
    {
        $root = $this->tree->getNodeForPath('');
        $this->assertInstanceOf('Sabre\\DAV\\FSExt\\Directory', $root);
    }

    public function testGetSubDir()
    {
        $root = $this->tree->getNodeForPath('subdir');
        $this->assertInstanceOf('Sabre\\DAV\\FSExt\\Directory', $root);
    }

    public function testCopyFile()
    {
        $this->tree->copy('file.txt', 'file2.txt');
        $this->assertTrue(file_exists(SABRE_TEMPDIR.'/root/file2.txt'));
        $this->assertEquals('contents', file_get_contents(SABRE_TEMPDIR.'/root/file2.txt'));
    }

    /**
     * @depends testCopyFile
     */
    public function testCopyDirectory()
    {
        $this->tree->copy('subdir', 'subdir2');
        $this->assertTrue(file_exists(SABRE_TEMPDIR.'/root/subdir2'));
        $this->assertTrue(file_exists(SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
        $this->assertEquals('subcontents', file_get_contents(SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
    }

    /**
     * @depends testCopyFile
     */
    public function testMoveFile()
    {
        $this->tree->move('file.txt', 'file2.txt');
        $this->assertTrue(file_exists(SABRE_TEMPDIR.'/root/file2.txt'));
        $this->assertFalse(file_exists(SABRE_TEMPDIR.'/root/file.txt'));
        $this->assertEquals('contents', file_get_contents(SABRE_TEMPDIR.'/root/file2.txt'));
    }

    /**
     * @depends testMoveFile
     */
    public function testMoveFileNewParent()
    {
        $this->tree->move('file.txt', 'subdir/file2.txt');
        $this->assertTrue(file_exists(SABRE_TEMPDIR.'/root/subdir/file2.txt'));
        $this->assertFalse(file_exists(SABRE_TEMPDIR.'/root/file.txt'));
        $this->assertEquals('contents', file_get_contents(SABRE_TEMPDIR.'/root/subdir/file2.txt'));
    }

    /**
     * @depends testCopyDirectory
     */
    public function testMoveDirectory()
    {
        $this->tree->move('subdir', 'subdir2');
        $this->assertTrue(file_exists(SABRE_TEMPDIR.'/root/subdir2'));
        $this->assertTrue(file_exists(SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
        $this->assertFalse(file_exists(SABRE_TEMPDIR.'/root/subdir'));
        $this->assertEquals('subcontents', file_get_contents(SABRE_TEMPDIR.'/root/subdir2/subfile.txt'));
    }
}
