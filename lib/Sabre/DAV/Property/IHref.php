<?php

/**
 * IHref interface
 *
 * Any property implementing this interface can expose a related url.
 * This is used by certain subsystems to aquire more information about for example
 * the owner of a file
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_Property_IHref {

    /**
     * getHref
     *
     * @return string
     */
    function getHref();

}
