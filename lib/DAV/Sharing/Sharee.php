<?php

namespace Sabre\DAV\Sharing;

/**
 * This class acts as a value object for sharees. It's used to send around
 * information about sharees.
 *
 * @copyright Copyright (C) fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Sharee {

    /**
     * A URL. Usually a mailto: address, could also be a principal url.
     * This uniquely identifies the sharee.
     *
     * @var string
     */
    public $href;

    /**
     * A list of WebDAV properties that describe the sharee. This might for
     * example contain a {DAV:}displayname with the real name of the user.
     *
     * @var array
     */
    public $properties = [];

    /**
     * Share access level. One of the Sabre\DAV\Sharing\Plugin::ACCESS
     * constants.
     *
     * Can be one of:
     *
     * ACCESS_READ
     * ACCESS_READWRITE
     * ACCESS_SHAREDOWNER
     * ACCESS_NOACCESS
     * 
     * depending on context.
     *
     * @var int
     */
    public $shareAccess;

    /**
     * When a sharee is originally invited to a share, the sharer may add
     * a comment. This will be placed in this property.
     *
     * @var string
     */
    public $comment;

    /**
     * The status of the invite, should be one of the
     * Sabre\DAV\Sharing\Plugin::INVITE constants.
     *
     * @var int
     */
    public $invite; 

}
