<?php

/**
 * This interface represents a file or leaf in the tree.
 *
 * The nature of a file is, as you might be aware of, that it doesn't contain sub-nodes and has contents
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_IFile extends Sabre_DAV_INode {

    /**
     * Updates the data 
     * 
     * The data argument is a readable stream resource.
     *
     * @param resource $data 
     * @return void 
     */
    function put($data);

    /**
     * Returns the data 
     * 
     * This method may either return a string or a readable stream resource
     *
     * @return mixed 
     */
    function get();

}

