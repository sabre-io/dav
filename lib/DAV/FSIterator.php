<?php

namespace Sabre\DAV;

use CallbackFilterIterator;
use FilesystemIterator;

/**
 * FS iterator.
 *
 * Transform any children of a directory into a collection of nodes.
 *
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Ivan Enderlin
 * @license http://sabre.io/license/ Modified BSD License
 */
class FSIterator extends CallbackFilterIterator {

    /**
     * Collection to iterate.
     *
     * @var ICollection
     */
    protected $collection;

    /**
     * Constructor.
     *
     * Based on a collection and a path, we build an iterator that transforms
     * every children of the path in the collection into a node. A filter can be
     * added and receive a \FilesystemIterator object as the only argument, i.e.
     * the child is not yet a node.
     *
     * @param ICollection $collection
     * @param string $path
     * @param callable|null $filter
     */
    public function __construct(ICollection $collection, $path, callable $filter = null) {

        $this->setCollection($collection);

        if (is_null($filter)) {
            $filter = function ( ) { return true; };
        }

        parent::__construct(
            new FilesystemIterator(
                $path,
                FilesystemIterator::CURRENT_AS_SELF | FilesystemIterator::SKIP_DOTS
            ),
            $filter
        );

    }

    /**
     * Get the current element as a node.
     *
     * @return INode
     */
    public function current() {

        return $this->getCollection()->getChild(
            parent::current()->getFilename()
        );

    }

    /**
     * Set the collection handler.
     *
     * @param ICollection $collection
     */
    protected function setCollection(ICollection $collection) {

        $this->collection = $collection;

    }

    /**
     * Get the collection handler.
     *
     * @return ICollection
     */
    public function getCollection() {

        return $this->collection;

    }

}
