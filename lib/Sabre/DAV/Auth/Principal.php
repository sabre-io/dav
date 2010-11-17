<?php

/**
 * Principal class
 *
 * This class represents a user in the directory tree.
 * Many WebDAV specs require a user to show up in the directory 
 * structure. The principal is defined in RFC 3744.
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Principal extends Sabre_DAV_Node implements Sabre_DAV_IProperties {

    /**
     * Struct with principal information.
     *
     * @var array 
     */
    protected $principalProperties;

    /**
     * Creates the principal object 
     *
     * @param array $principalProperties
     */
    public function __construct(array $principalProperties = array()) {

        if (!isset($principalProperties['uri'])) {
            throw new Sabre_DAV_Exception('The principal properties must at least contain the \'uri\' key');
        }
        $this->principalProperties = $principalProperties;

    }

    /**
     * Returns the name of the element 
     * 
     * @return void
     */
    public function getName() {

        list(, $name) = Sabre_DAV_URLUtil::splitPath($this->principalProperties['uri']);
        return $name;

    }

    /**
     * Returns the name of the user 
     * 
     * @return void
     */
    public function getDisplayName() {

        if (isset($this->principalProperties['{DAV:}displayname'])) {
            return $this->principalProperties['{DAV:}displayname'];
        } else {
            return $this->getName();
        }

    }

    /**
     * Returns a list of properties 
     * 
     * @param array $requestedProperties 
     * @return void
     */
    public function getProperties($requestedProperties) {

        if (!count($requestedProperties)) {
           
            // If all properties were requested
            // we will only returns properties from this list
            $requestedProperties = array(
                '{DAV:}resourcetype',
                '{DAV:}displayname',
            );

        }

        // We need to always return the resourcetype
        // This is a bug in the core server, but it is easier to do it this way for now
        $newProperties = array(
            '{DAV:}resourcetype' => new Sabre_DAV_Property_ResourceType('{DAV:}principal')
        );
        foreach($requestedProperties as $propName) switch($propName) {
            
            case '{DAV:}alternate-URI-set' :
                if (isset($this->principalProperties['{http://sabredav.org/ns}email-address'])) {
                    $href = 'mailto:' . $this->principalProperties['{http://sabredav.org/ns}email-address'];
                    $newProperties[$propName] = new Sabre_DAV_Property_Href($href);
                } else {
                    $newProperties[$propName] = null;
                }
                break;
            case '{DAV:}group-member-set' :
            case '{DAV:}group-membership' :
                $newProperties[$propName] = null;
                break;

            case '{DAV:}principal-URL' :
                $newProperties[$propName] = new Sabre_DAV_Property_Href($this->principalProperties['uri'] . '/');
                break;

            case '{DAV:}displayname' :
                $newProperties[$propName] = $this->getDisplayName();
                break;

            default :
                if (isset($this->principalProperties[$propName])) {
                    $newProperties[$propName] = $this->principalProperties[$propName];
                }
                break;

        }

        return $newProperties;
        

    }

    /**
     * Updates this principals properties.
     *
     * Currently this is not supported
     * 
     * @param array $properties
     * @see Sabre_DAV_IProperties::updateProperties
     * @return bool|array 
     */
    public function updateProperties($properties) {

        return false;

    }

}
