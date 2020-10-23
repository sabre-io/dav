<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses a database to manage passwords.
 * Based on the PDO backend which uses digest auth.
 *
 * @author Jeremy Symon
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class BasicPDO extends AbstractBasic
{
    /**
     * Reference to PDO connection.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * PDO table name we'll be using.
     *
     * @var string
     */
    public $tableName = 'users';

    /**
     * Creates the backend object.
     *
     * If the filename argument is passed in, it will parse out the specified file fist.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Checks whether a username/password pair is valid
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function validateUserPass($username, $password)
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM '.$this->tableName.' WHERE username = ?');
        $stmt->execute([$username]);

        $password_hash = $stmt->fetchColumn() ?: null;
        return password_verify($password, $password_hash); // fails on null password_hash
    }
}
