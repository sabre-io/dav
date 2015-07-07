<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\Element\KeyValue;
use Sabre\DAV\Exception\BadRequest;

/**
 * ShareResource request parser.
 *
 * This class parses the {DAV:}share-resource POST request as defined in:
 *
 * https://tools.ietf.org/html/draft-pot-webdav-resource-sharing-01#section-5.3.2.1
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ShareResource implements XmlDeserializable {

    /**
     * The list of new people added or updated.
     *
     * Every element has the following keys:
     * 1. href - An email address
     * 2. comment - An optional description of the share
     * 3. readOnly - true or false
     *
     * In addition to that, it might contain a list of webdav properties
     * associated with the sharer. The most common one is {DAV:}displayname.
     *
     * @var array
     */
    public $set = [];

    /**
     * List of people removed from the share list.
     *
     * The list is a flat list of email addresses (including mailto:).
     *
     * @var array
     */
    public $remove = [];

    /**
     * Constructor
     *
     * @param array $set
     * @param array $remove
     */
    function __construct(array $set, array $remove) {

        $this->set = $set;
        $this->remove = $remove;

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
     * You are responsible for advancing the reader to the next element. Not
     * doing anything will result in a never-ending loop.
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
    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree([
            '{DAV:}set-invitee'    => 'Sabre\\Xml\\Element\\KeyValue',
            '{DAV:}remove-invitee' => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $set = [];
        $remove = [];

        foreach ($elems as $elem) {
            switch ($elem['name']) {

                case '{DAV:}set-invitee' :
                    $sharee = $elem['value'];

                    $setInvitee = [
                        'href' => null,
                        'comment' => null,
                        'readOnly' => false,
                    ];
                    foreach($sharee as $key=>$value) {

                        switch($key) {

                            case '{DAV:}href' :
                                $setInvitee['href'] = $value;
                                break;
                            case '{DAV:}comment' :
                                $setInvitee['comment'] = $value;
                                break;
                            case '{DAV:}read' :
                                $setInvitee['readOnly'] = true;
                                break;
                            case '{DAV:}read-write' :
                                $setInvitee['readOnly'] = false;
                                break;
                            default :
                                $setInvitee[$key] = $value;
                                break;

                        } 

                    }
                    $set[] = $setInvitee;
                    break;

                case '{DAV:}remove-invitee' :
                    $remove[] = $elem['value']['{DAV:}href'];
                    break;

            }
        }

        return new self($set, $remove);

    }

}
