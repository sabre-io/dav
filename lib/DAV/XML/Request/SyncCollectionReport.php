<?php

namespace Sabre\DAV\XML\Request;

use
    Sabre\XML\Element,
    Sabre\XML\Reader,
    Sabre\XML\Writer,
    Sabre\XML\Element\KeyValue,
    Sabre\DAV\Exception\CannotSerialize,
    Sabre\DAV\Exception\BadRequest;

/**
 * SyncCollection request parser.
 *
 * This class parses the {DAV:}sync-collection reprot, as defined in:
 *
 * http://tools.ietf.org/html/rfc6578#section-3.2
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class SyncCollectionReport implements Element {

    /**
     * The sync-token the client supplied for the report.
     *
     * @var string|null
     */
    public $syncToken;

    /**
     * The 'depth' of the sync the client is interested in.
     *
     * @var int
     */
    public $syncLevel;

    /**
     * Maximum amount of items returned.
     *
     * @var int|null
     */
    public $limit;

    /**
     * The list of properties that are being requested for every change.
     *
     * @var null|array
     */
    public $properties;

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
    public function serializeXml(Writer $writer) {

        throw new CannotSerialize('This element cannot be serialized.');

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
    static public function deserializeXml(Reader $reader) {

        $self = new self();

        $elems = KeyValue::deserializeXml($reader);

        $required = [
            '{DAV:}sync-token',
            '{DAV:}prop',
            ];

        foreach($required as $elem) {
            if (!array_key_exists($elem, $elems)) {
                throw new BadRequest('The '.$elem.' element in the {DAV:}sync-collection report is required');
            }
        }


        $self->properties = array_keys($elems['{DAV:}prop']);
        $self->syncToken = $elems['{DAV:}sync-token'];

        if (isset($elems['{DAV:}limit'])) {
            $nresults = null;
            foreach($elems['{DAV:}limit'] as $child) {
                if ($child['name'] === '{DAV:}nresults') {
                    $nresults = (int)$child['value'];
                }
            }
            $self->limit = $nresults;
        }

        if (isset($elems['{DAV:}sync-level'])) {

            $value = $elems['{DAV:}sync-level'];
            if ($value==='infinity') {
                $value = \Sabre\DAV\Server::DEPTH_INFINITY;
            }
            $self->syncLevel = $value;

        }

        return $self;

    }

}
