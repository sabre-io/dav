<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This plugin implements support for sharing calendars.
 *
 * This plugin implements the following draft specifications:
 *    draft-pot-webdav-resource-sharing
 *    draft-pot-caldav-sharing
 *    draft-calendarserver-caldav-sharing
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SharingPlugin extends ServerPlugin {

    /**
     * Various statuses used by the sharing process
     */
    const STATUS_ACCEPTED = 1;
    const STATUS_DECLINED = 2;
    const STATUS_DELETED = 3;
    const STATUS_NORESPONSE = 4;
    const STATUS_INVALID = 5;

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param Server $server
     * @return void
     */
    function initialize(Server $server) {

        $server->on('propFind',   [$this, 'propFindEarly']);
        $server->on('propFind',   [$this, 'propFindLate'], 150);
        $server->on('method:POST',[$this, 'httpPost']);

        array_push(
            $server->protectedProperties,
            '{DAV:}invite',
            '{DAV:}shared-url'
        );


    }

    /**
     * This method should return a list of server-features.
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     *
     * @return array
     */
    function getFeatures() {

        return [];

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

        return 'caldav-sharing';

    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'This plugin adds support for calendar sharing.',
            'link'        => 'http://sabre.io/dav/caldav-sharing/',
        ];

    }

    /**
     * This method adds several new properties to sharing-related resources.
     *
     * @param PropFind $propFind
     * @param INode $node
     * @return void
     */
    function propFindEarly(PropFind $propFind, INode $node) {

        if ($node instanceof IShareableCalendar) {

            $propFind->handle('{DAV:}invite', function() use ($node) {

                return new DAV\Xml\Property\Invite(
                    $node->getShares()
                );

            });

        }
        if ($node instanceof ISharedCalendar) {

            $propFind->handle('{DAV:}invite', function() use ($node) {

                // Fetching owner information
                $props = $this->server->getPropertiesForPath($node->getOwner(), [
                    '{http://sabredav.org/ns}email-address',
                    '{DAV:}displayname',
                ], 0);

                $ownerInfo = [
                    'href' => $node->getOwner(),
                ];

                if (isset($props[0][200])) {

                    // We're mapping the internal webdav properties to the
                    // elements caldav-sharing expects.
                    if (isset($props[0][200]['{http://sabredav.org/ns}email-address'])) {
                        $ownerInfo['href'] = 'mailto:' . $props[0][200]['{http://sabredav.org/ns}email-address'];
                    }
                    if (isset($props[0][200]['{DAV:}displayname'])) {
                        $ownerInfo['commonName'] = $props[0][200]['{DAV:}displayname'];
                    }

                }

                return new DAV\Xml\Property\Invite(
                    $node->getShares(),
                    $ownerInfo
                );

            });

            $propFind->handle(
                '{DAV:}shared-url',
                function() use ($node) {

                    return new Href(
                        $node->getSharedUrl()
                    );

                }
            );

        }

    }

    /**
     * This method is triggered when WebDAV properties are fetched for a
     * resource. This event is triggered late in the process, because we need
     * to alter some values after the regular plugins have already dealt with
     * them.
     *
     * @param PropFind $propFind
     * @param INode $node
     */
    function propFindLate(PropFind $propFind, INode $node) {

        if ($node instanceof IShareableCalendar) {

            // We are adding the {DAV:}shared-owner value to the
            // {DAV:}resourcetype property, but only if the resource was shared
            // with more than one person.
            if ($rt = $propFind->get('{DAV:}resourcetype')) {
                if (count($node->getShares()) > 0) {
                    $rt->add('{DAV:}shared-owner');
                    $rt->add('{' . Plugin::NS_CALENDARSERVER . '}shared-owner');
                }
            }

        }
        if ($node instanceof ISharedCalendar) {

            // We are adding the {DAV:}shared value to the
            // {DAV:}resourcetype property
            if ($rt = $propFind->get('{DAV:}resourcetype')) {
                $rt->add('{DAV:}shared');
                $rt->add('{' . Plugin::NS_CALENDARSERVER . '}shared');

            }

        }

    }

    /**
     * We intercept this to handle POST requests on calendars.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return null|bool
     */
    function httpPost(RequestInterface $request, ResponseInterface $response) {

        // Only handling xml
        $contentType = $request->getHeader('Content-Type');
        if (strpos($contentType,'application/davshare+xml')===false)
            return;

        $path = $request->getPath();

        // Making sure the node exists
        try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (DAV\Exception\NotFound $e) {
            return;
        }

        $requestBody = $request->getBodyAsString();

        // If this request handler could not deal with this POST request, it
        // will return 'null' and other plugins get a chance to handle the
        // request.
        //
        // However, we already requested the full body. This is a problem,
        // because a body can only be read once. This is why we preemptively
        // re-populated the request body with the existing data.
        $request->setBody($requestBody);

        $message = $this->server->xml->parse(
            $requestBody,
            $request->getUrl(),
            $documentType
        );

        switch ($documentType) {

            // Dealing with the 'share-resource' document, which modified invitees on a
            // calendar.
            case '{DAV:}share-resource' :

                // We can only deal with IShareableCalendar objects
                if (!$node instanceof IShareableCalendar) {
                    return;
                }

                $this->server->transactionType = 'post-calendar-share';

                // Getting ACL info
                $acl = $this->server->getPlugin('acl');

                // If there's no ACL support, we allow everything
                if ($acl) {
                    $acl->checkPrivileges($path, '{DAV:}share');
                }

                $node->updateShares($message->set, $message->remove);

                $response->setStatus(200);
                // Adding this because sending a response body may cause issues,
                // and I wanted some type of indicator the response was handled.
                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                // Breaking the event chain
                return false;

            // The invite-reply document is sent when the user replies to an
            // invitation of a calendar share.
            case '{' . Plugin::NS_CALENDARSERVER . '}invite-reply' :

                // This only works on the calendar-home-root node.
                if (!$node instanceof INotification) {
                    return;
                }
                $this->server->transactionType = 'post-invite-reply';


                $createIn = $message->createIn;
                $node = $this->server->tree->getNodeForPath($createIn);

                if (!$node instanceof CalendarHome) {
                    throw new Forbidden('You MUST specify a resource referring to a calendar-home when replying to a sharing invitation');
                } 

                // Checking privilileges on target node.
                $acl = $this->server->getPlugin('acl');

                // If there's no ACL support, we allow everything
                if ($acl) {
                    $acl->checkPrivileges($path, '{DAV:}bind');
                }

                $url = $node->shareReply(
                    $message->href,
                    $message->status,
                    $message->calendarUri,
                    $message->inReplyTo,
                    $message->summary
                );

                $response->setStatus(200);
                // Adding this because sending a response body may cause issues,
                // and I wanted some type of indicator the response was handled.
                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                if ($url) {
                    $writer = $this->server->xml->getWriter($this->server->getBaseUri());
                    $writer->openMemory();
                    $writer->startDocument();
                    $writer->startElement('{' . Plugin::NS_CALENDARSERVER . '}shared-as');
                    $writer->write(new Href($url));
                    $writer->endElement();
                    $response->setHeader('Content-Type', 'application/xml');
                    $response->setBody($writer->outputMemory());

                }

                // Breaking the event chain
                return false;

        }

    }

}
