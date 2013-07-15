<?php

namespace Sabre\DAV;

class CopyTest extends \PHPUnit_Framework_TestCase {

    /**
     * This test makes sure that a path like /foo cannot be copied into a path
     * like /foo/bar/
     */
    public function testCopyIntoSubPath() {

        $tree = new FS\Directory(SABRE_TEMPDIR);
        $server = new Server($tree);

        $tree->createCollection('foo');

        $request = new HTTP\Request('COPY','/foo', [
            'Destination' => '/foo/bar',
        ]);

    }

}
