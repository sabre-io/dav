<?php declare (strict_types=1);

namespace Sabre\DAV\FS;

use Sabre\DAV;

/**
 * File class
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class File extends Node implements DAV\IFile {

    /**
     * Updates the data
     *
     * @param resource $data
     * @return void
     */
    function put($data) {

        file_put_contents($this->path, $data);
        clearstatcache(true, $this->path);

    }

    /**
     * Returns the data
     *
     * @return resource
     */
    function get() {

        return fopen($this->path, 'r');

    }

    /**
     * Delete the current file
     *
     * @return void
     */
    function delete() {

        unlink($this->path);

    }

    /**
     * Returns the size of the node, in bytes
     *
     * @return int
     */
    function getSize() {

        $size = @filesize($this->path);
        if ($size === false && lstat($this->path) !== false) {

            // report broken symlink as zero, size of symlink
            // itself is most likely not helpful
            $size = 0;

        }
        return $size;

    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return mixed
     */
    function getETag() {

        $inode = @fileinode($this->path);
        if ($inode === false) {

            // broken symlink?
            $inode = lstat($this->path)['ino'];

        }

        return '"' . sha1(
            $inode .
            $this->getSize() .
            $this->getLastModified()
        ) . '"';

    }

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return mixed
     */
    function getContentType() {

        return null;

    }

}
