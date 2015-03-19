<?php

namespace Sabre\DAV;
use Sabre\Xml;

/**
 * XML utilities for WebDAV
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class XMLUtil {

    /**
     * This is a list of XML elements that we automatically map to PHP classes.
     *
     * For instance, this list may contain an entry `{DAV:}propfind` that would
     * be mapped to Sabre\DAV\Xml\Request\PropFind
     */
    public $elementMap = [
        '{DAV:}multistatus' => 'Sabre\\DAV\\Xml\\Response\\MultiStatus',
        '{DAV:}response'    => 'Sabre\\DAV\\Xml\\Element\\Response',
        '{DAV:}propstat'    => 'Sabre\\Xml\\Element\\KeyValue',
        '{DAV:}prop'        => 'Sabre\\Xml\\Element\\KeyValue',
        '{DAV:}set'         => 'Sabre\\Xml\\Element\\KeyValue',
        '{DAV:}remove'      => 'Sabre\\Xml\\Element\\KeyValue',

        // Requests
        '{DAV:}propfind'        => 'Sabre\\DAV\\Xml\\Request\\PropFind',
        '{DAV:}propertyupdate'  => 'Sabre\\DAV\\Xml\\Request\\PropPatch',
        '{DAV:}mkcol'           => 'Sabre\\DAV\\Xml\\Request\\MkCol',

        // Properties
        '{DAV:}resourcetype' => 'Sabre\\DAV\\Xml\\Property\\ResourceType',

    ];

    /**
     * This is a default list of namespaces.
     *
     * If you are defining your own custom namespace, add it here to reduce
     * bandwidth and improve legibility of xml bodies.
     *
     * @var array
     */
    public $namespaceMap = [
        'DAV:' => 'd',
        'http://sabredav.org/ns' => 's',
    ];

    /**
     * Parses an XML file.
     * This method parses an xml file and maps all known properties to their
     * respective objects.
     *
     * @param string|resource|\Sabre\HTTP\MessageInterface $input
     * @return mixed
     */
    function parse($input) {

        $reader = new XML\Reader();
        $reader->elementMap = $this->elementMap;
        if ($input instanceof \Sabre\HTTP\MessageInterface) {
            $reader->xml($input->getBodyAsString());
        } else {
            $reader->xml($input);
        }
        return $reader->parse();

    }

    /**
     * Generates an XML document and returns the output as a string.
     *
     * @param mixed $output
     * @param string $baseUri // Specify the base URI of the document.
     * @return string
     */
    function write($output, $baseUri = null) {

        $writer = $this->getWriter($baseUri);
        $writer->startDocument();
        $writer->write($output);
        return $writer->outputMemory();

    }

    /**
     * Returns a fully configured xml writer, ready to start writing into
     * memory.
     *
     * @parama string $baseUri
     * @return XML\Writer
     */
    function getWriter($baseUri = null) {

        $writer = new XML\Writer();
        $writer->baseUri = $baseUri;
        $writer->namespaceMap = $this->namespaceMap;
        $writer->openMemory();
        $writer->setIndent(true);
        return $writer;

    }

    /**
     * Parses a clark-notation string, and returns the namespace and element
     * name components.
     *
     * If the string was invalid, it will throw an InvalidArgumentException.
     *
     * @param string $str
     * @throws InvalidArgumentException
     * @return array
     */
    static function parseClarkNotation($str) {

        if (!preg_match('/^{([^}]*)}(.*)$/',$str,$matches)) {
            throw new \InvalidArgumentException('\'' . $str . '\' is not a valid clark-notation formatted string');
        }

        return [
            $matches[1],
            $matches[2]
        ];

    }

}
