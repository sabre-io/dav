<?php

/**
 * PDO principal backend
 *
 * This is a simple principal backend that maps exactly to the users table, as 
 * used by Sabre_DAV_Auth_Backend_PDO.
 *
 * It assumes all principals are in a single collection. The default collection 
 * is 'principals/', but this can be overriden.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_PrincipalBackend_PDO implements Sabre_DAVACL_IPrincipalBackend {

    /**
     * Principals prefix 
     * 
     * @var string
     */
    public $prefix = 'principals';

    /**
     * pdo 
     * 
     * @var PDO 
     */
    protected $pdo;

    /**
     * Sets up the backend 
     * 
     * @param PDO $pdo 
     */
    public function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    } 


    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only 
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can 
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname 
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV 
     *     field that's actualy injected in a number of other properties. If
     *     you have an email address, use this property.
     * 
     * @param string $prefixPath 
     * @return array 
     */
    public function getPrincipalsByPrefix($prefixPath) {

        // This backend only support principals in one collection
        if ($prefixPath !== $this->prefix) return array();

        $result = $this->pdo->query('SELECT username, email, displayname FROM users');

        $users = array();

        while($row = $result->fetch(PDO::FETCH_ASSOC)) {

            $users[] = array(
                'uri' => $this->prefix . '/' . $row['username'],
                '{DAV:}displayname' => $row['displayname']?$row['displayname']:$row['username'],
                '{http://sabredav.org/ns}email-address' => $row['email'],
            );

        }

        return $users;

    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from 
     * getPrincipalsByPrefix. 
     * 
     * @param string $path 
     * @return array 
     */
    public function getPrincipalByPath($path) {

        list($prefixPath, $userName) = Sabre_DAV_URLUtil::splitPath($path);

        // This backend only support principals in one collection
        if ($prefixPath !== $this->prefix) return null; 

        $stmt = $this->pdo->prepare('SELECT username, email, displayname FROM users WHERE username = ?');
        $stmt->execute(array($userName));

        $users = array();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return;

        return array(
            'uri' => $this->prefix . '/' . $row['username'],
            '{DAV:}displayname' => $row['displayname']?$row['displayname']:$row['username'],
            '{http://sabredav.org/ns}email-address' => $row['email'],
        );

    }

}
