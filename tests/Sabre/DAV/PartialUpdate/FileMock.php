<?php

declare(strict_types=1);

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;

class FileMock implements DAV\IFile, IPatchSupport
{
    protected $data = '';

    public function put($str)
    {
        if (is_resource($str)) {
            $str = stream_get_contents($str);
        }
        $this->data = $str;
    }

    /**
     * Updates the file based on a range specification.
     *
     * The first argument is the data, which is either a readable stream
     * resource or a string.
     *
     * The second argument is the type of update we're doing.
     * This is either:
     * * 1. append
     * * 2. update based on a start byte
     * * 3. update based on an end byte
     *;
     * The third argument is the start or end byte.
     *
     * After a successful put operation, you may choose to return an ETag. The
     * etag must always be surrounded by double-quotes. These quotes must
     * appear in the actual string you're returning.
     *
     * Clients may use the ETag from a PUT request to later on make sure that
     * when they update the file, the contents haven't changed in the mean
     * time.
     *
     * @param resource|string $data
     * @param int             $rangeType
     * @param int             $offset
     *
     * @return string|null
     */
    public function patch($data, $rangeType, $offset = null)
    {
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        switch ($rangeType) {
            case 1:
                $this->data .= $data;
                break;
            case 3:
                // Turn the offset into an offset-offset.
                $offset = strlen($this->data) - $offset;
                // no break is intentional
            case 2:
                $this->data =
                    substr($this->data, 0, $offset).
                    $data.
                    substr($this->data, $offset + strlen($data));
                break;
        }
    }

    public function get()
    {
        return $this->data;
    }

    public function getContentType()
    {
        return 'text/plain';
    }

    public function getSize()
    {
        return strlen($this->data);
    }

    public function getETag()
    {
        return '"'.$this->data.'"';
    }

    public function delete()
    {
        throw new DAV\Exception\MethodNotAllowed();
    }

    public function setName($name)
    {
        throw new DAV\Exception\MethodNotAllowed();
    }

    public function getName()
    {
        return 'partial';
    }

    public function getLastModified()
    {
        return null;
    }
}
