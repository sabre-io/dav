<?php

/**
 * @covers Sabre_DAV_Tree
 * @covers Sabre_DAV_Tree_Filesystem
 * @covers Sabre_DAV_FS_Node
 * @covers Sabre_DAV_FS_File
 * @covers Sabre_DAV_FS_Directory
 */
class Sabre_DAV_Tree_FilesystemTest extends PHPUnit_Framework_TestCase {

    protected $tmpDir = 'temp';

    function setUp() {

        file_put_contents($this->tmpDir.'/file.txt','Body');
        mkdir($this->tmpDir.'/dir');
        file_put_contents($this->tmpDir.'/dir/subfile.txt','Body');

    }

    function tearDown() {

        $files = array(
            'file.txt',
            'file2.txt',
            'dir/subfile.txt',
            'dir2/subfile.txt',
            'dir',
            'dir2',
        );

        foreach($files as $file) {
            if (file_exists($this->tmpDir.'/'.$file)) {
                if (is_dir($this->tmpDir.'/'.$file)) {
                    rmdir($this->tmpDir . '/'.$file);
                } else {
                    unlink($this->tmpDir.'/' . $file);
                }
            }
        }

    }

    function testGetNodeForPath_File() {

        $fs = new Sabre_DAV_Tree_Filesystem($this->tmpDir);
        $node = $fs->getNodeForPath('file.txt');
        $this->assertTrue($node instanceof Sabre_DAV_FS_File);

    }

    function testGetNodeForPath_Directory() {

        $fs = new Sabre_DAV_Tree_Filesystem($this->tmpDir);
        $node = $fs->getNodeForPath('dir');
        $this->assertTrue($node instanceof Sabre_DAV_FS_Directory);

    }

    function testCopy() {

        $fs = new Sabre_DAV_Tree_Filesystem($this->tmpDir);
        $fs->copy('file.txt','file2.txt');
        $this->assertTrue(file_exists($this->tmpDir . '/file2.txt'));
        $this->assertEquals('Body',file_get_contents($this->tmpDir . '/file2.txt'));

    }

    function testCopyDir() {

        $fs = new Sabre_DAV_Tree_Filesystem($this->tmpDir);
        $fs->copy('dir','dir2');
        $this->assertTrue(file_exists($this->tmpDir . '/dir2'));
        $this->assertEquals('Body',file_get_contents($this->tmpDir . '/dir2/subfile.txt'));

    }

    function testMove() {

        $fs = new Sabre_DAV_Tree_Filesystem($this->tmpDir);
        $fs->move('file.txt','file2.txt');
        $this->assertTrue(file_exists($this->tmpDir . '/file2.txt'));
        $this->assertTrue(!file_exists($this->tmpDir . '/file.txt'));
        $this->assertEquals('Body',file_get_contents($this->tmpDir . '/file2.txt'));

    }


}
