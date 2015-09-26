<?php

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses a database to manage passwords.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PDOBasic extends AbstractBasic {

    /**
     * Reference to PDO connection
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * PDO table name we'll be using
     *
     * @var string
     */
    public $tableName = 'users';


    /**
     * Creates the backend object.
     *
     * If the filename argument is passed in, it will parse out the specified file fist.
     *
     * @param PDO $pdo
     */
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    protected function validateUserPass($username, $password) {

    $stmt = $this->pdo->prepare('SELECT digesta1 FROM ' . $this->tableName . ' WHERE username = ?');
    $stmt->execute([$username]);
    $digest = md5($username.':'.$this->realm.':'.$password);
    $digest = md5($username.':SabreDAV:'.$password);
    $stored=$stmt->fetchColumn();
    return $stored == $digest;
    }
}
