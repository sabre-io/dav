<?php

namespace Sabre\DAV;

class PropFindIterator implements \Iterator, \ArrayAccess
{
    /**
     * @var PropFind
     */
    private $propFind;

    /**
     * @var \SeekableIterator
     */
    private $nodes;

    /**
     * @var Server
     */
    private $server;

    public function __construct(PropFind $propFind, \SeekableIterator $nodes, Server $server)
    {
        $this->propFind = $propFind;
        $this->nodes = $nodes;
        $this->server = $server;
    }

    public function current()
    {
        /**
         * @var INode $node
         * @var string $path;
         */
        list($node, $path) = $this->nodes->current();
        $this->propFind->setPath($path);
        $r = $this->server->getPropertiesByNode($this->propFind, $node);
        if ($r) {
            $result = $this->propFind->getResultForMultiStatus();
            $result['href'] = $this->propFind->getPath();

            // WebDAV recommends adding a slash to the path, if the path is
            // a collection.
            // Furthermore, iCal also demands this to be the case for
            // principals. This is non-standard, but we support it.
            $resourceType = $this->server->getResourceTypeForNode($node);
            if (in_array('{DAV:}collection', $resourceType) || in_array('{DAV:}principal', $resourceType)) {
                $result['href'] .= '/';
            }
            return $result;
        }
        return [];
    }

    public function key()
    {
        return $this->nodes->key();
    }

    public function next()
    {
        $this->nodes->next();
    }

    public function rewind()
    {
        $this->nodes->rewind();
    }

    public function valid()
    {
        return $this->nodes->valid();
    }

    public function offsetSet($offset, $value)
    {
        throw new Exception();
    }

    public function offsetExists($offset)
    {
        $current = $this->nodes->key();
        try {
            $this->nodes->seek($offset);
            $exists = true;
        } catch (\OutOfBoundsException $e) {
            $exists = false;
        }
        $this->nodes->seek($current);
        return $exists;
    }

    public function offsetUnset($offset)
    {
        throw new Exception();
    }

    public function offsetGet($offset)
    {
        $current = $this->nodes->key();
        $this->nodes->seek($offset);
        $result = $this->current();
        $this->nodes->seek($current);
        return $result;
    }
}
