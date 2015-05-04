<?php

namespace Sabre\DAVSharing\Xml\Request;

use Sabre\Xml\ParseException;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * This class is responsible for parsing the {DAV:}share-resource element.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ShareResource implements XmlDeserializable {

    /**
     * This contains the list of new invitees for the share.
     *
     * Each item has the following propertis
     *   1. href - uri to the principal or email address who's being modified.
     *   2. displayname - a human-readable name for the person
     *   3. comment - a special optional note that will be sent to the user
     *      when inviting or removing the user.
     *   4. access - 1 = read-write, 2 = read-only, 3 = remove.
     *
     * @var array
     */
    public $mutations = [];

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

        $elementMap = [
            '{DAV:}set-invitee' => 'Sabre\Xml\Element\KeyValue',
            '{DAV:}remove-invitee' => 'Sabre\Xml\Element\KeyValue',
        ];
        $result = $this->parseInnerTree($elementMap);

        $mutations = [];

        foreach($result as $row) {

            $access = 0; // no value for access specified yet.


            if ($row['name'] === '{DAV:}remove-invitee') {
                $access = 3; // remove
            } elseif ($row['name'] !== '{DAV:}set-invitee') {
                // Unknown element, lets skip it.
                continue;
            }
            if (array_key_exists('{DAV:}read-write', $row['value'])) {
                $access = 1;
            } elseif (array_key_exists('{DAV:}read', $row['value'])) {
                $access = 2;
            }

            $comment = isset($row['value']['{DAV:}comment']) ? $row['value']['{DAV:}comment'] : null;
            $displayName = isset($row['value']['{DAV:}displayname']) ? $row['value']['{DAV:}displayname'] : null;

            if (!isset($row['value']['{DAV:}href'])) {
                throw new ParseException('Every set-invitee and remove-invitee element must have a {DAV:}href child element');
            }
            $href = $row['value']['{DAV:}href'];

            if ($access === 0) {
                throw new ParseException('{DAV:}read-write or {DAV:}read must be specified for every {DAV:}set-invitee');
            }
            $mutations[] = [
                'href' => $href,
                'comment' => $comment,
                'access' => $access,
                'displayName' => $displayName
            ];

        }

        $new = new self();
        $new->mutations = $mutations;

        return $new;

    }

}
