<?php declare (strict_types=1);

namespace Sabre\DAV\FSExt;

class DirectoryTest extends \PHPUnit\Framework\TestCase {

    function create() {

        return new Directory(SABRE_TEMPDIR);

    }

    function testCreate() {

        $dir = $this->create();
        $this->assertEquals(basename(SABRE_TEMPDIR), $dir->getName());

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testChildExistDot() {

        $dir = $this->create();
        $dir->childExists('..');

    }

}
