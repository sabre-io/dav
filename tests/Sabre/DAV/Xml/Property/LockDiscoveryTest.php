<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Locks\LockInfo;
use Sabre\DAV\Xml\AbstractXmlTestCase;

class LockDiscoveryTest extends AbstractXmlTestCase
{
    public function testSerialize()
    {
        $lock = new LockInfo();
        $lock->owner = 'hello';
        $lock->token = 'blabla';
        $lock->timeout = 600;
        $lock->created = strtotime('2015-03-25 19:21:00');
        $lock->scope = LockInfo::EXCLUSIVE;
        $lock->depth = 0;
        $lock->uri = 'hi';

        $prop = new LockDiscovery([$lock]);

        $xml = $this->write(['{DAV:}root' => $prop]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:activelock>
  <d:lockscope><d:exclusive /></d:lockscope>
  <d:locktype><d:write /></d:locktype>
  <d:lockroot>
    <d:href>/hi</d:href>
  </d:lockroot>
  <d:depth>0</d:depth>
  <d:timeout>Second-600</d:timeout>
  <d:locktoken>
    <d:href>opaquelocktoken:blabla</d:href>
  </d:locktoken>
  <d:owner>hello</d:owner>

  
</d:activelock>
</d:root>
', $xml);
    }

    public function testSerializeShared()
    {
        $lock = new LockInfo();
        $lock->owner = 'hello';
        $lock->token = 'blabla';
        $lock->timeout = 600;
        $lock->created = strtotime('2015-03-25 19:21:00');
        $lock->scope = LockInfo::SHARED;
        $lock->depth = 0;
        $lock->uri = 'hi';

        $prop = new LockDiscovery([$lock]);

        $xml = $this->write(['{DAV:}root' => $prop]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:activelock>
  <d:lockscope><d:shared /></d:lockscope>
  <d:locktype><d:write /></d:locktype>
  <d:lockroot>
    <d:href>/hi</d:href>
  </d:lockroot>
  <d:depth>0</d:depth>
  <d:timeout>Second-600</d:timeout>
  <d:locktoken>
    <d:href>opaquelocktoken:blabla</d:href>
  </d:locktoken>
  <d:owner>hello</d:owner>

  
</d:activelock>
</d:root>
', $xml);
    }

    public function testSerializeInfiniteTimeout()
    {
        $lock = new LockInfo();
        $lock->owner = 'hello';
        $lock->token = 'blabla';
        $lock->timeout = -1;
        $lock->created = strtotime('2015-03-25 19:21:00');
        $lock->scope = LockInfo::SHARED;
        $lock->depth = 0;
        $lock->uri = 'hi';

        $prop = new LockDiscovery([$lock]);

        $xml = $this->write(['{DAV:}root' => $prop]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:activelock>
  <d:lockscope><d:shared /></d:lockscope>
  <d:locktype><d:write /></d:locktype>
  <d:lockroot>
    <d:href>/hi</d:href>
  </d:lockroot>
  <d:depth>0</d:depth>
  <d:timeout>Infinite</d:timeout>
  <d:locktoken>
    <d:href>opaquelocktoken:blabla</d:href>
  </d:locktoken>
  <d:owner>hello</d:owner>

  
</d:activelock>
</d:root>
', $xml);
    }

    public function providesSerializeNoLockToken()
    {
        return [
            [''],
            [null],
        ];
    }

    /**
     * @dataProvider providesSerializeNoLockToken
     */
    public function testSerializeNoLockToken($emptyLockToken)
    {
        $lock = new LockInfo();
        $lock->owner = 'hello';
        $lock->token = $emptyLockToken;
        $lock->timeout = 600;
        $lock->created = strtotime('2015-03-25 19:21:00');
        $lock->scope = LockInfo::SHARED;
        $lock->depth = 0;
        $lock->uri = 'hi';

        $prop = new LockDiscovery([$lock]);

        $xml = $this->write(['{DAV:}root' => $prop]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:activelock>
  <d:lockscope><d:shared /></d:lockscope>
  <d:locktype><d:write /></d:locktype>
  <d:lockroot>
    <d:href>/hi</d:href>
  </d:lockroot>
  <d:depth>0</d:depth>
  <d:timeout>Second-600</d:timeout>
  <d:owner>hello</d:owner>

  
</d:activelock>
</d:root>
', $xml);
    }
}
