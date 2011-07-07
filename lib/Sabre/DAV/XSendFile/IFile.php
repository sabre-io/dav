<?php

/**
 * This interface extends the IFile interface for X-Sendfile support
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Markus Koller
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_XSendFile_IFile extends Sabre_DAV_IFile {

    /**
     * Returns the physical path of the file
     *
     * @return string
     */
    function getPhysicalPath();

}

