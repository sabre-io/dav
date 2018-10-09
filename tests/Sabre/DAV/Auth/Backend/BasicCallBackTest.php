<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

class BasicCallBackTest extends \PHPUnit\Framework\TestCase
{
    public function testCallBack()
    {
        $args = [];
        $callBack = function ($user, $pass) use (&$args) {
            $args = [$user, $pass];

            return true;
        };

        $backend = new BasicCallBack($callBack);

        $request = new HTTP\Request('GET', '/', [
            'Authorization' => 'Basic '.base64_encode('foo:bar'),
        ]);
        $response = new HTTP\Response();

        $this->assertEquals(
            [true, 'principals/foo'],
            $backend->check($request, $response)
        );

        $this->assertEquals(['foo', 'bar'], $args);
    }
}
