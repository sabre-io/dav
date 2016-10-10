<?php

namespace Sabre;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Sabre\HTTP\Sapi;

/**
 * This class may be used as a basis for other webdav-related unittests.
 *
 * This class is supposed to provide a reasonably big framework to quickly get
 * a testing environment running.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class DAVServerTest extends \PHPUnit_Framework_TestCase {

    protected $setupCalDAV = false;
    protected $setupCardDAV = false;
    protected $setupACL = false;
    protected $setupCalDAVSharing = false;
    protected $setupCalDAVScheduling = false;
    protected $setupCalDAVSubscriptions = false;
    protected $setupCalDAVICSExport = false;
    protected $setupLocks = false;
    protected $setupFiles = false;
    protected $setupSharing = false;
    protected $setupPropertyStorage = false;

    /**
     * An array with calendars. Every calendar should have
     *   - principaluri
     *   - uri
     */
    protected $caldavCalendars = [];
    protected $caldavCalendarObjects = [];

    protected $carddavAddressBooks = [];
    protected $carddavCards = [];

    /**
     * @var \Sabre\DAV\Server
     */
    protected $server;
    protected $tree = [];

    /**
     * @var CalDAV\Backend\MockSharing|CalDAV\Backend\MockSubscriptionSupport|CalDAV\Backend\Mock
     */
    protected $caldavBackend;

    /**
     * @var CardDAV\Backend\Mock
     */
    protected $carddavBackend;
    protected $principalBackend;
    protected $locksBackend;
    protected $propertyStorageBackend;

    /**
     * @var CalDAV\Plugin
     */
    protected $caldavPlugin;

    /**
     * @var CardDAV\Plugin
     */
    protected $carddavPlugin;

    /**
     * @var DAVACL\Plugin
     */
    protected $aclPlugin;

    /**
     * @var CalDAV\SharingPlugin
     */
    protected $caldavSharingPlugin;

    /**
     * CalDAV scheduling plugin
     *
     * @var CalDAV\Schedule\Plugin
     */
    protected $caldavSchedulePlugin;

    /**
     * @var DAV\Auth\Plugin
     */
    protected $authPlugin;

    /**
     * @var DAV\Locks\Plugin
     */
    protected $locksPlugin;

    /**
     * Sharing plugin.
     *
     * @var DAV\Sharing\Plugin
     */
    protected $sharingPlugin;

    /**
     * @var DAV\PropertyStorage\Plugin
     */
    protected $propertyStoragePlugin;

    /**
     * @var CalDAV\ICSExportPlugin
     */
    private $caldavICSExportPlugin;

    /**
     * If this string is set, we will automatically log in the user with this
     * name.
     */
    protected $autoLogin = null;

    function setUp() {

        $this->initializeEverything();

    }

    function initializeEverything() {

        $this->setUpBackends();
        $this->setUpTree();

        $this->server = new DAV\Server($this->tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;

        if ($this->setupCalDAV) {
            $this->caldavPlugin = new CalDAV\Plugin();
            $this->server->addPlugin($this->caldavPlugin);
        }
        if ($this->setupCalDAVSharing || $this->setupSharing) {
            $this->sharingPlugin = new DAV\Sharing\Plugin();
            $this->server->addPlugin($this->sharingPlugin);
        }
        if ($this->setupCalDAVSharing) {
            $this->caldavSharingPlugin = new CalDAV\SharingPlugin();
            $this->server->addPlugin($this->caldavSharingPlugin);
        }
        if ($this->setupCalDAVScheduling) {
            $this->caldavSchedulePlugin = new CalDAV\Schedule\Plugin();
            $this->server->addPlugin($this->caldavSchedulePlugin);
        }
        if ($this->setupCalDAVSubscriptions) {
            $this->server->addPlugin(new CalDAV\Subscriptions\Plugin());
        }
        if ($this->setupCalDAVICSExport) {
            $this->caldavICSExportPlugin = new CalDAV\ICSExportPlugin();
            $this->server->addPlugin($this->caldavICSExportPlugin);
        }
        if ($this->setupCardDAV) {
            $this->carddavPlugin = new CardDAV\Plugin();
            $this->server->addPlugin($this->carddavPlugin);
        }
        if ($this->setupLocks) {
            $this->locksPlugin = new DAV\Locks\Plugin(
                $this->locksBackend
            );
            $this->server->addPlugin($this->locksPlugin);
        }
        if ($this->setupPropertyStorage) {
            $this->propertyStoragePlugin = new DAV\PropertyStorage\Plugin(
                $this->propertyStorageBackend
            );
            $this->server->addPlugin($this->propertyStoragePlugin);
        }
        if ($this->autoLogin) {
            $this->autoLogin($this->autoLogin);
        }
        if ($this->setupACL) {
            $this->aclPlugin = new DAVACL\Plugin();
            if (!$this->autoLogin) {
                $this->aclPlugin->allowUnauthenticatedAccess = false;
            }
            $this->aclPlugin->adminPrincipals = ['principals/admin'];
            $this->server->addPlugin($this->aclPlugin);
        }

    }

    /**
     * Makes a request, and returns a response object.
     *
     * You can either pass an instance of Sabre\HTTP\Request, or an array,
     * which will then be used as the _SERVER array.
     *
     * If $expectedStatus is set, we'll compare it with the HTTP status of
     * the returned response. If it doesn't match, we'll immediately fail
     * the test.
     *
     * @param array|\Sabre\HTTP\Request $request
     * @param int $expectedStatus Don't call this method directly with this argument; use assertHttpStatus() instead to
     *                            make assertion more obvious.
     * @return \Sabre\HTTP\Response
     *
     * @see assertHttpStatus()
     */
    function request($request, $expectedStatus = null) {

        if (is_array($request)) {
            $request = HTTP\Request::createFromServerArray($request);
        }
        $response = new HTTP\Response();

        $this->server->httpRequest = $request;
        $this->server->httpResponse = $response;
        $this->server->exec();

        if ($expectedStatus) {
            $responseBody = $expectedStatus !== $response->getStatus() ? $response->getBodyAsString() : '';
            $this->assertEquals($expectedStatus, $response->getStatus(), 'Incorrect HTTP status received for request. Response body: ' . $responseBody);
        }
        return $this->server->httpResponse;

    }

    /**
     * This function takes a username and sets the server in a state where
     * this user is logged in, and no longer requires an authentication check.
     *
     * @param string $userName
     */
    function autoLogin($userName) {
        $authBackend = new DAV\Auth\Backend\Mock();
        $authBackend->setPrincipal('principals/' . $userName);
        $this->authPlugin = new DAV\Auth\Plugin($authBackend);

        // If the auth plugin already exists, we're removing its hooks:
        if ($oldAuth = $this->server->getPlugin('auth')) {
            $this->server->removeListener('beforeMethod', [$oldAuth, 'beforeMethod']);
        }
        $this->server->addPlugin($this->authPlugin);

        // This will trigger the actual login procedure
        $this->authPlugin->beforeMethod(new Request(), new Response());
    }

    /**
     * Override this to provide your own Tree for your test-case.
     */
    function setUpTree() {

        if ($this->setupCalDAV) {
            $this->tree[] = new CalDAV\CalendarRoot(
                $this->principalBackend,
                $this->caldavBackend
            );
        }
        if ($this->setupCardDAV) {
            $this->tree[] = new CardDAV\AddressBookRoot(
                $this->principalBackend,
                $this->carddavBackend
            );
        }

        if ($this->setupCalDAV) {
            $this->tree[] = new CalDAV\Principal\Collection(
                $this->principalBackend
            );
        } elseif ($this->setupCardDAV || $this->setupACL) {
            $this->tree[] = new DAVACL\PrincipalCollection(
                $this->principalBackend
            );
        }
        if ($this->setupFiles) {

            $this->tree[] = new DAV\Mock\Collection('files');

        }

    }

    function setUpBackends() {

        if ($this->setupCalDAVSharing && $this->caldavBackend === null) {
            $this->caldavBackend = new CalDAV\Backend\MockSharing($this->caldavCalendars, $this->caldavCalendarObjects);
        }
        if ($this->setupCalDAVSubscriptions && $this->caldavBackend === null) {
            $this->caldavBackend = new CalDAV\Backend\MockSubscriptionSupport($this->caldavCalendars, $this->caldavCalendarObjects);
        }
        if ($this->setupCalDAV && $this->caldavBackend === null) {
            if ($this->setupCalDAVScheduling) {
                $this->caldavBackend = new CalDAV\Backend\MockScheduling($this->caldavCalendars, $this->caldavCalendarObjects);
            } else {
                $this->caldavBackend = new CalDAV\Backend\Mock($this->caldavCalendars, $this->caldavCalendarObjects);
            }
        }
        if ($this->setupCardDAV && $this->carddavBackend === null) {
            $this->carddavBackend = new CardDAV\Backend\Mock($this->carddavAddressBooks, $this->carddavCards);
        }
        if ($this->setupCardDAV || $this->setupCalDAV || $this->setupACL) {
            $this->principalBackend = new DAVACL\PrincipalBackend\Mock();
        }
        if ($this->setupLocks) {
            $this->locksBackend = new DAV\Locks\Backend\Mock();
        }
        if ($this->setupPropertyStorage)  {
            $this->propertyStorageBackend = new DAV\PropertyStorage\Backend\Mock();
        }

    }


    /**
     * @param int $expectedStatus
     * @param Request $req
     * @return Response
     */
    function assertHttpStatus($expectedStatus, HTTP\Request $req) {

        return $this->request($req, (int)$expectedStatus);

    }

}
