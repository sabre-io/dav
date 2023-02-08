<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

class PrincipalCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        self::assertTrue($pc instanceof PrincipalCollection);

        self::assertEquals('principals', $pc->getName());
    }

    /**
     * @depends testBasic
     */
    public function testGetChildren()
    {
        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);

        $children = $pc->getChildren();
        self::assertTrue(is_array($children));

        foreach ($children as $child) {
            self::assertTrue($child instanceof IPrincipal);
        }
    }

    /**
     * @depends testBasic
     */
    public function testGetChildrenDisable()
    {
        $this->expectException('Sabre\DAV\Exception\MethodNotAllowed');
        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        $pc->disableListing = true;

        $children = $pc->getChildren();
    }

    public function testFindByUri()
    {
        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        self::assertEquals('principals/user1', $pc->findByUri('mailto:user1.sabredav@sabredav.org'));
        self::assertNull($pc->findByUri('mailto:fake.user.sabredav@sabredav.org'));
        self::assertNull($pc->findByUri(''));
    }
}
