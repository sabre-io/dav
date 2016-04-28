<?php

namespace Sabre\DAV;

use PDOException;
use PDO;

trait DbTestHelperTrait {

    /**
     * Should be "mysql", "pgsql", "sqlite".
     */
    public $driver = null;

    private $db = [];


    /**
     * Returns a fully configured PDO object.
     *
     * @return PDO
     */
    function getDb() {

        if (!$this->driver) {
            throw new \Exception('You must set the $driver public property');
       }

        if (isset($this->db[$this->driver])) {
            return $this->db[$this->driver];
        }

        try {

            switch ($this->driver) {

                case 'mysql' :
                    $pdo = new PDO(SABRE_MYSQLDSN, SABRE_MYSQLUSER, SABRE_MYSQLPASS);
                    break;
                case 'sqlite' :
                    $pdo = new \PDO('sqlite:' . SABRE_TEMPDIR . '/pdobackend');
                    break;
                case 'pgsql' :
                    $pdo = new \PDO(SABRE_PGSQLDSN);
                    break;



            }
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {

            $this->markTestSkipped($this->driver . ' was not enabled or not correctly configured. Error message: ' . $e->getMessage());

        }

        $this->db[$this->driver] = $pdo;
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

}
