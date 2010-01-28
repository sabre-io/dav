<?php

/**
 * XML utilities for WebDAV
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_XMLUtil {

    /**
     * Returns the 'clark notation' for an element.
     * 
     * For example, and element encoded as:
     * <b:myelem xmlns:b="http://www.example.org/" />
     * will be returned as:
     * {http://www.example.org}myelem
     *
     * This format is used throughout the SabreDAV sourcecode.
     * Elements encoded with the urn:DAV namespace will 
     * be returned as if they were in the DAV: namespace. This is to avoid
     * compatibility problems.
     *
     * @param DOMElement $dom 
     * @return string 
     */
    static function getClarkNotation(DOMElement $dom) {

        // Mapping back to the real namespace, in case it was dav
        if ($dom->namespaceURI=='urn:DAV') $ns = 'DAV:'; else $ns = $dom->namespaceURI;
        
        // Mapping to clark notation
        return '{' . $ns . '}' . $dom->localName;

    }

}
