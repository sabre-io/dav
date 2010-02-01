<?php

/**
 * ACL aware node
 *
 * This interface can be implemented by ACL-aware nodes,
 * allowing them to supply information about access control.
 * 
 * @package Sabre
 * @subpackage DAVACL
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAVACL_IAclNode extends Sabre_DAV_INode {

    /**
     * Returns the owner of the file
     *
     * This function MUST return either NULL, or a valid
     * principal resource uri.
     * 
     * @return mixed 
     */
    function getOwner();

}
