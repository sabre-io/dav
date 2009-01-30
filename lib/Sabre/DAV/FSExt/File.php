<?php

/**
 * File class 
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_FSExt_File extends Sabre_DAV_FSExt_Node implements Sabre_DAV_IFile {

    /**
     * Updates the data 
     *
     * data is a readable stream resource.
     *
     * @param resource $data 
     * @return void 
     */
    public function put($data) {

        file_put_contents($this->path,$data);

    }

    /**
     * Returns the data
     *
     * @return string 
     */
    public function get() {

        return fopen($this->path,'r');

    }

    /**
     * Delete the current file
     *
     * @return void 
     */
    public function delete() {

        unlink($this->path);
        return parent::delete();

    }

}

