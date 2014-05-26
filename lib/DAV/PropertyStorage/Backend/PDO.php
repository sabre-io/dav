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
    public function __construct(\PDO $pdo) {

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
    public function propFind($path, PropFind $propFind) {

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
    public function propPatch($path, PropPatch $propPatch) {

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
    public function delete($path) {

        $stmt = $this->pdo->prepare("DELETE FROM propertystorage WHERE path = ?");
        $stmt->execute([$path]);

    }

}
