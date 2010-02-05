<?php

class Sabre_DAV_Auth_Backend_FileTest extends PHPUnit_Framework_TestCase {

    function tearDown() {

        if (file_exists('temp/filebackend')) unlink('temp/filebackend');

    }

    function testConstruct() {

        $file = new Sabre_DAV_Auth_Backend_File();
        $this->assertTrue($file instanceof Sabre_DAV_Auth_Backend_File);

    }

    /**
     * @expectedException Sabre_DAV_Exception
     */
    function testLoadFileBroken() {

        file_put_contents('temp/backend','user:realm:hash');
        $file = new Sabre_DAV_Auth_Backend_File();
        $file->loadFile('temp/backend');

    }

    function testLoadFile() {

        file_put_contents('temp/backend','user:realm:' . md5('user:realm:password'));
        $file = new Sabre_DAV_Auth_Backend_File();
        $file->loadFile('temp/backend');

        $this->assertEquals(array(array('userId'=>'user')), $file->getUsers());
        $this->assertFalse($file->getUserInfo('realm','blabla'));
        $this->assertEquals(array('userId'=>'user','digestHash'=>md5('user:realm:password')), $file->getUserInfo('realm','user'));

    }

}
