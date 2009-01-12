<?php

require_once 'Sabre/DAV/INode.php';

/**
 * Implement this class to support locking 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_ILockable extends Sabre_DAV_INode {

    /**
     * Returns an array with locks currently on the node 
     * 
     * @return Sabre_DAV_Lock[] 
     */
    function getLocks();

    /**
     * Creates a new lock on the file.  
     * 
     * @param Sabre_DAV_Lock $lockInfo The lock information 
     * @return void
     */
    function lock(Sabre_DAV_Lock $lockInfo);

    /**
     * Unlocks a file 
     * 
     * @param Sabre_DAV_Lock $lockInfo The lock information 
     * @return void 
     */
    function unlock(Sabre_DAV_Lock $lockInfo);

}

