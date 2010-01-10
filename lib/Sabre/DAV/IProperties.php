<?php

/**
 * IProperties interface
 *
 * Implement this interface to support custom WebDAV properties requested and sent from clients.
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_IProperties extends Sabre_DAV_INode {

    /**
     * Updates properties on this node,
     *
     * The mutations array, contains arrays with mutation information, with the following 3 elements:
     *   * 0 = mutationtype (1 for set, 2 for remove)
     *   * 1 = nodename (encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     *   * 2 = value, can either be a string or a DOMElement
     * 
     * This method should return a similar array, with information about every mutation:
     *   * 0 - nodename, encoded as in the $mutations argument
     *   * 1 - statuscode, encoded as http status code, for example
     *      200 for an updated property or succesful delete
     *      201 for a new property
     *      403 for permission denied
     *      etc..
     *
     * @param array $mutations 
     * @return void
     */
    function updateProperties($mutations);

    /**
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested, encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     * If the array is empty, all properties should be returned
     *
     * @param array $properties 
     * @return void
     */
    function getProperties($properties);

}

