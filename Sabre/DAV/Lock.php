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
         * UNIX Timestamp of when this lock was created 
         * 
         * @var int 
         */
        public $created;

        /**
         * Exclusive or shared lock 
         * 
         * @var int 
         */
        public $lockScope = self::EXCLUSIVE;

        /**
         * Parses a webdav lock xml body, and returns a new Sabre_DAV_LockInfo object 
         * 
         * @param string $body 
         * @return Sabre_DAV_LockInfo
         */
        static function parseLockRequest($body) {

            $xml = simplexml_load_string($body,null,LIBXML_NOWARNING);
            $lockInfo = new self();
         
            $lockInfo->owner = (string)$xml->owner;

            $lockToken = '44445502';
            $id = md5(microtime() . 'somethingrandom');
            $lockToken.='-' . substr($id,0,4) . '-' . substr($id,4,4) . '-' . substr($id,8,4) . '-' . substr($id,12,12);

            $lockInfo->token = $lockToken;
            $lockInfo->lockScope = isset($xml->lockscope->exclusive);

            return $lockInfo;

        }

    }

?>
