<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

class PropFindAllTest extends \PHPUnit\Framework\TestCase
{
    public function testHandleSimple()
    {
        $pf = new PropFindAll('foo');
        $pf->handle('{DAV:}displayname', 'foo');

        $this->assertEquals(200, $pf->getStatus('{DAV:}displayname'));
        $this->assertEquals('foo', $pf->get('{DAV:}displayname'));
    }

    public function testHandleCallBack()
    {
        $pf = new PropFindAll('foo');
        $pf->handle('{DAV:}displayname', function () { return 'foo'; });

        $this->assertEquals(200, $pf->getStatus('{DAV:}displayname'));
        $this->assertEquals('foo', $pf->get('{DAV:}displayname'));
    }

    public function testSet()
    {
        $pf = new PropFindAll('foo');
        $pf->set('{DAV:}displayname', 'foo');

        $this->assertEquals(200, $pf->getStatus('{DAV:}displayname'));
        $this->assertEquals('foo', $pf->get('{DAV:}displayname'));
    }

    public function testSetNull()
    {
        $pf = new PropFindAll('foo');
        $pf->set('{DAV:}displayname', null);

        $this->assertEquals(404, $pf->getStatus('{DAV:}displayname'));
        $this->assertEquals(null, $pf->get('{DAV:}displayname'));
    }

    public function testGet404Properties()
    {
        $pf = new PropFindAll('foo');
        $pf->set('{DAV:}displayname', null);
        $this->assertEquals(
            ['{DAV:}displayname'],
            $pf->get404Properties()
        );
    }

    public function testGet404PropertiesNothing()
    {
        $pf = new PropFindAll('foo');
        $pf->set('{DAV:}displayname', 'foo');
        $this->assertEquals(
            ['{http://sabredav.org/ns}idk'],
            $pf->get404Properties()
        );
    }
}
