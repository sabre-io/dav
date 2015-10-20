<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use Sabre\DAV\Sharing\Plugin as SharingPlugin;

/**
 * This class represents the {DAV:}share-access property.
 *
 * This property is defined here:
 * TODO
 *
 * This property is used to indicate if a resource is a shared resource, and
 * whether the instance of the shared resource is the original instance, or
 * an instance belonging to a sharee.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ShareAccess implements XmlSerializable {

    /**
     * Either SHARED or SHAREDOWNER
     *
     * @var int
     */
    protected $value;

    /**
     * Creates the property.
     *
     * The constructor value must be one of the
     * \Sabre\DAV\Sharing\Plugin::ACCESS_ constants.
     *
     * @param int $shareAccess
     */
    function __construct($shareAccess) {

        $this->value = $shareAccess;

    }

    /**
     * Returns the current value.
     *
     * @return int
     */
    function getValue() {

        return $this->value;

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

            case SharingPlugin::ACCESS_NOTSHARED :
                $writer->writeElement('{DAV:}not-shared');
                break;
            case SharingPlugin::ACCESS_OWNER :
                $writer->writeElement('{DAV:}shared-owner');
                break;
            case SharingPlugin::ACCESS_READONLY :
                $writer->writeElement('{DAV:}shared-readonly');
                break;
            case SharingPlugin::ACCESS_READWRITE :
                $writer->writeElement('{DAV:}shared-readwrite');
                break;

        }

    }

}
