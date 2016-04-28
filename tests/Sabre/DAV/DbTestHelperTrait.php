<?php

namespace Sabre\DAV;

use PDOException;
use PDO;

class DbCache {

    static $cache = [];

}

trait DbTestHelperTrait {

    /**
     * Should be "mysql", "pgsql", "sqlite".
     */
    public $driver = null;

    /**
     * Returns a fully configured PDO object.
     *
     * @return PDO
     */
    function getDb() {

        if (!$this->driver) {
            throw new \Exception('You must set the $driver public property');
        }

        if (isset(DbCache::$cache[$this->driver])) {
            return DbCache::$cache[$this->driver];
        } else {
        }

        try {

            switch ($this->driver) {

                case 'mysql' :
                    $pdo = new PDO(SABRE_MYSQLDSN, SABRE_MYSQLUSER, SABRE_MYSQLPASS);
                    break;
                case 'sqlite' :
                    $pdo = new \PDO('sqlite:' . SABRE_TEMPDIR . '/testdb');
                    break;
                case 'pgsql' :
                    $pdo = new \PDO(SABRE_PGSQLDSN);
                    break;



            }
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {

            $this->markTestSkipped($this->driver . ' was not enabled or not correctly configured. Error message: ' . $e->getMessage());

        }

        DbCache::$cache[$this->driver] = $pdo;
        return $pdo;

    }

    /**
     * Alias for getDb
     *
     * @return PDO
     */
    function getPDO() {

        return $this->getDb();

    }

    /**
     * Uses .sql files from the examples directory to initialize the database.
     */
    function createSchema($schemaName) {

        $db = $this->getDb();

        $queries = file_get_contents(
            __DIR__ . '/../../../examples/sql/' . $this->driver . '.' . $schemaName . '.sql'
        );

        foreach (explode(';', $queries) as $query) {

            if (trim($query) === '') {
                continue;
            }
            
            $db->exec($query);

        }

    }

    /**
     * Drops tables, if they exist
     *
     * @param string|string[] $tableNames
     * @return void
     */
    function dropTables($tableNames) {

        $tableNames = (array)$tableNames;
        $db = $this->getDb();
        foreach ($tableNames as $tableName) {
            $db->exec('DROP TABLE IF EXISTS ' . $tableName);
        }
        

    }

    function tearDown() {

        switch($this->driver) {

            case 'sqlite' :
                // Recreating sqlite, just in case
                unset(DbCache::$cache[$this->driver]);
                unlink(SABRE_TEMPDIR . '/testdb');
        }

    }

}
