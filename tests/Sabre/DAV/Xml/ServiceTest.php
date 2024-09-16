<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml;

use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    public function testInvalidNameSpace()
    {
        $this->expectException(\Sabre\Xml\LibXMLException::class);
        $xml = '<D:propfind xmlns:D="DAV:"><D:prop><bar:foo xmlns:bar=""/></D:prop></D:propfind>';
        $util = new Service();
        $util->expect('{DAV:}propfind', $xml);
    }
}
