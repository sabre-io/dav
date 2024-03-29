<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\Xml\Writer;

class SystemStatusTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array  $notification
     * @param string $expected1
     * @param string $expected2
     * @dataProvider dataProvider
     */
    public function testSerializers($notification, $expected1, $expected2)
    {
        self::assertEquals('foo', $notification->getId());
        self::assertEquals('"1"', $notification->getETag());

        $writer = new Writer();
        $writer->namespaceMap = [
            'http://calendarserver.org/ns/' => 'cs',
        ];
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('{http://calendarserver.org/ns/}root');
        $writer->write($notification);
        $writer->endElement();
        self::assertXmlStringEqualsXmlString($expected1, $writer->outputMemory());

        $writer = new Writer();
        $writer->namespaceMap = [
            'http://calendarserver.org/ns/' => 'cs',
            'DAV:' => 'd',
        ];
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('{http://calendarserver.org/ns/}root');
        $notification->xmlSerializeFull($writer);
        $writer->endElement();
        self::assertXmlStringEqualsXmlString($expected2, $writer->outputMemory());
    }

    public function dataProvider()
    {
        return [
            [
                new SystemStatus('foo', '"1"'),
                '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="high"/></cs:root>'."\n",
                '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:"><cs:systemstatus type="high"/></cs:root>'."\n",
            ],
            [
                new SystemStatus('foo', '"1"', SystemStatus::TYPE_MEDIUM, 'bar'),
                '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="medium"/></cs:root>'."\n",
                '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:"><cs:systemstatus type="medium"><cs:description>bar</cs:description></cs:systemstatus></cs:root>'."\n",
            ],
            [
                new SystemStatus('foo', '"1"', SystemStatus::TYPE_LOW, null, 'http://example.org/'),
                '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:systemstatus type="low"/></cs:root>'."\n",
                '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:"><cs:systemstatus type="low"><d:href>http://example.org/</d:href></cs:systemstatus></cs:root>'."\n",
            ],
        ];
    }
}
