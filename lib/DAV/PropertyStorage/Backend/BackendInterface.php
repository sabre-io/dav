<?php

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;

interface BackendInterface {

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
    public function propFind($path, PropFind $propFind);

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
    public function propPatch($path, PropPatch $propPatch);

    /**
     * This method is called after a node is deleted.
     *
     * This allows a backend to clean up all associated properties.
     */
    public function delete($path);

}
