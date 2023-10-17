<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

class FileTest extends \PHPUnit\Framework\TestCase
{
    /**
     * System under test.
     *
     * @var File
     */
    protected $sut;

    protected $path = 'foo';
    protected $acl = [
        [
            'privilege' => '{DAV:}read',
            'principal' => '{DAV:}authenticated',
        ],
    ];

    protected $owner = 'principals/evert';

    public function setup(): void
    {
        $this->sut = new File($this->path, $this->acl, $this->owner);
    }

    public function testGetOwner()
    {
        self::assertEquals(
            $this->owner,
            $this->sut->getOwner()
        );
    }

    public function testGetGroup()
    {
        self::assertNull(
            $this->sut->getGroup()
        );
    }

    public function testGetACL()
    {
        self::assertEquals(
            $this->acl,
            $this->sut->getACL()
        );
    }

    public function testSetAcl()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $this->sut->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        self::assertNull(
            $this->sut->getSupportedPrivilegeSet()
        );
    }
}
