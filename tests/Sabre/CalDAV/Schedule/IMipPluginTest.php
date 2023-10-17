<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\DAV\Server;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Reader;

class IMipPluginTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPluginInfo()
    {
        $plugin = new IMipPlugin('system@example.com');
        self::assertEquals(
            'imip',
            $plugin->getPluginInfo()['name']
        );
    }

    public function testDeliverReply()
    {
        $message = new Message();
        $message->sender = 'mailto:sender@example.org';
        $message->senderName = 'Sender';
        $message->recipient = 'mailto:recipient@example.org';
        $message->recipientName = 'Recipient';
        $message->method = 'REPLY';

        $ics = <<<ICS
BEGIN:VCALENDAR\r
METHOD:REPLY\r
BEGIN:VEVENT\r
SUMMARY:Birthday party\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $message->message = Reader::read($ics);

        $result = $this->schedule($message);

        $expected = [
            [
                'to' => 'Recipient <recipient@example.org>',
                'subject' => 'Re: Birthday party',
                'body' => $ics,
                'headers' => [
                    'Reply-To: Sender <sender@example.org>',
                    'From: Sender <system@example.org>',
                    'MIME-Version: 1.0',
                    'Content-Type: text/calendar; charset=UTF-8; method=REPLY',
                    'X-Sabre-Version: '.\Sabre\DAV\Version::VERSION,
                ],
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testDeliverReplyNoMailto()
    {
        $message = new Message();
        $message->sender = 'mailto:sender@example.org';
        $message->senderName = 'Sender';
        $message->recipient = 'http://example.org/recipient';
        $message->recipientName = 'Recipient';
        $message->method = 'REPLY';

        $ics = <<<ICS
BEGIN:VCALENDAR\r
METHOD:REPLY\r
BEGIN:VEVENT\r
SUMMARY:Birthday party\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $message->message = Reader::read($ics);

        $result = $this->schedule($message);

        $expected = [];

        self::assertEquals($expected, $result);
    }

    public function testDeliverRequest()
    {
        $message = new Message();
        $message->sender = 'mailto:sender@example.org';
        $message->senderName = 'Sender';
        $message->recipient = 'mailto:recipient@example.org';
        $message->recipientName = 'Recipient';
        $message->method = 'REQUEST';

        $ics = <<<ICS
BEGIN:VCALENDAR\r
METHOD:REQUEST\r
BEGIN:VEVENT\r
SUMMARY:Birthday party\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $message->message = Reader::read($ics);

        $result = $this->schedule($message);

        $expected = [
            [
                'to' => 'Recipient <recipient@example.org>',
                'subject' => 'Invitation: Birthday party',
                'body' => $ics,
                'headers' => [
                    'Reply-To: Sender <sender@example.org>',
                    'From: Sender <system@example.org>',
                    'MIME-Version: 1.0',
                    'Content-Type: text/calendar; charset=UTF-8; method=REQUEST',
                    'X-Sabre-Version: '.\Sabre\DAV\Version::VERSION,
                ],
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testDeliverCancel()
    {
        $message = new Message();
        $message->sender = 'mailto:sender@example.org';
        $message->senderName = 'Sender';
        $message->recipient = 'mailto:recipient@example.org';
        $message->recipientName = 'Recipient';
        $message->method = 'CANCEL';

        $ics = <<<ICS
BEGIN:VCALENDAR\r
METHOD:CANCEL\r
BEGIN:VEVENT\r
SUMMARY:Birthday party\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $message->message = Reader::read($ics);

        $result = $this->schedule($message);

        $expected = [
            [
                'to' => 'Recipient <recipient@example.org>',
                'subject' => 'Cancelled: Birthday party',
                'body' => $ics,
                'headers' => [
                    'Reply-To: Sender <sender@example.org>',
                    'From: Sender <system@example.org>',
                    'MIME-Version: 1.0',
                    'Content-Type: text/calendar; charset=UTF-8; method=CANCEL',
                    'X-Sabre-Version: '.\Sabre\DAV\Version::VERSION,
                ],
            ],
        ];

        self::assertEquals($expected, $result);
        self::assertEquals('1.1', substr($message->scheduleStatus, 0, 3));
    }

    public function schedule(Message $message)
    {
        $plugin = new IMip\MockPlugin('system@example.org');

        $server = new Server();
        $server->addPlugin($plugin);
        $server->emit('schedule', [$message]);

        return $plugin->getSentEmails();
    }

    public function testDeliverInsignificantRequest()
    {
        $message = new Message();
        $message->sender = 'mailto:sender@example.org';
        $message->senderName = 'Sender';
        $message->recipient = 'mailto:recipient@example.org';
        $message->recipientName = 'Recipient';
        $message->method = 'REQUEST';
        $message->significantChange = false;

        $ics = <<<ICS
BEGIN:VCALENDAR\r
METHOD:REQUEST\r
BEGIN:VEVENT\r
SUMMARY:Birthday party\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $message->message = Reader::read($ics);

        $result = $this->schedule($message);

        $expected = [];
        self::assertEquals($expected, $result);
        self::assertSame('1', $message->getScheduleStatus()[0]);
    }

    public function testRecipientNameIsEmail()
    {
        $message = new Message();
        $message->sender = 'mailto:sender@example.org';
        $message->senderName = 'Sender';
        $message->recipient = 'mailto:recipient@example.org';
        $message->recipientName = 'recipient@example.org';
        $message->method = 'REQUEST';

        $ics = <<<ICS
BEGIN:VCALENDAR\r
METHOD:REQUEST\r
BEGIN:VEVENT\r
SUMMARY:Birthday party\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $message->message = Reader::read($ics);

        $result = $this->schedule($message);

        self::assertEquals('recipient@example.org', $result[0]['to']);
    }
}
