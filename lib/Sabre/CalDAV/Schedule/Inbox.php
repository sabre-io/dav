<?php

/**
 * The CalDAV scheduling inbox
 *
 * The inbox may contain a list of new invites from different users.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Schedule_Inbox extends Sabre_DAV_Directory implements Sabre_CalDAV_Schedule_IInbox {

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url. 
     * 
     * @return string 
     */
    public function getName() {

        return 'inbox';

    }

    /**
     * Returns an array with all the child nodes 
     * 
     * @return Sabre_DAV_INode[] 
     */
    public function getChildren() {

        return array();

    }

}

?>
