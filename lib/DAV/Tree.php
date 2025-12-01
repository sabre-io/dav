<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\Uri;

/**
 * The tree object is responsible for basic tree operations.
 *
 * It allows for fetching nodes by path, facilitates deleting, copying and
 * moving.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Tree implements INodeByPath
{
    /**
     * The root node.
     *
     * @var ICollection
     */
    protected $rootNode;

    /**
     * This is the node cache. Accessed nodes are stored here.
     * Arrays keys are path names, values are the actual nodes.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Creates the object.
     *
     * This method expects the rootObject to be passed as a parameter
     */
    public function __construct(ICollection $rootNode)
    {
        $this->rootNode = $rootNode;
    }

    /**
     * Returns the INode object for the requested path.
     *
     * @param string $path
     *
     * @return INode
     */
    public function getNodeForPath($path)
    {
        $path = trim($path, '/');
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }

        // Is it the root node?
        if (!strlen($path)) {
            return $this->rootNode;
        }

        $node = $this->rootNode;

        // look for any cached parent and collect the parts below the parent
        $parts = [];
        $remainingPath = $path;
        do {
            list($remainingPath, $baseName) = Uri\split($remainingPath);
            array_unshift($parts, $baseName);

            if (isset($this->cache[$remainingPath])) {
                $node = $this->cache[$remainingPath];
                break;
            }
        } while ('' !== $remainingPath);

        while (count($parts)) {
            if (!($node instanceof ICollection)) {
                throw new Exception\NotFound('Could not find node at path: '.$path);
            }

            if ($node instanceof INodeByPath) {
                $targetNode = $node->getNodeForPath(implode('/', $parts));
                if ($targetNode instanceof INode) {
                    $node = $targetNode;
                    break;
                }
            }

            $part = array_shift($parts);
            if ('' !== $part) {
                $node = $node->getChild($part);
            }
        }

        $this->cache[$path] = $node;

        return $node;
    }

    /**
     * This function allows you to check if a node exists.
     *
     * Implementors of this class should override this method to make
     * it cheaper.
     *
     * @param string $path
     *
     * @return bool
     */
    public function nodeExists($path)
    {
        try {
            // The root always exists
            if ('' === $path) {
                return true;
            }

            list($parent, $base) = Uri\split($path);

            $parentNode = $this->getNodeForPath($parent);
            if (!$parentNode instanceof ICollection) {
                return false;
            }

            return $parentNode->childExists($base);
        } catch (Exception\NotFound $e) {
            return false;
        }
    }

    /**
     * Copies a file from path to another.
     *
     * @param string $sourcePath      The source location
     * @param string $destinationPath The full destination path
     * @param int    $depth           How many levels of children to copy.
     *                                The value can be 'infinity' (\Sabre\DAV\Server::DEPTH_INFINITY) or a positive integer, including zero.
     *                                Zero means only copy the collection without children but with its properties.
     */
    public function copy($sourcePath, $destinationPath, int $depth = Server::DEPTH_INFINITY)
    {
        $sourceNode = $this->getNodeForPath($sourcePath);

        // grab the dirname and basename components
        list($destinationDir, $destinationName) = Uri\split($destinationPath);

        $destinationParent = $this->getNodeForPath($destinationDir);
        // Check if the target can handle the copy itself. If not, we do it ourselves.
        if (!$destinationParent instanceof ICopyTarget || !$destinationParent->copyInto($destinationName, $sourcePath, $sourceNode, $depth)) {
            $this->copyNode($sourceNode, $destinationParent, $destinationName, $depth);
        }

        $this->markDirty($destinationDir);
    }

    /**
     * Moves a file from one location to another.
     *
     * @param string $sourcePath      The path to the file which should be moved
     * @param string $destinationPath The full destination path, so not just the destination parent node
     */
    public function move($sourcePath, $destinationPath)
    {
        list($sourceDir) = Uri\split($sourcePath);
        list($destinationDir, $destinationName) = Uri\split($destinationPath);

        if ($sourceDir === $destinationDir) {
            // If this is a 'local' rename, it means we can just trigger a rename.
            $sourceNode = $this->getNodeForPath($sourcePath);
            $sourceNode->setName($destinationName);
        } else {
            $newParentNode = $this->getNodeForPath($destinationDir);
            $moveSuccess = false;
            if ($newParentNode instanceof IMoveTarget) {
                // The target collection may be able to handle the move
                $sourceNode = $this->getNodeForPath($sourcePath);
                $moveSuccess = $newParentNode->moveInto($destinationName, $sourcePath, $sourceNode);
            }
            if (!$moveSuccess) {
                // Move is a copy with depth = infinity and deleting the source afterwards
                $this->copy($sourcePath, $destinationPath, Server::DEPTH_INFINITY);
                $this->getNodeForPath($sourcePath)->delete();
            }
        }
        $this->markDirty($sourceDir);
        $this->markDirty($destinationDir);
    }

    /**
     * Deletes a node from the tree.
     *
     * @param string $path
     */
    public function delete($path)
    {
        $node = $this->getNodeForPath($path);
        $node->delete();

        list($parent) = Uri\split($path);
        $this->markDirty($parent);
    }

    /**
     * Returns a list of childnodes for a given path.
     *
     * @param string $path
     *
     * @return \Traversable
     */
    public function getChildren($path)
    {
        $node = $this->getNodeForPath($path);
        $basePath = trim($path, '/');
        if ('' !== $basePath) {
            $basePath .= '/';
        }

        if ($node instanceof ICollection) {
            foreach ($node->getChildren() as $child) {
                $this->cache[$basePath.$child->getName()] = $child;
                yield $child;
            }
        } else {
            yield from [];
        }
    }

    /**
     * This method is called with every tree update.
     *
     * Examples of tree updates are:
     *   * node deletions
     *   * node creations
     *   * copy
     *   * move
     *   * renaming nodes
     *
     * If Tree classes implement a form of caching, this will allow
     * them to make sure caches will be expired.
     *
     * If a path is passed, it is assumed that the entire subtree is dirty
     *
     * @param string $path
     */
    public function markDirty($path)
    {
        // We don't care enough about sub-paths
        // flushing the entire cache
        $path = trim($path, '/');
        foreach ($this->cache as $nodePath => $node) {
            if ('' === $path || $nodePath == $path || 0 === strpos((string) $nodePath, $path.'/')) {
                unset($this->cache[$nodePath]);
            }
        }
    }

    /**
     * This method tells the tree system to pre-fetch and cache a list of
     * children of a single parent.
     *
     * There are a bunch of operations in the WebDAV stack that request many
     * children (based on uris), and sometimes fetching many at once can
     * optimize this.
     *
     * This method returns an array with the found nodes. It's keys are the
     * original paths. The result may be out of order.
     *
     * @param array $paths list of nodes that must be fetched
     *
     * @return array
     */
    public function getMultipleNodes($paths)
    {
        // Finding common parents
        $parents = [];
        foreach ($paths as $path) {
            list($parent, $node) = Uri\split($path);
            if (!isset($parents[$parent])) {
                $parents[$parent] = [$node];
            } else {
                $parents[$parent][] = $node;
            }
        }

        $result = [];

        foreach ($parents as $parent => $children) {
            $parentNode = $this->getNodeForPath($parent);
            if ($parentNode instanceof IMultiGet) {
                foreach ($parentNode->getMultipleChildren($children) as $childNode) {
                    $fullPath = $parent.'/'.$childNode->getName();
                    $result[$fullPath] = $childNode;
                    $this->cache[$fullPath] = $childNode;
                }
            } else {
                foreach ($children as $child) {
                    $fullPath = $parent.'/'.$child;
                    $result[$fullPath] = $this->getNodeForPath($fullPath);
                }
            }
        }

        return $result;
    }

    /**
     * copyNode.
     *
     * @param string $destinationName
     * @param int    $depth           How many children of the node to copy
     */
    protected function copyNode(INode $source, ICollection $destinationParent, ?string $destinationName = null, int $depth = Server::DEPTH_INFINITY)
    {
        if ('' === (string) $destinationName) {
            $destinationName = $source->getName();
        }

        $destination = null;

        if ($source instanceof IFile) {
            $data = $source->get();

            // If the body was a string, we need to convert it to a stream
            if (is_string($data)) {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $data);
                rewind($stream);
                $data = $stream;
            }
            $destinationParent->createFile($destinationName, $data);
            $destination = $destinationParent->getChild($destinationName);
        } elseif ($source instanceof ICollection) {
            $destinationParent->createDirectory($destinationName);
            $destination = $destinationParent->getChild($destinationName);

            // Copy children if depth is not zero
            if (0 !== $depth) {
                // Adjust next depth for children (keep 'infinity' or decrease)
                $depth = Server::DEPTH_INFINITY === $depth ? Server::DEPTH_INFINITY : $depth - 1;
                $destination = $destinationParent->getChild($destinationName);
                foreach ($source->getChildren() as $child) {
                    $this->copyNode($child, $destination, null, $depth);
                }
            }
        }
        if ($source instanceof IProperties && $destination instanceof IProperties) {
            $props = $source->getProperties([]);
            $propPatch = new PropPatch($props);
            $destination->propPatch($propPatch);
            $propPatch->commit();
        }
    }
}
