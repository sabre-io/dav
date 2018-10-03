<?php

declare(strict_types=1);

namespace Sabre\DAV;

class MongoCache
{
    public static $db = null;
}

trait MongoTestHelperTrait
{
    /**
     * Should be "mongodb".
     */
    public $driver = null;

    /**
     * Returns a fully configured Mongo client object.
     *
     * @return \MongoDB\Database
     */
    public function getDb()
    {
        if (!$this->driver) {
            throw new \Exception('You must set the $driver public property');
        }

        if (MongoCache::$db) {
            return MongoCache::$db;
        }

        try {
            $mongo = new \MongoDB\Client(SABRE_MONGO_SABREURI);

            // Connection check
            $mongo->listDatabases();

            $db = $mongo->{SABRE_MONGO_SABREDB};
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            $this->markTestSkipped($this->driver.' was not enabled or not correctly configured. Error message: '.$e->getMessage());
        }

        MongoCache::$db = $db;

        return $db;
    }

    /**
     * Alias for getDb.
     *
     * @return \MongoDB\Database
     */
    public function getMongo()
    {
        return $this->getDb();
    }
}
