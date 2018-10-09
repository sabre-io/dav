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

    public function setUp()
    {
        $this->sut = new File($this->path, $this->acl, $this->owner);
    }

    public function testGetOwner()
    {
        $this->assertEquals(
            $this->owner,
            $this->sut->getOwner()
        );
    }

    public function testGetGroup()
    {
        $this->assertNull(
            $this->sut->getGroup()
        );
    }

    public function testGetACL()
    {
        $this->assertEquals(
            $this->acl,
            $this->sut->getACL()
        );
    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    public function testSetAcl()
    {
        $this->sut->setACL([]);
    }

    public function testGetSupportedPrivilegeSet()
    {
        $this->assertNull(
            $this->sut->getSupportedPrivilegeSet()
        );
    }
}
