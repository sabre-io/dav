<?php

    require_once 'Sabre/DAV/IFile.php';

    /**
     * Implement this interface to enable locking support on a per-file basis 
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */
    interface Sabre_DAV_ILockable extends Sabre_DAV_IFile {

        /**
         * Requests a lock on the file, and returns a locktoken
         *
         * @param int $timeout How long to retain the lock in seconds. 0 is passed for 'infinite'
         * @param int $locktype 1 for shared, 2 for exclusive
         * @throws Sabre_DAV_LockedException
         * @return string This method should return a lock token.
         */
        function lock($timeout, $type); 

        /**
         * Unlocks a previously locked file.
         *
         * @param string $locktoken The token that was returned from lock()
         * @return bool This method should return false if it was an invalid lock. 
         */
        function unlock($locktoken); 

        /**
         * Returns a list of locks currently on the file
         *
         * The returned value should be a array, containing an array with the following keys: 
         *   * lockid
         *   * owner - string, optional
         *   * type - integer (1 for shared, 2 for exclusive)
         *   * timeout - integer, optional (how long till the lock times out, if it does)
         * 
         * @return void
         */
        function getLocks();

    }

?>
