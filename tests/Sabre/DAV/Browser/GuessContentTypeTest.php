<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

use Sabre\DAV;

class GuessContentTypeTest extends DAV\AbstractServer
{
    public function setUp(): void
    {
        parent::setUp();
        \Sabre\TestUtil::clearTempDir();
        file_put_contents(SABRE_TEMPDIR.'/somefile.jpg', 'blabla');
        file_put_contents(SABRE_TEMPDIR.'/somefile.hoi', 'blabla');
    }

    public function tearDown(): void
    {
        \Sabre\TestUtil::clearTempDir();
        parent::tearDown();
    }

    public function testGetProperties()
    {
        $properties = [
            '{DAV:}getcontenttype',
        ];
        $result = $this->server->getPropertiesForPath('/somefile.jpg', $properties);
        self::assertArrayHasKey(0, $result);
        self::assertArrayHasKey(404, $result[0]);
        self::assertArrayHasKey('{DAV:}getcontenttype', $result[0][404]);
    }

    /**
     * @depends testGetProperties
     */
    public function testGetPropertiesPluginEnabled()
    {
        $this->server->addPlugin(new GuessContentType());
        $properties = [
            '{DAV:}getcontenttype',
        ];
        $result = $this->server->getPropertiesForPath('/somefile.jpg', $properties);
        self::assertArrayHasKey(0, $result);
        self::assertArrayHasKey(200, $result[0], 'We received: '.print_r($result, true));
        self::assertArrayHasKey('{DAV:}getcontenttype', $result[0][200]);
        self::assertEquals('image/jpeg', $result[0][200]['{DAV:}getcontenttype']);
    }

    /**
     * @depends testGetPropertiesPluginEnabled
     */
    public function testGetPropertiesUnknown()
    {
        $this->server->addPlugin(new GuessContentType());
        $properties = [
            '{DAV:}getcontenttype',
        ];
        $result = $this->server->getPropertiesForPath('/somefile.hoi', $properties);
        self::assertArrayHasKey(0, $result);
        self::assertArrayHasKey(200, $result[0]);
        self::assertArrayHasKey('{DAV:}getcontenttype', $result[0][200]);
        self::assertEquals('application/octet-stream', $result[0][200]['{DAV:}getcontenttype']);
    }
}
