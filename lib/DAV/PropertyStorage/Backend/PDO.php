<?php

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;

class PDO implements BackendInterface {

    /**
     * PDO
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Creates the PDO property storage engine
     *
     * @param \PDO $pdo
     */
    function __construct(\PDO $pdo) {

        $this->pdo = $pdo;

    }

    /**
     * Fetches properties for a path.
     *
     * This method received a PropFind object, which contains all the
     * information about the properties that need to be fetched.
     *
     * Ususually you would just want to call 'get404Properties' on this object,
     * as this will give you the _exact_ list of properties that need to be
     * fetched, and haven't yet.
     *
     * @param string $path
     * @param PropFind $propFind
     * @return void
     */
    function propFind($path, PropFind $propFind) {

        $propertyNames = $propFind->get404Properties();
        if (!$propertyNames) {
            return;
        }

        $query = 'SELECT name, value FROM propertystorage WHERE path = ?';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$path]);

        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $propFind->set($row['name'], $row['value']);
        }

    }

    /**
     * Updates properties for a path
     *
     * This method received a PropPatch object, which contains all the
     * information about the update.
     *
     * Usually you would want to call 'handleRemaining' on this object, to get;
     * a list of all properties that need to be stored.
     *
     * @param string $path
     * @param PropPatch $propPatch
     * @return void
     */
    function propPatch($path, PropPatch $propPatch) {

        $propPatch->handleRemaining(function($properties) use ($path) {

            $updateStmt = $this->pdo->prepare("REPLACE INTO propertystorage (path, name, value) VALUES (?, ?, ?)");
            $deleteStmt = $this->pdo->prepare("DELETE FROM propertystorage WHERE path = ? AND name = ?");

            foreach($properties as $name=>$value) {

                if (!is_null($value)) {
                    $updateStmt->execute([$path, $name, $value]);
                } else {
                    $deleteStmt->execute([$path, $name]);
                }

            }

            return true;

        });

    }

    /**
     * This method is called after a node is deleted.
     *
     * This allows a backend to clean up all associated properties.
     */
    function delete($path) {

        $stmt = $this->pdo->prepare("DELETE FROM propertystorage WHERE path = ?");
        $stmt->execute([$path]);

    }

    /**
     * This method is called after a successful MOVE
     *
     * This should be used to migrate all properties from one path to another.
     * Note that entire collections may be moved, so ensure that all properties
     * for children are also moved along.
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    function move($source, $destination) {

        // I don't know a way to write this all in a single sql query that's
        // also compatible across db engines, so we're letting PHP do all the
        // updates. Much slower, but it should still be pretty fast in most
        // cases.
        $select = $this->pdo->prepare('SELECT id, path FROM propertystorage WHERE path = ? OR path LIKE ?');
        $select->execute([$source, $source . '/%']);

        $update = $this->pdo->prepare('UPDATE propertystorage SET path = ? WHERE id = ?');
        while($row = $select->fetch(\PDO::FETCH_ASSOC)) {

            // Sanity check. SQL may select too many records, such as records
            // with different cases.
            if ($row['path'] !== $source && strpos($row['path'], $source . '/')!==0) continue;

            $trailingPart = substr($row['path'], strlen($source)+1);
            $newPath = $destination;
            if ($trailingPart) {
                $newPath.='/' . $trailingPart;
            }
            $update->execute([$newPath, $row['id']]);

        }

    }

}
