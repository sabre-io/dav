<?php

namespace Sabre\DAV\PropertyStorage;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\PropPatch;
use Sabre\DAV\PropFind;
use Sabre\DAV\INode;

class Plugin extends ServerPlugin {

    /**
     * Creates the plugin
     *
     * @param Backend\BackendInterface $backend
     */
    public function __construct(Backend\BackendInterface $backend) {

        $this->backend = $backend;

    }

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param Server $server
     * @return void
     */
    public function initialize(Server $server) {

        $server->on('propFind', [$this, 'propFind'], 101);
        $server->on('propPatch', [$this, 'propPatch'], 300);
        $server->on('afterUnbind', [$this, 'afterUnbind']);

    }

    /**
     * Called during PROPFIND operations.
     *
     * If there's any requested properties that don't have a value yet, this
     * plugin will look in the property storage backend to find them.
     *
     * @param PropFind $propFind
     * @param INode $node
     * @return void
     */
    public function propFind(PropFind $propFind, INode $node) {

        $this->backend->propFind($propFind->getPath(), $propFind);

    }

    /**
     * Called during PROPPATCH operations
     *
     * If there's any updated properties that haven't been stored, the
     * propertystorage backend can handle it.
     *
     * @param string $path
     * @param PropPatch $propPatch
     * @return void
     */
    public function propPatch($path, PropPatch $propPatch) {

        $this->backend->propPatch($path, $propPatch);

    }

    /**
     * Called after a node is deleted.
     *
     * This allows the backend to clean up any properties still in the
     * database.
     *
     * @param string $path
     * @return void
     */
    public function afterUnbind($path) {

        $this->backend->delete($path);

    }

}
