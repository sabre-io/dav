<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * This class represents the {DAV:}share-mode property.
 *
 * This property is defined here:
 * https://tools.ietf.org/html/draft-pot-webdav-resource-sharing-02#section-5.2.1
 *
 * This property is used to indicate if a resource is a shared resource, and
 * whether the instance of the shared resource is the original instance, or
 * an instance belonging to a sharee.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ShareMode implements XmlSerializable {

    const SHARED = 1;
    const SHAREDOWNER = 2;

    /**
     * Either SHARED or SHAREDOWNER
     *
     * @var int
     */
    protected $value;

    /**
     * Creates the property
     *
     * @param int $shareModeType Either SHARED or SHAREDOWNER.
     */
    function __construct($shareModeType) {

        $this->value = $shareModeType;

    }

    /**
     * The xmlSerialize method is called during xml writing.
     *
     * Use the $writer argument to write its own xml serialization.
     *
     * An important note: do _not_ create a parent element. Any element
     * implementing XmlSerializble should only ever write what's considered
     * its 'inner xml'.
     *
     * The parent of the current element is responsible for writing a
     * containing element.
     *
     * This allows serializers to be re-used for different element names.
     *
     * If you are opening new elements, you must also close them again.
     *
     * @param Writer $writer
     * @return void
     */
    function xmlSerialize(Writer $writer) {

        switch ($this->value) {

            case self::SHARED :
                $writer->writeElement('{DAV:}shared');
                break;
            case self::SHAREDOWNER :
                $writer->writeElement('{DAV:}shared-owner');
                break;

        }

    }

}
