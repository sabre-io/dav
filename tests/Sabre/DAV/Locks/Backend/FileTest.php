<?php

namespace Sabre\DAV\Locks\Backend;

class FileTest extends AbstractTest {

    function getBackend() {

        \Sabre\TestUtil::clearTempDir();
        $backend = new File(SABRE_TEMPDIR . '/lockdb');
        return $backend;

    }


    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

}
