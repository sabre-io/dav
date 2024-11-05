<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml;

/**
 * XML service for WebDAV.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Service extends \Sabre\Xml\Service
{
    /**
     * This is a list of XML elements that we automatically map to PHP classes.
     *
     * For instance, this list may contain an entry `{DAV:}propfind` that would
     * be mapped to Sabre\DAV\Xml\Request\PropFind
     */
    public array $elementMap = [
        '{DAV:}multistatus' => Response\MultiStatus::class,
        '{DAV:}response' => Element\Response::class,

        // Requests
        '{DAV:}propfind' => Request\PropFind::class,
        '{DAV:}propertyupdate' => Request\PropPatch::class,
        '{DAV:}mkcol' => Request\MkCol::class,

        // Properties
        '{DAV:}resourcetype' => Property\ResourceType::class,
    ];

    /**
     * This is a default list of namespaces.
     *
     * If you are defining your own custom namespace, add it here to reduce
     * bandwidth and improve legibility of xml bodies.
     */
    public array $namespaceMap = [
        'DAV:' => 'd',
        'http://sabredav.org/ns' => 's',
    ];
}
