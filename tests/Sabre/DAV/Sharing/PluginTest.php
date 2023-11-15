<?php

declare(strict_types=1);

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Mock;
use Sabre\DAV\Xml\Property;

class PluginTest extends \Sabre\AbstractDAVServerTestCase
{
    protected $setupSharing = true;
    protected $setupACL = true;
    protected $autoLogin = 'admin';

    public function setUpTree()
    {
        $this->tree[] = new Mock\SharedNode(
            'shareable',
            Plugin::ACCESS_READWRITE
        );
    }

    public function testPostWithoutContentType()
    {
        $request = new \Sabre\HTTP\Request('POST', '/');
        $response = new \Sabre\HTTP\ResponseMock();

        $this->sharingPlugin->httpPost($request, $response);
        self::assertTrue(true);
    }

    public function testFeatures()
    {
        self::assertEquals(
            ['resource-sharing'],
            $this->sharingPlugin->getFeatures()
        );
    }

    public function testProperties()
    {
        $result = $this->server->getPropertiesForPath(
            'shareable',
            ['{DAV:}share-access']
        );

        $expected = [
            [
                200 => [
                    '{DAV:}share-access' => new Property\ShareAccess(Plugin::ACCESS_READWRITE),
                ],
                404 => [],
                'href' => 'shareable',
            ],
        ];

        self::assertEquals(
            $expected,
            $result
        );
    }

    public function testGetPluginInfo()
    {
        $result = $this->sharingPlugin->getPluginInfo();
        self::assertIsArray($result);
        self::assertEquals('sharing', $result['name']);
    }

    public function testHtmlActionsPanel()
    {
        $node = new \Sabre\DAV\Mock\Collection('foo');
        $html = '';

        self::assertNull(
            $this->sharingPlugin->htmlActionsPanel($node, $html, 'foo/bar')
        );

        self::assertEquals(
            '',
            $html
        );

        $node = new \Sabre\DAV\Mock\SharedNode('foo', \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER);
        $html = '';

        self::assertNull(
            $this->sharingPlugin->htmlActionsPanel($node, $html, 'shareable')
        );
        self::assertStringContainsString(
            'Share this resource',
            $html
        );
    }

    public function testBrowserPostActionUnknownAction()
    {
        self::assertNull($this->sharingPlugin->browserPostAction(
            'shareable',
            'foo',
            []
        ));
    }

    public function testBrowserPostActionSuccess()
    {
        self::assertFalse($this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'access' => 'read',
                'href' => 'mailto:foo@example.org',
            ]
        ));

        $expected = [
            new \Sabre\DAV\Xml\Element\Sharee([
                'href' => 'mailto:foo@example.org',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE,
            ]),
        ];
        self::assertEquals(
            $expected,
            $this->tree[0]->getInvites()
        );
    }

    public function testBrowserPostActionNoHref()
    {
        $this->expectException('Sabre\DAV\Exception\BadRequest');
        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'access' => 'read',
            ]
        );
    }

    public function testBrowserPostActionNoAccess()
    {
        $this->expectException('Sabre\DAV\Exception\BadRequest');
        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'href' => 'mailto:foo@example.org',
            ]
        );
    }

    public function testBrowserPostActionBadAccess()
    {
        $this->expectException('Sabre\DAV\Exception\BadRequest');
        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'href' => 'mailto:foo@example.org',
                'access' => 'bleed',
            ]
        );
    }

    public function testBrowserPostActionAccessDenied()
    {
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        $this->aclPlugin->setDefaultAcl([]);
        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'access' => 'read',
                'href' => 'mailto:foo@example.org',
            ]
        );
    }
}
