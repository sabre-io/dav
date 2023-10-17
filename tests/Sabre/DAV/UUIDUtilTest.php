<?php

declare(strict_types=1);

namespace Sabre\DAV;

class UUIDUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateUUID()
    {
        self::assertTrue(
            UUIDUtil::validateUUID('11111111-2222-3333-4444-555555555555')
        );
        self::assertFalse(
            UUIDUtil::validateUUID(' 11111111-2222-3333-4444-555555555555')
        );
        self::assertTrue(
            UUIDUtil::validateUUID('ffffffff-2222-3333-4444-555555555555')
        );
        self::assertFalse(
            UUIDUtil::validateUUID('fffffffg-2222-3333-4444-555555555555')
        );
    }
}
