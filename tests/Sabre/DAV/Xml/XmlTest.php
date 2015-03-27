<?php

namespace Sabre\DAV\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\Reader;

abstract class XmlTest extends \PHPUnit_Framework_TestCase {

    protected $elementMap = [];
    protected $namespaceMap = ['DAV:' => 'd'];
    protected $contextUri = '/';

    function write($input) {

        $writer = new Writer();
        $writer->contextUri = $this->contextUri;
        $writer->namespaceMap = $this->namespaceMap;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->write($input);
        return $writer->outputMemory();

    }

    function parse($xml, array $elementMap = []) {

        $reader = new Reader();
        $reader->elementMap = array_merge($this->elementMap, $elementMap);
        $reader->xml($xml);
        return $reader->parse();

    }

    function cleanUp() {

        libxml_clear_errors();

    }

}
