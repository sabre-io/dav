<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

class FileTest extends \PHPUnit\Framework\TestCase
{
    public function teardown(): void
    {
        if (file_exists(\Sabre\TestUtil::SABRE_TEMPDIR.'/filebackend')) {
            unlink(\Sabre\TestUtil::SABRE_TEMPDIR.'/filebackend');
        }
    }

    public function testConstruct()
    {
        $file = new File();
        self::assertTrue($file instanceof File);
    }

    public function testLoadFileBroken()
    {
        $this->expectException('Sabre\DAV\Exception');
        file_put_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/backend', 'user:realm:hash');
        $file = new File(\Sabre\TestUtil::SABRE_TEMPDIR.'/backend');
    }

    public function testLoadFile()
    {
        file_put_contents(\Sabre\TestUtil::SABRE_TEMPDIR.'/backend', 'user:realm:'.md5('user:realm:password'));
        $file = new File();
        $file->loadFile(\Sabre\TestUtil::SABRE_TEMPDIR.'/backend');

        self::assertFalse($file->getDigestHash('realm', 'blabla'));
        self::assertEquals(md5('user:realm:password'), $file->getDigestHash('realm', 'user'));
    }
}
