<?php

/**
 * This class can be used to hook into your tree and override/alter actions
 *
 * This class is not intended to be used on its own, as it just proxies all requests to the sub-tree by default
 * To use it, subclass it and construct the object by passing your underlying tree in the constructor
 *
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Tree_Filter extends Sabre_DAV_Tree {

    /**
     * subject 
     * 
     * @var Sabre_DAV_Tree 
     */
    protected $subject;

    /**
     * Creates the filtertree 
     * 
     * @param Sabre_DAV_Tree $subject The tree object that needs to be filtered
     * @return void
     */
    public function __construct(Sabre_DAV_Tree $subject) {

        $this->subject = $subject;

    }

    /**
     * Copies a file from path to another
     *
     * @param string $sourcePath The source location
     * @param string $destinationPath The full destination path
     * @return int
     */
    public function copy($sourcePath,$destinationPath) {

        return $this->subject->copy($sourcePath,$destinationPath);

    }

    /**
     * Moves a file from one location to another 
     * 
     * @param string $sourcePath The path to the file which should be moved 
     * @param string $destinationPath The full destination path, so not just the destination parent node
     * @return int
     */
    public function move($sourcePath, $destinationPath) {

        return $this->subject->move($sourcePath,$destinationPath);

    }

}

