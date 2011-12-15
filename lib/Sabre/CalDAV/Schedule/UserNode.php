<?php

/**
 * CalDAV Scheduling User Node
 *
 * This node contains the inbox and outbox for a single user.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Schedule_UserNode extends Sabre_DAV_Directory {

    protected $principalUri;

    /**
     * Constructor
     *
     * @param string $principalUri
     */
    public function __construct($principalUri) {

        $this->principalUri = $principalUri;

    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the uri.
     *
     * @return string
     */
    public function getName() {

        return basename($this->principalUri);

    }

    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren() {

        return array(
            new Sabre_CalDAV_Schedule_Inbox($this->principalUri),
            new Sabre_CalDAV_Schedule_Outbox($this->principalUri),
        );

    }

}
