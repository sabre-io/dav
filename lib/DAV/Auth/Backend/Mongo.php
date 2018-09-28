<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses a Mongo database to manage passwords.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Linagora Folks (lgs-openpaas-dev@linagora.com)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Mongo extends AbstractBasic
{
    /**
     * Reference to MongoDb connection.
     *
     * @var MongoDB
     */
    protected $mongoDB;

    /**
     * MongoDB current user ID.
     *
     * @var string
     */
    protected $currentUserId;

    public function __construct($mongoDB)
    {
        $this->db = $mongoDB;
    }

    protected function validateUserPass($username, $password)
    {
        $query = ['username' => trim(strtolower($username))];
        $rec = $this->db->users->findOne($query, ['projection' => ['password' => 1, '_id' => 1]]);

        $authenticated = false;
        if ($rec) {
            $hash = $rec['password'];
            $authenticated = crypt($password, $hash) === $hash;
        }

        if ($authenticated) {
            $this->currentUserId = $rec['_id'];
        }

        return $authenticated;
    }
}
