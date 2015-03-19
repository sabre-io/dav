<?php

namespace Sabre\DAV; 

class XMLUtilTest extends \PHPUnit_Framework_TestCase {

    function testParseClarkNotation() {

        $this->assertEquals(array(
            'DAV:',
            'foo',
        ), XMLUtil::parseClarkNotation('{DAV:}foo'));

        $this->assertEquals(array(
            'http://example.org/ns/bla',
            'bar-soap',
        ), XMLUtil::parseClarkNotation('{http://example.org/ns/bla}bar-soap'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testParseClarkNotationFail() {

        XMLUtil::parseClarkNotation('}foo');

    }

}

