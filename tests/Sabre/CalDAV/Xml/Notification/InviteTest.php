<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\DAV;
use Sabre\Xml\Writer;

class InviteTest extends DAV\Xml\AbstractXmlTestCase
{
    /**
     * @param array  $notification
     * @param string $expected
     *
     * @dataProvider dataProvider
     */
    public function testSerializers($notification, $expected)
    {
        $notification = new Invite($notification);

        self::assertEquals('foo', $notification->getId());
        self::assertEquals('"1"', $notification->getETag());

        $simpleExpected = '<cs:invite-notification xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/" />'."\n";
        $this->namespaceMap['http://calendarserver.org/ns/'] = 'cs';

        $xml = $this->write($notification);

        self::assertXmlStringEqualsXmlString($simpleExpected, $xml);

        $this->namespaceMap['urn:ietf:params:xml:ns:caldav'] = 'cal';
        $xml = $this->writeFull($notification);

        self::assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function dataProvider()
    {
        $dtStamp = new \DateTime('2012-01-01 00:00:00', new \DateTimeZone('GMT'));

        return [
            [
                [
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'href' => 'mailto:foo@example.org',
                    'type' => DAV\Sharing\Plugin::INVITE_ACCEPTED,
                    'readOnly' => true,
                    'hostUrl' => 'calendar',
                    'organizer' => 'principal/user1',
                    'commonName' => 'John Doe',
                    'summary' => 'Awesome stuff!',
                ],
                <<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-accepted/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:summary>Awesome stuff!</cs:summary>
    <cs:access>
      <cs:read/>
    </cs:access>
    <cs:organizer>
      <d:href>/principal/user1</d:href>
      <cs:common-name>John Doe</cs:common-name>
    </cs:organizer>
    <cs:organizer-cn>John Doe</cs:organizer-cn>
  </cs:invite-notification>
</cs:root>

FOO,
            ],
            [
                [
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'href' => 'mailto:foo@example.org',
                    'type' => DAV\Sharing\Plugin::INVITE_NORESPONSE,
                    'readOnly' => true,
                    'hostUrl' => 'calendar',
                    'organizer' => 'principal/user1',
                    'firstName' => 'Foo',
                    'lastName' => 'Bar',
                ],
                <<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-notification>
    <cs:uid>foo</cs:uid>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-noresponse/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:access>
      <cs:read/>
    </cs:access>
    <cs:organizer>
      <d:href>/principal/user1</d:href>
      <cs:first-name>Foo</cs:first-name>
      <cs:last-name>Bar</cs:last-name>
    </cs:organizer>
    <cs:organizer-first>Foo</cs:organizer-first>
    <cs:organizer-last>Bar</cs:organizer-last>
  </cs:invite-notification>
</cs:root>

FOO,
            ],
        ];
    }

    public function testMissingArg()
    {
        $this->expectException('InvalidArgumentException');
        new Invite([]);
    }

    public function testUnknownArg()
    {
        $this->expectException('InvalidArgumentException');
        new Invite([
            'foo-i-will-break' => true,

            'id' => 1,
            'etag' => '"bla"',
            'href' => 'abc',
            'dtStamp' => 'def',
            'type' => 'ghi',
            'readOnly' => true,
            'hostUrl' => 'jkl',
            'organizer' => 'mno',
        ]);
    }

    public function writeFull($input)
    {
        $writer = new Writer();
        $writer->contextUri = '/';
        $writer->namespaceMap = $this->namespaceMap;
        $writer->openMemory();
        $writer->startElement('{http://calendarserver.org/ns/}root');
        $input->xmlSerializeFull($writer);
        $writer->endElement();

        return $writer->outputMemory();
    }
}
