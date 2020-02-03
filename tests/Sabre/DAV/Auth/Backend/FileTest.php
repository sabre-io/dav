<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

class FileTest extends \PHPUnit\Framework\TestCase
{
    public function tearDown()
    {
        if (file_exists(SABRE_TEMPDIR.'/filebackend')) {
            unlink(SABRE_TEMPDIR.'/filebackend');
        }
    }

    public function testConstruct()
    {
        $file = new File();
        $this->assertTrue($file instanceof File);
    }

    public function testLoadFileBroken()
    {
        $this->expectException('Sabre\DAV\Exception');
        file_put_contents(SABRE_TEMPDIR.'/backend', 'user:realm:hash');
        $file = new File(SABRE_TEMPDIR.'/backend');
    }

    public function testLoadFile()
    {
        file_put_contents(SABRE_TEMPDIR.'/backend', 'user:realm:'.md5('user:realm:password'));
        $file = new File();
        $file->loadFile(SABRE_TEMPDIR.'/backend');

        $this->assertFalse($file->getDigestHash('realm', 'blabla'));
        $this->assertEquals(md5('user:realm:password'), $file->getDigestHash('realm', 'user'));
    }
}
