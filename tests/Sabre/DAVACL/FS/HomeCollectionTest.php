<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

use Sabre\DAVACL\PrincipalBackend\Mock as PrincipalBackend;

class HomeCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * System under test.
     */
    protected HomeCollection $sut;

    protected string $path;
    protected string $name = 'thuis';

    public function setup(): void
    {
        $principalBackend = new PrincipalBackend();

        $this->path = \Sabre\TestUtil::SABRE_TEMPDIR.'/home';

        $this->sut = new HomeCollection($principalBackend, $this->path);
        $this->sut->collectionName = $this->name;
    }

    public function teardown(): void
    {
        \Sabre\TestUtil::clearTempDir();
    }

    public function testGetName(): void
    {
        self::assertEquals(
            $this->name,
            $this->sut->getName()
        );
    }

    public function testGetChild(): void
    {
        $child = $this->sut->getChild('user1');
        self::assertInstanceOf(Collection::class, $child);
        self::assertEquals('user1', $child->getName());

        $owner = 'principals/user1';
        $acl = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        self::assertEquals($acl, $child->getACL());
        self::assertEquals($owner, $child->getOwner());
    }

    public function testGetOwner(): void
    {
        self::assertNull(
            $this->sut->getOwner()
        );
    }

    public function testGetGroup(): void
    {
        self::assertNull(
            $this->sut->getGroup()
        );
    }

    public function testGetACL(): void
    {
        $acl = [
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ],
        ];

        self::assertEquals(
            $acl,
            $this->sut->getACL()
        );
    }

    public function testSetAcl(): void
    {
        $this->expectException(\Sabre\DAV\Exception\Forbidden::class);
        $this->sut->setACL([]);
    }

    public function testGetSupportedPrivilegeSet(): void
    {
        self::assertNull(
            $this->sut->getSupportedPrivilegeSet()
        );
    }
}
