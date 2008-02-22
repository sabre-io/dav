<?php

    /**
     * Lock class
     *
     * An object of the Lock class represents a lock on a node. Its a basic Data Transfer Object, that can easily be serialized and stored
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007, 2008 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */
    class Sabre_DAV_Lock {

        /**
         * A shared lock
         */
        const SHARED = 1;

        /**
         * An exclusive lock
         */
        const EXCLUSIVE = 2;

        /**
         * The owner of the lock 
         * 
         * @var string 
         */
        public $owner;

        /**
         * The locktoken 
         * 
         * @var string 
         */
        public $token;

        /**
         * How long till the lock is expiring 
         * 
         * @var int 
         */
        public $timeout;

        /**
         * The locktype, use the SHARED and EXCLUSIVE constants for this 
         * 
         * @var int 
         */
        public $lockType = self::EXCLUSIVE;

    }

?>
