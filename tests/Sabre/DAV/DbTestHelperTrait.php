<?php

declare(strict_types=1);

namespace Sabre\DAV;

use PDO;
use PDOException;
use Sabre\TestUtil;

class DbCache
{
    public static $cache = [];
}

trait DbTestHelperTrait
{
    /**
     * Should be "mysql", "pgsql", "sqlite".
     */
    public $driver = null;

    /**
     * Returns a fully configured PDO object.
     *
     * @return PDO
     */
    public function getDb()
    {
        if (!$this->driver) {
            throw new \Exception('You must set the $driver public property');
        }

        $pdo = null;

        if (array_key_exists($this->driver, DbCache::$cache)) {
            $pdo = DbCache::$cache[$this->driver];
            if (null === $pdo) {
                $this->markTestSkipped($this->driver.' was not enabled, not correctly configured or of the wrong version');
            }

            return $pdo;
        }

        try {
            switch ($this->driver) {
                case 'mysql':
                    $dsn = getenv('SABRE_MYSQLDSN');
                    $user = getenv('SABRE_MYSQLUSER');
                    $pass = getenv('SABRE_MYSQLPASS');
                    $pdo = new PDO($dsn, $user, $pass);
                    break;
                case 'sqlite':
                    $pdo = new PDO('sqlite:'.TestUtil::SABRE_TEMPDIR.'/testdb');
                    break;
                case 'pgsql':
                    $pdo = new PDO(getenv('SABRE_PGSQLDSN'));
                    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                    preg_match('|([0-9\.]){5,}|', $version, $matches);
                    $version = $matches[0];
                    if (version_compare($version, '9.5.0', '<')) {
                        DbCache::$cache[$this->driver] = null;
                        $this->markTestSkipped('We require at least Postgres 9.5. This server is running '.$version);
                    }
                    break;
            }

            if (null === $pdo) {
                $this->markTestSkipped($this->driver.' was not recognised');
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->markTestSkipped($this->driver.' was not enabled or not correctly configured. Error message: '.$e->getMessage());
        }

        DbCache::$cache[$this->driver] = $pdo;

        return $pdo;
    }

    /**
     * Alias for getDb.
     *
     * @return PDO
     */
    public function getPDO()
    {
        return $this->getDb();
    }

    /**
     * Uses .sql files from the examples directory to initialize the database.
     *
     * @param string $schemaName
     */
    public function createSchema($schemaName)
    {
        $db = $this->getDb();

        $queries = file_get_contents(
            __DIR__.'/../../../examples/sql/'.$this->driver.'.'.$schemaName.'.sql'
        );

        foreach (explode(';', $queries) as $query) {
            if ('' === trim($query)) {
                continue;
            }

            $db->exec($query);
        }
    }

    /**
     * Drops tables, if they exist.
     *
     * @param string|string[] $tableNames
     */
    public function dropTables($tableNames)
    {
        $tableNames = (array) $tableNames;
        $db = $this->getDb();
        foreach ($tableNames as $tableName) {
            $db->exec('DROP TABLE IF EXISTS '.$tableName);
        }
    }

    public function tearDown(): void
    {
        switch ($this->driver) {
            case 'sqlite':
                // Recreating sqlite, just in case
                unset(DbCache::$cache[$this->driver]);
                unlink(TestUtil::SABRE_TEMPDIR.'/testdb');
        }
    }
}
