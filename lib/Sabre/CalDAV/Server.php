<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAVACL;

/**
 * CalDAV server
 *
 * Deprecated! Warning: This class is now officially deprecated
 *
 * This script is a convenience script. It quickly sets up a WebDAV server
 * with caldav and ACL support, and it creates the root 'principals' and
 * 'calendars' collections.
 *
 * Note that if you plan to do anything moderately complex, you are advised to
 * not subclass this server, but use \Sabre\DAV\Server directly instead. This
 * class is nothing more than an 'easy setup'.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @deprecated Don't use this class anymore, it will be removed in version 1.7.
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Server extends DAV\Server {

    /**
     * The authentication realm
     *
     * Note that if this changes, the hashes in the auth backend must also
     * be recalculated.
     *
     * @var string
     */
    public $authRealm = 'SabreDAV';

    /**
     * Sets up the object. A PDO object must be passed to setup all the backends.
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo) {

        /* Backends */
        $authBackend = new DAV\Auth\Backend\PDO($pdo);
        $calendarBackend = new Backend\PDO($pdo);
        $principalBackend = new DAVACL\PrincipalBackend\PDO($pdo);

        /* Directory structure */
        $tree = array(
            new Principal\Collection($principalBackend),
            new CalendarRootNode($principalBackend, $calendarBackend),
        );

        /* Initializing server */
        parent::__construct($tree);

        /* Server Plugins */
        $authPlugin = new DAV\Auth\Plugin($authBackend,$this->authRealm);
        $this->addPlugin($authPlugin);

        $aclPlugin = new DAVACL\Plugin();
        $this->addPlugin($aclPlugin);

        $caldavPlugin = new Plugin();
        $this->addPlugin($caldavPlugin);

    }

}
