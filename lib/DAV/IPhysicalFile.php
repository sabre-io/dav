<?php

namespace Sabre\DAV;

/**
 * IPhysicalFile
 *
 * This interface is for Files that have a physical representation on disk. So 
 * instead of a virtual file that can represent anything, this is a literal 
 * file on this system.
 *
 * Implementing this interface is optional, but if it is implemented, it may 
 * allow for optimizations that are otherwise not possible, such as support for 
 * X-SendFile. 
 * 
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface IPhysicalFile extends IFile {

    /**
     * getPhysicalPath 
     * 
     * @return string 
     */
    public function getPhysicalPath();

}
