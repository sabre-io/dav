<?php

declare(strict_types=1);

namespace Sabre\DAV;

class SimpleFileTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $file = new SimpleFile('filename.txt', 'contents', 'text/plain');

        self::assertEquals('filename.txt', $file->getName());
        self::assertEquals('contents', $file->get());
        self::assertEquals(8, $file->getSize());
        self::assertEquals('"'.sha1('contents').'"', $file->getETag());
        self::assertEquals('text/plain', $file->getContentType());
    }
}
