<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * By implementing this interface, a collection can effectively say "other
 * nodes may be copied into this collection".
 *
 * If a backend supports a better optimized copy operation, e.g. by avoiding
 * copying the contents, this can trigger some huge speed gains.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ICopyTarget extends ICollection
{
    /**
     * Copies a node into this collection.
     *
     * It is up to the implementors to:
     *   1. Create the new resource.
     *   2. Copy the data and any properties.
     *
     * If you return true from this function, the assumption
     * is that the copy was successful.
     * If you return false, sabre/dav will handle the copy itself.
     *
     * @param string $targetName new local file/collection name
     * @param string $sourcePath Full path to source node
     * @param INode  $sourceNode Source node itself
     * @param string|int $depth How many level of children to copy.
     *                          The value can be 'infinity' or a positiv number including zero.
     *                          Zero means to only copy a shallow collection with props but without children.
     *
     * @return bool
     */
    public function copyInto($targetName, $sourcePath, INode $sourceNode, $depth);
}
