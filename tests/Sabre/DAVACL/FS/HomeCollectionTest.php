<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

use Sabre\DAVACL\PrincipalBackend\Mock as PrincipalBackend;

class HomeCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * System under test.
     *
     * @var HomeCollection
     */
    protected $sut;

    protected $path;
    protected $name = 'thuis';

    public function setup(): void
    {
        $principalBackend = new PrincipalBackend();

        $this->path = SABRE_TEMPDIR.'/home';

        $this->sut = new HomeCollection($principalBackend, $this->path);
        $this->sut->collectionName = $this->name;
    }

    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testGetName()
    {
        $this->assertEquals(
            $this->name,
            $this->sut->getName()
        );
    }

    public function testGetChild()
    {
        $child = $this->sut->getChild('user1');
        $this->assertInstanceOf('Sabre\\DAVACL\\FS\\Collection', $child);
        $this->assertEquals('user1', $child->getName());

        $owner = 'principals/user1';
        $acl = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        $this->assertEquals($acl, $child->getACL());
        $this->assertEquals($owner, $child->getOwner());
    }

    public function testGetOwner()
    {
        $this->assertNull(
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
        $acl = [
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ],
        ];

        $this->assertEquals(
            $acl,
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
        $this->assertNull(
            $this->sut->getSupportedPrivilegeSet()
        );
    }
}
