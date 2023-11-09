<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Request;

use Sabre\DAV;
use Sabre\DAV\Xml\AbstractXmlTestCase;

class InviteReplyTest extends AbstractXmlTestCase
{
    protected $elementMap = [
        '{http://calendarserver.org/ns/}invite-reply' => 'Sabre\\CalDAV\\Xml\\Request\\InviteReply',
    ];

    public function testDeserialize()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <d:href>/principal/1</d:href>
    <cs:hosturl><d:href>/calendar/1</d:href></cs:hosturl>
    <cs:invite-accepted />
    <cs:in-reply-to>blabla</cs:in-reply-to>
    <cs:summary>Summary</cs:summary>
</cs:invite-reply>
XML;

        $result = $this->parse($xml);
        $inviteReply = new InviteReply('/principal/1', '/calendar/1', 'blabla', 'Summary', DAV\Sharing\Plugin::INVITE_ACCEPTED);

        self::assertEquals(
            $inviteReply,
            $result['value']
        );
    }

    public function testDeserializeDeclined()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <d:href>/principal/1</d:href>
    <cs:hosturl><d:href>/calendar/1</d:href></cs:hosturl>
    <cs:invite-declined />
    <cs:in-reply-to>blabla</cs:in-reply-to>
    <cs:summary>Summary</cs:summary>
</cs:invite-reply>
XML;

        $result = $this->parse($xml);
        $inviteReply = new InviteReply('/principal/1', '/calendar/1', 'blabla', 'Summary', DAV\Sharing\Plugin::INVITE_DECLINED);

        self::assertEquals(
            $inviteReply,
            $result['value']
        );
    }

    public function testDeserializeNoHostUrl()
    {
        $this->expectException('Sabre\DAV\Exception\BadRequest');
        $xml = <<<XML
<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <d:href>/principal/1</d:href>
    <cs:invite-declined />
    <cs:in-reply-to>blabla</cs:in-reply-to>
    <cs:summary>Summary</cs:summary>
</cs:invite-reply>
XML;

        $this->parse($xml);
    }
}
