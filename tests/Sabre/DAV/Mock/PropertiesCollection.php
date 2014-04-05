<?php

namespace Sabre\DAV\Mock;

use
    Sabre\DAV\IProperties,
    Sabre\DAV\PropPatch;


/**
 * A node specifically for testing property-related operations
 * 
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class PropertiesCollection extends Collection implements IProperties {

    public $failMode = false;

    /**
     * Updates properties on this node.
     *
     * This method received a PropPatch object, which contains all the
     * information about the update.
     *
     * To update specific properties, call the 'handle' method on this object.
     * Read the PropPatch documentation for more information.
     *
     * @param array $mutations
     * @return bool|array
     */
    public function propPatch(PropPatch $proppatch) {

        $proppatch->handleRemaining(function($updateProperties) {

            switch($this->failMode) {
                case 'updatepropsfalse' : return false;
                case 'updatepropsarray' :
                    $r = [];
                    foreach($updateProperties as $k=>$v) $r[$k] = 402;
                    return $r;
                case 'updatepropsobj' :
                    return new \STDClass();
            }

        });

    }

    function getProperties($requestedPropeties) {

        return array();

    }


}
