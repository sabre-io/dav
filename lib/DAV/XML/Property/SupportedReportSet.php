<?php

namespace Sabre\DAV\XML\Property;

use
    Sabre\DAV,
    Sabre\XML\Element,
    Sabre\XML\Reader,
    Sabre\XML\Writer,
    Sabre\XML\Element\Elements;

/**
 * supported-report-set property.
 *
 * This property is defined in RFC3253, but since it's
 * so common in other webdav-related specs, it is part of the core server.
 *
 * This property is defined here:
 * http://tools.ietf.org/html/rfc3253#section-3.1.5
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class SupportedReportSet implements Element {

    /**
     * List of reports
     *
     * @var array
     */
    protected $reports = array();

    /**
     * Creates the property
     *
     * Any reports passed in the constructor
     * should be valid report-types in clark-notation.
     *
     * Either a string or an array of strings must be passed.
     *
     * @param string|string[] $reports
     */
    public function __construct($reports = null) {

        if (!is_null($reports))
            $this->addReport($reports);

    }

    /**
     * Adds a report to this property
     *
     * The report must be a string in clark-notation.
     * Multiple reports can be specified as an array.
     *
     * @param mixed $report
     * @return void
     */
    public function addReport($report) {

        if (!is_array($report)) $report = array($report);

        foreach($report as $r) {

            if (!preg_match('/^{([^}]*)}(.*)$/',$r))
                throw new DAV\Exception('Reportname must be in clark-notation');

            $this->reports[] = $r;

        }

    }

    /**
     * Returns the list of supported reports
     *
     * @return string[]
     */
    public function getValue() {

        return $this->reports;

    }

    /**
     * Returns true or false if the property contains a specific report.
     *
     * @param string $reportName
     * @return bool
     */
    public function has($reportName) {

        return in_array(
            $reportName,
            $this->reports
        );

    }

    /**
     * The serialize method is called during xml writing.
     *
     * It should use the $writer argument to encode this object into XML.
     *
     * Important note: it is not needed to create the parent element. The
     * parent element is already created, and we only have to worry about
     * attributes, child elements and text (if any).
     *
     * Important note 2: If you are writing any new elements, you are also
     * responsible for closing them.
     *
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer) {

        foreach($this->getValue() as $val) {
            $writer->startElement('{DAV:}supported-report');
            $writer->startElement('{DAV:}report');
            $writer->writeElement($val);
            $writer->endElement();
            $writer->endElement();
        }

    }

    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statictly, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * Important note 2: You are responsible for advancing the reader to the
     * next element. Not doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @param Reader $reader
     * @return mixed
     */
    static public function xmlDeserialize(Reader $reader) {

        throw new CannotDeserialize('This element does not have a deserializer');

    }

}
