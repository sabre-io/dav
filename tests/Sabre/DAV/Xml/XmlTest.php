<?php

namespace Sabre\DAV\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\Reader;

abstract class XmlTest extends \PHPUnit_Framework_TestCase {

    protected $namespaceMap = ['DAV:' => 'd'];
    protected $baseUri = '/';

    function write($input) {

        $writer = new Writer();
        $writer->baseUri = $this->baseUri;
        $writer->namespaceMap = $this->namespaceMap;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->write($input);
        return $writer->outputMemory();

    }

    function parse($xml, $elementMap) {

        $reader = new Reader();
        $reader->elementMap = $elementMap;
        $reader->xml($xml);
        return $reader->parse();

    }

    function cleanUp() {

        libxml_clear_errors();

    }

}
