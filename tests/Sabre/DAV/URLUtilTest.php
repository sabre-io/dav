<?php

class Sabre_DAV_URLUtilTest extends PHPUnit_Framework_TestCase{

    function testEncodePath() {

        $str = '';
        for($i=0;$i<128;$i++) $str.=chr($i);

        $newStr = Sabre_DAV_URLUtil::encodePath($str);

        $this->assertEquals(
            '%00%01%02%03%04%05%06%07%08%09%0a%0b%0c%0d%0e%0f'.
            '%10%11%12%13%14%15%16%17%18%19%1a%1b%1c%1d%1e%1f'.
            '%20%21%22%23%24%25%26%27()%2a%2b%2c-./'.
            '0123456789%3a%3b%3c%3d%3e%3f'.
            '%40ABCDEFGHIJKLMNO' .
            'PQRSTUVWXYZ%5b%5c%5d%5e_' .
            '%60abcdefghijklmno' .
            'pqrstuvwxyz%7b%7c%7d~%7f',
            $newStr);

        $this->assertEquals($str,Sabre_DAV_URLUtil::decodePath($newStr));

    }

    function testEncodePathSegment() {

        $str = '';
        for($i=0;$i<128;$i++) $str.=chr($i);

        $newStr = Sabre_DAV_URLUtil::encodePathSegment($str);

        // Note: almost exactly the same as the last test, with the
        // exception of the encoding of / (ascii code 2f)
        $this->assertEquals(
            '%00%01%02%03%04%05%06%07%08%09%0a%0b%0c%0d%0e%0f'.
            '%10%11%12%13%14%15%16%17%18%19%1a%1b%1c%1d%1e%1f'.
            '%20%21%22%23%24%25%26%27()%2a%2b%2c-.%2f'.
            '0123456789%3a%3b%3c%3d%3e%3f'.
            '%40ABCDEFGHIJKLMNO' .
            'PQRSTUVWXYZ%5b%5c%5d%5e_' .
            '%60abcdefghijklmno' .
            'pqrstuvwxyz%7b%7c%7d~%7f',
            $newStr);

        $this->assertEquals($str,Sabre_DAV_URLUtil::decodePathSegment($newStr));

    }


}
