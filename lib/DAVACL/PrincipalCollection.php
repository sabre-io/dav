<?php

namespace Sabre\DAVACL;

use
    Sabre\DAV\Exception\InvalidResourceType,
    Sabre\DAV\IExtendedCollection,
    Sabre\DAV\MkCol;

/**
 * Principals Collection
 *
 * This collection represents a list of users.
 * The users are instances of Sabre\DAVACL\Principal
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PrincipalCollection extends AbstractPrincipalCollection implements IExtendedCollection {

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     * @param array $principal
     * @return \Sabre\DAV\INode
     */
    function getChildForPrincipal(array $principal) {

        return new Principal($this->principalBackend, $principal);

    }

    /**
     * Creates a new collection.
     *
     * This method will receive a MkCol object with all the information about
     * the new collection that's being created.
     *
     * The MkCol object contains information about the resourceType of the new
     * collection. If you don't support the specified resourceType, you should
     * throw Exception\InvalidResourceType.
     *
     * The object also contains a list of WebDAV properties for the new
     * collection.
     *
     * You should call the handle() method on this object to specify exactly
     * which properties you are storing. This allows the system to figure out
     * exactly which properties you didn't store, which in turn allows other
     * plugins (such as the propertystorage plugin) to handle storing the
     * property for you.
     *
     * @param string $name
     * @param MkCol $mkCol
     * @throws Exception\InvalidResourceType
     * @return void
     */
    function createExtendedCollection($name, MkCol $mkCol) {

        if (!$mkCol->hasResourceType('{DAV:}principal')) {
            throw new InvalidResourceType('Only resources of type {DAV:}principal may be created here');
        }

        $this->principalBackend->createPrincipal(
            $this->principalPrefix . '/' . $name,
            $mkCol
        );

    }

}
