<?php

class Sabre_DAV_XMLUtilTest extends PHPUnit_Framework_TestCase {

    function testToClarkNotation() {

        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><test1 xmlns="http://www.example.org/">Testdoc</test1>');

        $this->assertEquals(
            '{http://www.example.org/}test1',
            Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild)
        );

    }

    function testToClarkNotation2() {

        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><s:test1 xmlns:s="http://www.example.org/">Testdoc</s:test1>');

        $this->assertEquals(
            '{http://www.example.org/}test1',
            Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild)
        );

    }

    function testToClarkNotationDAVNamespace() {

        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><s:test1 xmlns:s="urn:DAV">Testdoc</s:test1>');

        $this->assertEquals(
            '{DAV:}test1',
            Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild)
        );

    }

    function testToClarkNotationNoElem() {

        $dom = new DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><s:test1 xmlns:s="urn:DAV">Testdoc</s:test1>');

        $this->assertNull(
            Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild->firstChild)
        );

    }

    function testConvertDAVNamespace() {

        $xml='<?xml version="1.0"?><document xmlns="DAV:">blablabla</document>';
        $this->assertEquals(
            '<?xml version="1.0"?><document xmlns="urn:DAV">blablabla</document>',
            Sabre_DAV_XMLUtil::convertDAVNamespace($xml)
        );

    }

    function testConvertDAVNamespace2() {

        $xml='<?xml version="1.0"?><s:document xmlns:s="DAV:">blablabla</s:document>';
        $this->assertEquals(
            '<?xml version="1.0"?><s:document xmlns:s="urn:DAV">blablabla</s:document>',
            Sabre_DAV_XMLUtil::convertDAVNamespace($xml)
        );

    }

    function testConvertDAVNamespace3() {

        $xml='<?xml version="1.0"?><s:document xmlns="http://bla" xmlns:s="DAV:" xmlns:z="http://othernamespace">blablabla</s:document>';
        $this->assertEquals(
            '<?xml version="1.0"?><s:document xmlns="http://bla" xmlns:s="urn:DAV" xmlns:z="http://othernamespace">blablabla</s:document>',
            Sabre_DAV_XMLUtil::convertDAVNamespace($xml)
        );

    }

    function testConvertDAVNamespace4() {

        $xml='<?xml version="1.0"?><document xmlns=\'DAV:\'>blablabla</document>';
        $this->assertEquals(
            '<?xml version="1.0"?><document xmlns=\'urn:DAV\'>blablabla</document>',
            Sabre_DAV_XMLUtil::convertDAVNamespace($xml)
        );

    }

    function testConvertDAVNamespaceMixedQuotes() {

        $xml='<?xml version="1.0"?><document xmlns=\'DAV:" xmlns="Another attribute\'>blablabla</document>';
        $this->assertEquals(
            $xml,
            Sabre_DAV_XMLUtil::convertDAVNamespace($xml)
        );

    }
    
    /**
     * @depends testConvertDAVNamespace
     */
    function testLoadDOMDocument() {

        $xml='<?xml version="1.0"?><document></document>';
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);
        $this->assertTrue($dom instanceof DOMDocument);

    }

    /**
     * @depends testLoadDOMDocument
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testLoadDOMDocumentEmpty() {

        Sabre_DAV_XMLUtil::loadDOMDocument('');

    }
    
    /**
     * @depends testConvertDAVNamespace
     * @expectedException Sabre_DAV_Exception_BadRequest
     */
    function testLoadDOMDocumentInvalid() {

        $xml='<?xml version="1.0"?><document></docu';
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($xml);

    }
}
