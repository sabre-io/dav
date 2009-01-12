<?php

/**
 * Lock class
 *
 * An object of the Lock class represents a lock on a node. Its a basic Data Transfer Object, that can easily be serialized and stored
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
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
    public $scope = self::EXCLUSIVE;

    /**
     * Depth of lock, can be 0 or Sabre_DAV_Server::DEPTH_INFINITY
     */
    public $depth = 0;

    /**
     * The uri this lock locks
     *
     * TODO: This value is not always set 
     * @var mixed
     */
    public $uri;

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
        $lockInfo->scope = isset($xml->lockscope->exclusive)?self::EXCLUSIVE:self::SHARED;

        return $lockInfo;

    }

}

