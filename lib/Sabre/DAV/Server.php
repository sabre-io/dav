<?php

namespace Sabre\DAV;

use
    Sabre\Event\EventEmitter,
    Sabre\HTTP,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface,
    Sabre\HTTP\URLUtil;

/**
 * Main DAV server class
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Server extends EventEmitter {

    /**
     * Infinity is used for some request supporting the HTTP Depth header and indicates that the operation should traverse the entire tree
     */
    const DEPTH_INFINITY = -1;

    /**
     * Nodes that are files, should have this as the type property
     */
    const NODE_FILE = 1;

    /**
     * Nodes that are directories, should use this value as the type property
     */
    const NODE_DIRECTORY = 2;

    /**
     * XML namespace for all SabreDAV related elements
     */
    const NS_SABREDAV = 'http://sabredav.org/ns';

    /**
     * The tree object
     *
     * @var Sabre\DAV\Tree
     */
    public $tree;

    /**
     * The base uri
     *
     * @var string
     */
    protected $baseUri = null;

    /**
     * httpResponse
     *
     * @var Sabre\HTTP\Response
     */
    public $httpResponse;

    /**
     * httpRequest
     *
     * @var Sabre\HTTP\Request
     */
    public $httpRequest;

    /**
     * The list of plugins
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * This array contains a list of callbacks we should call when certain events are triggered
     *
     * @var array
     */
    protected $eventSubscriptions = [];

    /**
     * This property will be filled with a unique string that describes the
     * transaction. This is useful for performance measuring and logging
     * purposes.
     *
     * By default it will just fill it with a lowercased HTTP method name, but
     * plugins override this. For example, the WebDAV-Sync sync-collection
     * report will set this to 'report-sync-collection'.
     *
     * @var string
     */
    public $transactionType;

    /**
     * This is a default list of namespaces.
     *
     * If you are defining your own custom namespace, add it here to reduce
     * bandwidth and improve legibility of xml bodies.
     *
     * @var array
     */
    public $xmlNamespaces = [
        'DAV:' => 'd',
        'http://sabredav.org/ns' => 's',
    ];

    /**
     * The propertymap can be used to map properties from
     * requests to property classes.
     *
     * @var array
     */
    public $propertyMap = [
        '{DAV:}resourcetype' => 'Sabre\\DAV\\Property\\ResourceType',
    ];

    public $protectedProperties = [
        // RFC4918
        '{DAV:}getcontentlength',
        '{DAV:}getetag',
        '{DAV:}getlastmodified',
        '{DAV:}lockdiscovery',
        '{DAV:}supportedlock',

        // RFC4331
        '{DAV:}quota-available-bytes',
        '{DAV:}quota-used-bytes',

        // RFC3744
        '{DAV:}supported-privilege-set',
        '{DAV:}current-user-privilege-set',
        '{DAV:}acl',
        '{DAV:}acl-restrictions',
        '{DAV:}inherited-acl-set',

    ];

    /**
     * This is a flag that allow or not showing file, line and code
     * of the exception in the returned XML
     *
     * @var bool
     */
    public $debugExceptions = false;

    /**
     * This property allows you to automatically add the 'resourcetype' value
     * based on a node's classname or interface.
     *
     * The preset ensures that {DAV:}collection is automaticlly added for nodes
     * implementing Sabre\DAV\ICollection.
     *
     * @var array
     */
    public $resourceTypeMapping = [
        'Sabre\\DAV\\ICollection' => '{DAV:}collection',
    ];

    /**
     * If this setting is turned off, SabreDAV's version number will be hidden
     * from various places.
     *
     * Some people feel this is a good security measure.
     *
     * @var bool
     */
    static public $exposeVersion = true;

    /**
     * Sets up the server
     *
     * If a Sabre\DAV\Tree object is passed as an argument, it will
     * use it as the directory tree. If a Sabre\DAV\INode is passed, it
     * will create a Sabre\DAV\ObjectTree and use the node as the root.
     *
     * If nothing is passed, a Sabre\DAV\SimpleCollection is created in
     * a Sabre\DAV\ObjectTree.
     *
     * If an array is passed, we automatically create a root node, and use
     * the nodes in the array as top-level children.
     *
     * @param Tree|INode|array|null $treeOrNode The tree object
     */
    public function __construct($treeOrNode = null) {

        if ($treeOrNode instanceof Tree) {
            $this->tree = $treeOrNode;
        } elseif ($treeOrNode instanceof INode) {
            $this->tree = new ObjectTree($treeOrNode);
        } elseif (is_array($treeOrNode)) {

            // If it's an array, a list of nodes was passed, and we need to
            // create the root node.
            foreach($treeOrNode as $node) {
                if (!($node instanceof INode)) {
                    throw new Exception('Invalid argument passed to constructor. If you\'re passing an array, all the values must implement Sabre\\DAV\\INode');
                }
            }

            $root = new SimpleCollection('root', $treeOrNode);
            $this->tree = new ObjectTree($root);

        } elseif (is_null($treeOrNode)) {
            $root = new SimpleCollection('root');
            $this->tree = new ObjectTree($root);
        } else {
            throw new Exception('Invalid argument passed to constructor. Argument must either be an instance of Sabre\\DAV\\Tree, Sabre\\DAV\\INode, an array or null');
        }
        $this->httpResponse = new HTTP\Response();
        $this->httpRequest = HTTP\Request::createFromPHPRequest();
        $this->addPlugin(new CorePlugin());

    }

    /**
     * Starts the DAV Server
     *
     * @return void
     */
    public function exec() {

        try {

            // If nginx (pre-1.2) is used as a proxy server, and SabreDAV as an
            // origin, we must make sure we send back HTTP/1.0 if this was
            // requested.
            // This is mainly because nginx doesn't support Chunked Transfer
            // Encoding, and this forces the webserver SabreDAV is running on,
            // to buffer entire responses to calculate Content-Length.
            $this->httpResponse->setHTTPVersion($this->httpRequest->getHTTPVersion());

            // Setting the base url
            $this->httpRequest->setBaseUrl($this->getBaseUri());
            $this->invokeMethod($this->httpRequest, $this->httpResponse);

        } catch (Exception $e) {

            try {
                $this->emit('exception', [$e]);
            } catch (Exception $ignore) {
            }
            $DOM = new \DOMDocument('1.0','utf-8');
            $DOM->formatOutput = true;

            $error = $DOM->createElementNS('DAV:','d:error');
            $error->setAttribute('xmlns:s',self::NS_SABREDAV);
            $DOM->appendChild($error);

            $h = function($v) {

                return htmlspecialchars($v, ENT_NOQUOTES, 'UTF-8');

            };

            if (self::$exposeVersion) {
                $error->appendChild($DOM->createElement('s:sabredav-version',$h(Version::VERSION)));
            }

            $error->appendChild($DOM->createElement('s:exception',$h(get_class($e))));
            $error->appendChild($DOM->createElement('s:message',$h($e->getMessage())));
            if ($this->debugExceptions) {
                $error->appendChild($DOM->createElement('s:file',$h($e->getFile())));
                $error->appendChild($DOM->createElement('s:line',$h($e->getLine())));
                $error->appendChild($DOM->createElement('s:code',$h($e->getCode())));
                $error->appendChild($DOM->createElement('s:stacktrace',$h($e->getTraceAsString())));
            }

            if ($this->debugExceptions) {
                $previous = $e;
                while ($previous = $previous->getPrevious()) {
                    $xPrevious = $DOM->createElement('s:previous-exception');
                    $xPrevious->appendChild($DOM->createElement('s:exception',$h(get_class($previous))));
                    $xPrevious->appendChild($DOM->createElement('s:message',$h($previous->getMessage())));
                    $xPrevious->appendChild($DOM->createElement('s:file',$h($previous->getFile())));
                    $xPrevious->appendChild($DOM->createElement('s:line',$h($previous->getLine())));
                    $xPrevious->appendChild($DOM->createElement('s:code',$h($previous->getCode())));
                    $xPrevious->appendChild($DOM->createElement('s:stacktrace',$h($previous->getTraceAsString())));
                    $error->appendChild($xPrevious);
                }
            }


            if($e instanceof Exception) {

                $httpCode = $e->getHTTPCode();
                $e->serialize($this,$error);
                $headers = $e->getHTTPHeaders($this);

            } else {

                $httpCode = 500;
                $headers = [];

            }
            $headers['Content-Type'] = 'application/xml; charset=utf-8';

            $this->httpResponse->setStatus($httpCode);
            $this->httpResponse->setHeaders($headers);
            $this->httpResponse->setBody($DOM->saveXML());
            $this->httpResponse->send();

        }

    }

    /**
     * Sets the base server uri
     *
     * @param string $uri
     * @return void
     */
    public function setBaseUri($uri) {

        // If the baseUri does not end with a slash, we must add it
        if ($uri[strlen($uri)-1]!=='/')
            $uri.='/';

        $this->baseUri = $uri;

    }

    /**
     * Returns the base responding uri
     *
     * @return string
     */
    public function getBaseUri() {

        if (is_null($this->baseUri)) $this->baseUri = $this->guessBaseUri();
        return $this->baseUri;

    }

    /**
     * This method attempts to detect the base uri.
     * Only the PATH_INFO variable is considered.
     *
     * If this variable is not set, the root (/) is assumed.
     *
     * @return string
     */
    public function guessBaseUri() {

        $pathInfo = $this->httpRequest->getRawServerValue('PATH_INFO');
        $uri = $this->httpRequest->getRawServerValue('REQUEST_URI');

        // If PATH_INFO is found, we can assume it's accurate.
        if (!empty($pathInfo)) {

            // We need to make sure we ignore the QUERY_STRING part
            if ($pos = strpos($uri,'?'))
                $uri = substr($uri,0,$pos);

            // PATH_INFO is only set for urls, such as: /example.php/path
            // in that case PATH_INFO contains '/path'.
            // Note that REQUEST_URI is percent encoded, while PATH_INFO is
            // not, Therefore they are only comparable if we first decode
            // REQUEST_INFO as well.
            $decodedUri = URLUtil::decodePath($uri);

            // A simple sanity check:
            if(substr($decodedUri,strlen($decodedUri)-strlen($pathInfo))===$pathInfo) {
                $baseUri = substr($decodedUri,0,strlen($decodedUri)-strlen($pathInfo));
                return rtrim($baseUri,'/') . '/';
            }

            throw new Exception('The REQUEST_URI ('. $uri . ') did not end with the contents of PATH_INFO (' . $pathInfo . '). This server might be misconfigured.');

        }

        // The last fallback is that we're just going to assume the server root.
        return '/';

    }

    /**
     * Adds a plugin to the server
     *
     * For more information, console the documentation of Sabre\DAV\ServerPlugin
     *
     * @param ServerPlugin $plugin
     * @return void
     */
    public function addPlugin(ServerPlugin $plugin) {

        $this->plugins[$plugin->getPluginName()] = $plugin;
        $plugin->initialize($this);

    }

    /**
     * Returns an initialized plugin by it's name.
     *
     * This function returns null if the plugin was not found.
     *
     * @param string $name
     * @return ServerPlugin
     */
    public function getPlugin($name) {

        if (isset($this->plugins[$name]))
            return $this->plugins[$name];

        // This is a fallback and deprecated.
        foreach($this->plugins as $plugin) {
            if (get_class($plugin)===$name) return $plugin;
        }

        return null;

    }

    /**
     * Returns all plugins
     *
     * @return array
     */
    public function getPlugins() {

        return $this->plugins;

    }

    /**
     * Handles a http request, and execute a method based on its name
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function invokeMethod(RequestInterface $request, ResponseInterface $response) {

        $method = $request->getMethod();

        if (!$this->emit('beforeMethod:' . $method,[$request, $response])) return;
        if (!$this->emit('beforeMethod',[$request, $response])) return;


        // Make sure this is a HTTP method we support
        $internalMethods = [
            'OPTIONS',
            'HEAD',
            'DELETE',
            'PROPFIND',
            'MKCOL',
            'PUT',
            'PROPPATCH',
            'COPY',
            'MOVE',
            'REPORT'
        ];

        $this->transactionType = strtolower($method);

        if (in_array($method,$internalMethods)) {

            call_user_func([$this, 'http' . $method], $request->getPath());

        } else {

            if ($this->emit('method:' . $method, [$request, $response])) {
                if ($this->emit('method',[$request, $response])) {
                    // Unsupported method
                    throw new Exception\NotImplemented('There was no handler found for this "' . $method . '" method');
                }
            }

        }
        if (!$this->emit('afterMethod:' . $method,[$request, $response])) return;
        if (!$this->emit('afterMethod', [$request, $response])) return;

        $response->send();

    }

    // {{{ HTTP Method implementations

    /**
     * HTTP OPTIONS
     *
     * @param string $uri
     * @return void
     */
    protected function httpOptions($uri) {

        $methods = $this->getAllowedMethods($uri);

        $this->httpResponse->setHeader('Allow',strtoupper(implode(', ',$methods)));
        $features = ['1','3', 'extended-mkcol'];

        foreach($this->plugins as $plugin) $features = array_merge($features,$plugin->getFeatures());

        $this->httpResponse->setHeader('DAV',implode(', ',$features));
        $this->httpResponse->setHeader('MS-Author-Via','DAV');
        $this->httpResponse->setHeader('Accept-Ranges','bytes');
        if (self::$exposeVersion) {
            $this->httpResponse->setHeader('X-Sabre-Version',Version::VERSION);
        }
        $this->httpResponse->setHeader('Content-Length',0);
        $this->httpResponse->setStatus(200);

    }

    /**
     * HTTP HEAD
     *
     * This method is normally used to take a peak at a url, and only get the HTTP response headers, without the body
     * This is used by clients to determine if a remote file was changed, so they can use a local cached version, instead of downloading it again
     *
     * @param string $uri
     * @return void
     */
    protected function httpHead($uri) {

        $node = $this->tree->getNodeForPath($uri);
        /* This information is only collection for File objects.
         * Ideally we want to throw 405 Method Not Allowed for every
         * non-file, but MS Office does not like this
         */
        if ($node instanceof IFile) {
            $headers = $this->getHTTPHeaders($this->getRequestUri());
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/octet-stream';
            }
            $this->httpResponse->setHeaders($headers);
        }
        $this->httpResponse->setStatus(200);

    }

    /**
     * HTTP Delete
     *
     * The HTTP delete method, deletes a given uri
     *
     * @param string $uri
     * @return void
     */
    protected function httpDelete($uri) {

        $this->checkPreconditions();
        if (!$this->emit('beforeUnbind',[$uri])) return;
        $this->tree->delete($uri);
        $this->emit('afterUnbind',[$uri]);

        $this->httpResponse->setStatus(204);
        $this->httpResponse->setHeader('Content-Length','0');

    }


    /**
     * WebDAV PROPFIND
     *
     * This WebDAV method requests information about an uri resource, or a list of resources
     * If a client wants to receive the properties for a single resource it will add an HTTP Depth: header with a 0 value
     * If the value is 1, it means that it also expects a list of sub-resources (e.g.: files in a directory)
     *
     * The request body contains an XML data structure that has a list of properties the client understands
     * The response body is also an xml document, containing information about every uri resource and the requested properties
     *
     * It has to return a HTTP 207 Multi-status status code
     *
     * @param string $uri
     * @return void
     */
    protected function httpPropfind($uri) {

        $requestedProperties = $this->parsePropFindRequest($this->httpRequest->getBody(true));

        $depth = $this->getHTTPDepth(1);
        // The only two options for the depth of a propfind is 0 or 1
        if ($depth!=0) $depth = 1;

        $newProperties = $this->getPropertiesForPath($uri,$requestedProperties,$depth);

        // This is a multi-status response
        $this->httpResponse->setStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->httpResponse->setHeader('Vary','Brief,Prefer');

        // Normally this header is only needed for OPTIONS responses, however..
        // iCal seems to also depend on these being set for PROPFIND. Since
        // this is not harmful, we'll add it.
        $features = ['1', '3', 'extended-mkcol'];
        foreach($this->plugins as $plugin) $features = array_merge($features,$plugin->getFeatures());
        $this->httpResponse->setHeader('DAV',implode(', ',$features));

        $prefer = $this->getHTTPPrefer();
        $minimal = $prefer['return-minimal'];

        $data = $this->generateMultiStatus($newProperties, $minimal);
        $this->httpResponse->setBody($data);
        $this->httpResponse->send();

    }

    /**
     * WebDAV PROPPATCH
     *
     * This method is called to update properties on a Node. The request is an XML body with all the mutations.
     * In this XML body it is specified which properties should be set/updated and/or deleted
     *
     * @param string $uri
     * @return void
     */
    protected function httpPropPatch($uri) {

        $this->checkPreconditions();

        $newProperties = $this->parsePropPatchRequest($this->httpRequest->getBody(true));

        $result = $this->updateProperties($uri, $newProperties);

        $prefer = $this->getHTTPPrefer();
        $this->httpResponse->setHeader('Vary','Brief,Prefer');

        if ($prefer['return-minimal']) {

            // If return-minimal is specified, we only have to check if the
            // request was succesful, and don't need to return the
            // multi-status.
            $ok = true;
            foreach($result as $code=>$prop) {
                if ((int)$code > 299) {
                    $ok = false;
                }
            }

            if ($ok) {

                $this->httpResponse->setStatus(204);
                return;

            }

        }

        $this->httpResponse->setStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');

        $this->httpResponse->setBody(
            $this->generateMultiStatus([$result])
        );
        $this->httpResponse->send();

    }

    /**
     * HTTP PUT method
     *
     * This HTTP method updates a file, or creates a new one.
     *
     * If a new resource was created, a 201 Created status code should be returned. If an existing resource is updated, it's a 204 No Content
     *
     * @param string $uri
     * @return bool
     */
    protected function httpPut($uri) {

        $body = $this->httpRequest->getBody();

        // Intercepting Content-Range
        if ($this->httpRequest->getHeader('Content-Range')) {
            /**
            Content-Range is dangerous for PUT requests:  PUT per definition
            stores a full resource.  draft-ietf-httpbis-p2-semantics-15 says
            in section 7.6:
              An origin server SHOULD reject any PUT request that contains a
              Content-Range header field, since it might be misinterpreted as
              partial content (or might be partial content that is being mistakenly
              PUT as a full representation).  Partial content updates are possible
              by targeting a separately identified resource with state that
              overlaps a portion of the larger resource, or by using a different
              method that has been specifically defined for partial updates (for
              example, the PATCH method defined in [RFC5789]).
            This clarifies RFC2616 section 9.6:
              The recipient of the entity MUST NOT ignore any Content-*
              (e.g. Content-Range) headers that it does not understand or implement
              and MUST return a 501 (Not Implemented) response in such cases.
            OTOH is a PUT request with a Content-Range currently the only way to
            continue an aborted upload request and is supported by curl, mod_dav,
            Tomcat and others.  Since some clients do use this feature which results
            in unexpected behaviour (cf PEAR::HTTP_WebDAV_Client 1.0.1), we reject
            all PUT requests with a Content-Range for now.
            */

            throw new Exception\NotImplemented('PUT with Content-Range is not allowed.');
        }

        // Intercepting the Finder problem
        if (($expected = $this->httpRequest->getHeader('X-Expected-Entity-Length')) && $expected > 0) {

            /**
            Many webservers will not cooperate well with Finder PUT requests,
            because it uses 'Chunked' transfer encoding for the request body.

            The symptom of this problem is that Finder sends files to the
            server, but they arrive as 0-length files in PHP.

            If we don't do anything, the user might think they are uploading
            files successfully, but they end up empty on the server. Instead,
            we throw back an error if we detect this.

            The reason Finder uses Chunked, is because it thinks the files
            might change as it's being uploaded, and therefore the
            Content-Length can vary.

            Instead it sends the X-Expected-Entity-Length header with the size
            of the file at the very start of the request. If this header is set,
            but we don't get a request body we will fail the request to
            protect the end-user.
            */

            // Only reading first byte
            $firstByte = fread($body,1);
            if (strlen($firstByte)!==1) {
                throw new Exception\Forbidden('This server is not compatible with OS/X finder. Consider using a different WebDAV client or webserver.');
            }

            // The body needs to stay intact, so we copy everything to a
            // temporary stream.

            $newBody = fopen('php://temp','r+');
            fwrite($newBody,$firstByte);
            stream_copy_to_stream($body, $newBody);
            rewind($newBody);

            $body = $newBody;

        }

        if ($this->tree->nodeExists($uri)) {

            $node = $this->tree->getNodeForPath($uri);

            // Checking If-None-Match and related headers.
            if (!$this->checkPreconditions()) return;

            // If the node is a collection, we'll deny it
            if (!($node instanceof IFile)) throw new Exception\Conflict('PUT is not allowed on non-files.');

            // It is possible for an event handler to modify the content of the
            // body, before it gets written. If this is the case, $modified
            // should be set to true.
            //
            // If $modified is true, we must not send back an etag.
            $modified = false;
            if (!$this->emit('beforeWriteContent',[$uri, $node, &$body, &$modified])) return false;

            $etag = $node->put($body);

            $this->emit('afterWriteContent',[$uri, $node]);

            $this->httpResponse->setHeader('Content-Length','0');
            if ($etag && !$modified) $this->httpResponse->setHeader('ETag',$etag);
            $this->httpResponse->setStatus(204);

        } else {

            $etag = null;
            // If we got here, the resource didn't exist yet.
            if (!$this->createFile($this->getRequestUri(),$body,$etag)) {
                // For one reason or another the file was not created.
                return;
            }

            $this->httpResponse->setHeader('Content-Length','0');
            if ($etag) $this->httpResponse->setHeader('ETag', $etag);
            $this->httpResponse->setStatus(201);

        }

    }


    /**
     * WebDAV MKCOL
     *
     * The MKCOL method is used to create a new collection (directory) on the server
     *
     * @param string $uri
     * @return void
     */
    protected function httpMkcol($uri) {

        $requestBody = $this->httpRequest->getBody(true);

        if ($requestBody) {

            $contentType = $this->httpRequest->getHeader('Content-Type');
            if (strpos($contentType,'application/xml')!==0 && strpos($contentType,'text/xml')!==0) {

                // We must throw 415 for unsupported mkcol bodies
                throw new Exception\UnsupportedMediaType('The request body for the MKCOL request must have an xml Content-Type');

            }

            $dom = XMLUtil::loadDOMDocument($requestBody);
            if (XMLUtil::toClarkNotation($dom->firstChild)!=='{DAV:}mkcol') {

                // We must throw 415 for unsupported mkcol bodies
                throw new Exception\UnsupportedMediaType('The request body for the MKCOL request must be a {DAV:}mkcol request construct.');

            }

            $properties = [];
            foreach($dom->firstChild->childNodes as $childNode) {

                if (XMLUtil::toClarkNotation($childNode)!=='{DAV:}set') continue;
                $properties = array_merge($properties, XMLUtil::parseProperties($childNode, $this->propertyMap));

            }
            if (!isset($properties['{DAV:}resourcetype']))
                throw new Exception\BadRequest('The mkcol request must include a {DAV:}resourcetype property');

            $resourceType = $properties['{DAV:}resourcetype']->getValue();
            unset($properties['{DAV:}resourcetype']);

        } else {

            $properties = [];
            $resourceType = ['{DAV:}collection'];

        }

        $result = $this->createCollection($uri, $resourceType, $properties);

        if (is_array($result)) {
            $this->httpResponse->setStatus(207);
            $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');

            $this->httpResponse->setBody(
                $this->generateMultiStatus([$result])
            );
            $this->httpResponse->send();

        } else {
            $this->httpResponse->setHeader('Content-Length','0');
            $this->httpResponse->setStatus(201);
        }

    }

    /**
     * WebDAV HTTP MOVE method
     *
     * This method moves one uri to a different uri. A lot of the actual request processing is done in getCopyMoveInfo
     *
     * @param string $uri
     * @return bool
     */
    protected function httpMove($uri) {

        $this->checkPreconditions();
        $moveInfo = $this->getCopyAndMoveInfo();

        // If the destination is part of the source tree, we must fail
        if ($moveInfo['destination']==$uri)
            throw new Exception\Forbidden('Source and destination uri are identical.');

        if ($moveInfo['destinationExists']) {

            if (!$this->emit('beforeUnbind',[$moveInfo['destination']])) return false;
            $this->tree->delete($moveInfo['destination']);
            $this->emit('afterUnbind',[$moveInfo['destination']]);

        }

        if (!$this->emit('beforeUnbind',[$uri])) return false;
        if (!$this->emit('beforeBind',[$moveInfo['destination']])) return false;
        $this->tree->move($uri,$moveInfo['destination']);
        $this->emit('afterUnbind',[$uri]);
        $this->emit('afterBind',[$moveInfo['destination']]);

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->setHeader('Content-Length','0');
        $this->httpResponse->setStatus($moveInfo['destinationExists']?204:201);

    }

    /**
     * WebDAV HTTP COPY method
     *
     * This method copies one uri to a different uri, and works much like the MOVE request
     * A lot of the actual request processing is done in getCopyMoveInfo
     *
     * @param string $uri
     * @return bool
     */
    protected function httpCopy($uri) {

        $this->checkPreconditions();
        $copyInfo = $this->getCopyAndMoveInfo();
        // If the destination is part of the source tree, we must fail
        if ($copyInfo['destination']==$uri)
            throw new Exception\Forbidden('Source and destination uri are identical.');

        if ($copyInfo['destinationExists']) {
            if (!$this->emit('beforeUnbind',[$copyInfo['destination']])) return false;
            $this->tree->delete($copyInfo['destination']);

        }
        if (!$this->emit('beforeBind',[$copyInfo['destination']])) return false;
        $this->tree->copy($uri,$copyInfo['destination']);
        $this->emit('afterBind',[$copyInfo['destination']]);

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->setHeader('Content-Length','0');
        $this->httpResponse->setStatus($copyInfo['destinationExists']?204:201);

    }



    /**
     * HTTP REPORT method implementation
     *
     * Although the REPORT method is not part of the standard WebDAV spec (it's from rfc3253)
     * It's used in a lot of extensions, so it made sense to implement it into the core.
     *
     * @param string $uri
     * @return void
     */
    protected function httpReport($uri) {

        $body = $this->httpRequest->getBody(true);
        $dom = XMLUtil::loadDOMDocument($body);

        $reportName = XMLUtil::toClarkNotation($dom->firstChild);

        if ($this->emit('report',[$reportName, $dom, $uri])) {

            // If emit returned true, it means the report was not supported
            throw new Exception\ReportNotSupported();

        }

    }

    // }}}
    // {{{ HTTP/WebDAV protocol helpers

    /**
     * Returns an array with all the supported HTTP methods for a specific uri.
     *
     * @param string $uri
     * @return array
     */
    public function getAllowedMethods($uri) {

        $methods = [
            'OPTIONS',
            'GET',
            'HEAD',
            'DELETE',
            'PROPFIND',
            'PUT',
            'PROPPATCH',
            'COPY',
            'MOVE',
            'REPORT'
        ];

        // The MKCOL is only allowed on an unmapped uri
        try {
            $this->tree->getNodeForPath($uri);
        } catch (Exception\NotFound $e) {
            $methods[] = 'MKCOL';
        }

        // We're also checking if any of the plugins register any new methods
        foreach($this->plugins as $plugin) $methods = array_merge($methods, $plugin->getHTTPMethods($uri));
        array_unique($methods);

        return $methods;

    }

    /**
     * Gets the uri for the request, keeping the base uri into consideration
     *
     * @return string
     */
    public function getRequestUri() {

        return $this->calculateUri($this->httpRequest->getUrl());

    }

    /**
     * Calculates the uri for a request, making sure that the base uri is stripped out
     *
     * @param string $uri
     * @throws Exception\Forbidden A permission denied exception is thrown whenever there was an attempt to supply a uri outside of the base uri
     * @return string
     */
    public function calculateUri($uri) {

        if ($uri[0]!='/' && strpos($uri,'://')) {

            $uri = parse_url($uri,PHP_URL_PATH);

        }

        $uri = str_replace('//','/',$uri);

        if (strpos($uri,$this->getBaseUri())===0) {

            return trim(URLUtil::decodePath(substr($uri,strlen($this->getBaseUri()))),'/');

        // A special case, if the baseUri was accessed without a trailing
        // slash, we'll accept it as well.
        } elseif ($uri.'/' === $this->getBaseUri()) {

            return '';

        } else {

            throw new Exception\Forbidden('Requested uri (' . $uri . ') is out of base uri (' . $this->getBaseUri() . ')');

        }

    }

    /**
     * Returns the HTTP depth header
     *
     * This method returns the contents of the HTTP depth request header. If the depth header was 'infinity' it will return the Sabre\DAV\Server::DEPTH_INFINITY object
     * It is possible to supply a default depth value, which is used when the depth header has invalid content, or is completely non-existent
     *
     * @param mixed $default
     * @return int
     */
    public function getHTTPDepth($default = self::DEPTH_INFINITY) {

        // If its not set, we'll grab the default
        $depth = $this->httpRequest->getHeader('Depth');

        if (is_null($depth)) return $default;

        if ($depth == 'infinity') return self::DEPTH_INFINITY;


        // If its an unknown value. we'll grab the default
        if (!ctype_digit($depth)) return $default;

        return (int)$depth;

    }

    /**
     * Returns the HTTP range header
     *
     * This method returns null if there is no well-formed HTTP range request
     * header or array($start, $end).
     *
     * The first number is the offset of the first byte in the range.
     * The second number is the offset of the last byte in the range.
     *
     * If the second offset is null, it should be treated as the offset of the last byte of the entity
     * If the first offset is null, the second offset should be used to retrieve the last x bytes of the entity
     *
     * @return array|null
     */
    public function getHTTPRange() {

        $range = $this->httpRequest->getHeader('range');
        if (is_null($range)) return null;

        // Matching "Range: bytes=1234-5678: both numbers are optional

        if (!preg_match('/^bytes=([0-9]*)-([0-9]*)$/i',$range,$matches)) return null;

        if ($matches[1]==='' && $matches[2]==='') return null;

        return [
            $matches[1]!==''?$matches[1]:null,
            $matches[2]!==''?$matches[2]:null,
        ];

    }

    /**
     * Returns the HTTP Prefer header information.
     *
     * The prefer header is defined in:
     * http://tools.ietf.org/html/draft-snell-http-prefer-14
     *
     * This method will return an array with options.
     *
     * Currently, the following options may be returned:
     *  [
     *      'return-asynch'         => true,
     *      'return-minimal'        => true,
     *      'return-representation' => true,
     *      'wait'                  => 30,
     *      'strict'                => true,
     *      'lenient'               => true,
     *  ]
     *
     * This method also supports the Brief header, and will also return
     * 'return-minimal' if the brief header was set to 't'.
     *
     * For the boolean options, false will be returned if the headers are not
     * specified. For the integer options it will be 'null'.
     *
     * @return array
     */
    public function getHTTPPrefer() {

        $result = [
            'return-asynch'         => false,
            'return-minimal'        => false,
            'return-representation' => false,
            'wait'                  => null,
            'strict'                => false,
            'lenient'               => false,
        ];

        if ($prefer = $this->httpRequest->getHeader('Prefer')) {

            $parameters = array_map('trim',
                explode(',', $prefer)
            );

            foreach($parameters as $parameter) {

                // Right now our regex only supports the tokens actually
                // specified in the draft. We may need to expand this if new
                // tokens get registered.
                if(!preg_match('/^(?P<token>[a-z0-9-]+)(?:=(?P<value>[0-9]+))?$/', $parameter, $matches)) {
                    continue;
                }

                switch($matches['token']) {

                    case 'return-asynch' :
                    case 'return-minimal' :
                    case 'return-representation' :
                    case 'strict' :
                    case 'lenient' :
                        $result[$matches['token']] = true;
                        break;
                    case 'wait' :
                        $result[$matches['token']] = $matches['value'];
                        break;

                }

            }

        }

        if ($this->httpRequest->getHeader('Brief')=='t') {
            $result['return-minimal'] = true;
        }

        return $result;

    }


    /**
     * Returns information about Copy and Move requests
     *
     * This function is created to help getting information about the source and the destination for the
     * WebDAV MOVE and COPY HTTP request. It also validates a lot of information and throws proper exceptions
     *
     * The returned value is an array with the following keys:
     *   * destination - Destination path
     *   * destinationExists - Whether or not the destination is an existing url (and should therefore be overwritten)
     *
     * @return array
     */
    public function getCopyAndMoveInfo() {

        // Collecting the relevant HTTP headers
        if (!$this->httpRequest->getHeader('Destination')) throw new Exception\BadRequest('The destination header was not supplied');
        $destination = $this->calculateUri($this->httpRequest->getHeader('Destination'));
        $overwrite = $this->httpRequest->getHeader('Overwrite');
        if (!$overwrite) $overwrite = 'T';
        if (strtoupper($overwrite)=='T') $overwrite = true;
        elseif (strtoupper($overwrite)=='F') $overwrite = false;
        // We need to throw a bad request exception, if the header was invalid
        else throw new Exception\BadRequest('The HTTP Overwrite header should be either T or F');

        list($destinationDir) = URLUtil::splitPath($destination);

        try {
            $destinationParent = $this->tree->getNodeForPath($destinationDir);
            if (!($destinationParent instanceof ICollection)) throw new Exception\UnsupportedMediaType('The destination node is not a collection');
        } catch (Exception\NotFound $e) {

            // If the destination parent node is not found, we throw a 409
            throw new Exception\Conflict('The destination node is not found');
        }

        try {

            $destinationNode = $this->tree->getNodeForPath($destination);

            // If this succeeded, it means the destination already exists
            // we'll need to throw precondition failed in case overwrite is false
            if (!$overwrite) throw new Exception\PreconditionFailed('The destination node already exists, and the overwrite header is set to false','Overwrite');

        } catch (Exception\NotFound $e) {

            // Destination didn't exist, we're all good
            $destinationNode = false;



        }

        // These are the three relevant properties we need to return
        return [
            'destination'       => $destination,
            'destinationExists' => $destinationNode==true,
            'destinationNode'   => $destinationNode,
        ];

    }

    /**
     * Returns a list of properties for a path
     *
     * This is a simplified version getPropertiesForPath.
     * if you aren't interested in status codes, but you just
     * want to have a flat list of properties. Use this method.
     *
     * @param string $path
     * @param array $propertyNames
     */
    public function getProperties($path, $propertyNames) {

        $result = $this->getPropertiesForPath($path,$propertyNames,0);
        return $result[0][200];

    }

    /**
     * A kid-friendly way to fetch properties for a node's children.
     *
     * The returned array will be indexed by the path of the of child node.
     * Only properties that are actually found will be returned.
     *
     * The parent node will not be returned.
     *
     * @param string $path
     * @param array $propertyNames
     * @return array
     */
    public function getPropertiesForChildren($path, $propertyNames) {

        $result = [];
        foreach($this->getPropertiesForPath($path,$propertyNames,1) as $k=>$row) {

            // Skipping the parent path
            if ($k === 0) continue;

            $result[$row['href']] = $row[200];

        }
        return $result;

    }

    /**
     * Returns a list of HTTP headers for a particular resource
     *
     * The generated http headers are based on properties provided by the
     * resource. The method basically provides a simple mapping between
     * DAV property and HTTP header.
     *
     * The headers are intended to be used for HEAD and GET requests.
     *
     * @param string $path
     * @return array
     */
    public function getHTTPHeaders($path) {

        $propertyMap = [
            '{DAV:}getcontenttype'   => 'Content-Type',
            '{DAV:}getcontentlength' => 'Content-Length',
            '{DAV:}getlastmodified'  => 'Last-Modified',
            '{DAV:}getetag'          => 'ETag',
        ];

        $properties = $this->getProperties($path,array_keys($propertyMap));

        $headers = [];
        foreach($propertyMap as $property=>$header) {
            if (!isset($properties[$property])) continue;

            if (is_scalar($properties[$property])) {
                $headers[$header] = $properties[$property];

            // GetLastModified gets special cased
            } elseif ($properties[$property] instanceof Property\GetLastModified) {
                $headers[$header] = HTTP\Util::toHTTPDate($properties[$property]->getTime());
            }

        }

        return $headers;

    }

    /**
     * Returns a list of properties for a given path
     *
     * The path that should be supplied should have the baseUrl stripped out
     * The list of properties should be supplied in Clark notation. If the list is empty
     * 'allprops' is assumed.
     *
     * If a depth of 1 is requested child elements will also be returned.
     *
     * @param string $path
     * @param array $propertyNames
     * @param int $depth
     * @return array
     */
    public function getPropertiesForPath($path, $propertyNames = [], $depth = 0) {

        if ($depth!=0) $depth = 1;

        $path = rtrim($path,'/');

        // This event allows people to intercept these requests early on in the
        // process.
        //
        // We're not doing anything with the result, but this can be helpful to
        // pre-fetch certain expensive live properties.
        $this->emit('beforeGetPropertiesForPath', [$path, $propertyNames, $depth]);

        $returnPropertyList = [];

        $parentNode = $this->tree->getNodeForPath($path);
        $nodes = [
            $path => $parentNode
        ];
        if ($depth==1 && $parentNode instanceof ICollection) {
            foreach($this->tree->getChildren($path) as $childNode)
                $nodes[$path . '/' . $childNode->getName()] = $childNode;
        }

        foreach($nodes as $myPath=>$node) {

            $r = $this->getPropertiesByNode($myPath, $node, $propertyNames);
            if ($r) {
                $returnPropertyList[] = $r;
            }

        }

        return $returnPropertyList;

    }

    /**
     * Returns a list of properties for a list of paths.
     *
     * The path that should be supplied should have the baseUrl stripped out
     * The list of properties should be supplied in Clark notation. If the list is empty
     * 'allprops' is assumed.
     *
     * The result is returned as an array, with paths for it's keys.
     * The result may be returned out of order.
     *
     * @param array $paths
     * @param array $propertyNames
     * @return array
     */
    public function getPropertiesForMultiplePaths(array $paths, array $propertyNames = []) {

        $result = [
        ];

        $nodes = $this->tree->getMultipleNodes($paths);

        foreach($nodes as $path=>$node) {

            $result[$path] = $this->getPropertiesByNode($path, $node, $propertyNames);

        }

        return $result;

    }


    /**
     * Determines all properties for a node.
     *
     * This method tries to grab all properties for a node. This method is used
     * internally getPropertiesForPath and a few others.
     *
     * It could be useful to call this, if you already have an instance of your
     * target node and simply want to run through the system to get a correct
     * list of properties.
     *
     * @param string $path The path we're properties for fetching.
     * @param INode $node
     * @param array $propertyNames list of properties to fetch.
     * @return array
     */
    public function getPropertiesByNode($path, INode $node, array $propertyNames) {

        $newProperties = [
            '200' => [],
            '404' => [],
        ];

        // If no properties were supplied, it means this was an 'allprops'
        // request, and we use a default set of properties.
        $allProperties = count($propertyNames)===0;

        if ($allProperties) {
            // Default list of propertyNames, when all properties were requested.
            $propertyNames = [
                '{DAV:}getlastmodified',
                '{DAV:}getcontentlength',
                '{DAV:}resourcetype',
                '{DAV:}quota-used-bytes',
                '{DAV:}quota-available-bytes',
                '{DAV:}getetag',
                '{DAV:}getcontenttype',
            ];
        }

        // If the resourceType was not part of the list, we manually add it
        // and mark it for removal. We need to know the resourcetype in order
        // to make certain decisions about the entry.
        // WebDAV dictates we should add a / and the end of href's for collections
        $removeRT = false;
        if (!in_array('{DAV:}resourcetype',$propertyNames)) {
            $propertyNames[] = '{DAV:}resourcetype';
            $removeRT = true;
        }

        $result = $this->emit('beforeGetProperties',[$path, $node, &$propertyNames, &$newProperties]);
        // If this method explicitly returned false, we must ignore this
        // node as it is inaccessible.
        if ($result===false) return;

        if (count($propertyNames) > 0) {

            if ($node instanceof IProperties) {
                $nodeProperties = $node->getProperties($propertyNames);

                // The getProperties method may give us too much,
                // properties, in case the implementor was lazy.
                //
                // So as we loop through this list, we will only take the
                // properties that were actually requested and discard the
                // rest.
                foreach($propertyNames as $k=>$propertyName) {
                    if (isset($nodeProperties[$propertyName])) {
                        unset($propertyNames[$k]);
                        $newProperties[200][$propertyName] = $nodeProperties[$propertyName];
                    }
                }

            }

        }

        foreach($propertyNames as $prop) {

            if (isset($newProperties[200][$prop])) continue;

            switch($prop) {
                case '{DAV:}getlastmodified'       : if ($node->getLastModified()) $newProperties[200][$prop] = new Property\GetLastModified($node->getLastModified()); break;
                case '{DAV:}getcontentlength'      :
                    if ($node instanceof IFile) {
                        $size = $node->getSize();
                        if (!is_null($size)) {
                            $newProperties[200][$prop] = (int)$node->getSize();
                        }
                    }
                    break;
                case '{DAV:}quota-used-bytes'      :
                    if ($node instanceof IQuota) {
                        $quotaInfo = $node->getQuotaInfo();
                        $newProperties[200][$prop] = $quotaInfo[0];
                    }
                    break;
                case '{DAV:}quota-available-bytes' :
                    if ($node instanceof IQuota) {
                        $quotaInfo = $node->getQuotaInfo();
                        $newProperties[200][$prop] = $quotaInfo[1];
                    }
                    break;
                case '{DAV:}getetag'               : if ($node instanceof IFile && $etag = $node->getETag())  $newProperties[200][$prop] = $etag; break;
                case '{DAV:}getcontenttype'        : if ($node instanceof IFile && $ct = $node->getContentType())  $newProperties[200][$prop] = $ct; break;
                case '{DAV:}supported-report-set'  :
                    $reports = [];
                    foreach($this->plugins as $plugin) {
                        $reports = array_merge($reports, $plugin->getSupportedReportSet($path));
                    }
                    $newProperties[200][$prop] = new Property\SupportedReportSet($reports);
                    break;
                case '{DAV:}resourcetype' :
                    $newProperties[200]['{DAV:}resourcetype'] = new Property\ResourceType();
                    foreach($this->resourceTypeMapping as $className => $resourceType) {
                        if ($node instanceof $className) $newProperties[200]['{DAV:}resourcetype']->add($resourceType);
                    }
                    break;

            }

            // If we were unable to find the property, we will list it as 404.
            if (!$allProperties && !isset($newProperties[200][$prop])) $newProperties[404][$prop] = null;

        }

        $this->emit('afterGetProperties',[trim($path,'/'),&$newProperties, $node]);

        $newProperties['href'] = trim($path,'/');

        // Its is a WebDAV recommendation to add a trailing slash to collectionnames.
        // Apple's iCal also requires a trailing slash for principals (rfc 3744), though this is non-standard.
        if ($path!='' && isset($newProperties[200]['{DAV:}resourcetype'])) {
            $rt = $newProperties[200]['{DAV:}resourcetype'];
            if ($rt->is('{DAV:}collection') || $rt->is('{DAV:}principal')) {
                $newProperties['href'] .='/';
            }
        }

        // If the resourcetype property was manually added to the requested property list,
        // we will remove it again.
        if ($removeRT) unset($newProperties[200]['{DAV:}resourcetype']);

        return $newProperties;

    }

    /**
     * This method is invoked by sub-systems creating a new file.
     *
     * Currently this is done by HTTP PUT and HTTP LOCK (in the Locks_Plugin).
     * It was important to get this done through a centralized function,
     * allowing plugins to intercept this using the beforeCreateFile event.
     *
     * This method will return true if the file was actually created
     *
     * @param string   $uri
     * @param resource $data
     * @param string   $etag
     * @return bool
     */
    public function createFile($uri,$data, &$etag = null) {

        list($dir,$name) = URLUtil::splitPath($uri);

        if (!$this->emit('beforeBind',[$uri])) return false;

        $parent = $this->tree->getNodeForPath($dir);
        if (!$parent instanceof ICollection) {
            throw new Exception\Conflict('Files can only be created as children of collections');
        }

        // It is possible for an event handler to modify the content of the
        // body, before it gets written. If this is the case, $modified
        // should be set to true.
        //
        // If $modified is true, we must not send back an etag.
        $modified = false;
        if (!$this->emit('beforeCreateFile',[$uri, &$data, $parent, &$modified])) return false;

        $etag = $parent->createFile($name,$data);

        if ($modified) $etag = null;

        $this->tree->markDirty($dir . '/' . $name);

        $this->emit('afterBind',[$uri]);
        $this->emit('afterCreateFile',[$uri, $parent]);

        return true;
    }

    /**
     * This method is invoked by sub-systems creating a new directory.
     *
     * @param string $uri
     * @return void
     */
    public function createDirectory($uri) {

        $this->createCollection($uri,['{DAV:}collection'], []);

    }

    /**
     * Use this method to create a new collection
     *
     * The {DAV:}resourcetype is specified using the resourceType array.
     * At the very least it must contain {DAV:}collection.
     *
     * The properties array can contain a list of additional properties.
     *
     * @param string $uri The new uri
     * @param array $resourceType The resourceType(s)
     * @param array $properties A list of properties
     * @return array|null
     */
    public function createCollection($uri, array $resourceType, array $properties) {

        list($parentUri,$newName) = URLUtil::splitPath($uri);

        // Making sure {DAV:}collection was specified as resourceType
        if (!in_array('{DAV:}collection', $resourceType)) {
            throw new Exception\InvalidResourceType('The resourceType for this collection must at least include {DAV:}collection');
        }


        // Making sure the parent exists
        try {

            $parent = $this->tree->getNodeForPath($parentUri);

        } catch (Exception\NotFound $e) {

            throw new Exception\Conflict('Parent node does not exist');

        }

        // Making sure the parent is a collection
        if (!$parent instanceof ICollection) {
            throw new Exception\Conflict('Parent node is not a collection');
        }



        // Making sure the child does not already exist
        try {
            $parent->getChild($newName);

            // If we got here.. it means there's already a node on that url, and we need to throw a 405
            throw new Exception\MethodNotAllowed('The resource you tried to create already exists');

        } catch (Exception\NotFound $e) {
            // This is correct
        }


        if (!$this->emit('beforeBind',[$uri])) return;

        // There are 2 modes of operation. The standard collection
        // creates the directory, and then updates properties
        // the extended collection can create it directly.
        if ($parent instanceof IExtendedCollection) {

            $parent->createExtendedCollection($newName, $resourceType, $properties);

        } else {

            // No special resourcetypes are supported
            if (count($resourceType)>1) {
                throw new Exception\InvalidResourceType('The {DAV:}resourcetype you specified is not supported here.');
            }

            $parent->createDirectory($newName);
            $rollBack = false;
            $exception = null;
            $errorResult = null;

            if (count($properties)>0) {

                try {

                    $errorResult = $this->updateProperties($uri, $properties);
                    if (!isset($errorResult[200])) {
                        $rollBack = true;
                    }

                } catch (Exception $e) {

                    $rollBack = true;
                    $exception = $e;

                }

            }

            if ($rollBack) {
                if (!$this->emit('beforeUnbind',[$uri])) return;
                $this->tree->delete($uri);

                // Re-throwing exception
                if ($exception) throw $exception;

                return $errorResult;
            }

        }
        $this->tree->markDirty($parentUri);
        $this->emit('afterBind',[$uri]);

    }

    /**
     * This method updates a resource's properties
     *
     * The properties array must be a list of properties. Array-keys are
     * property names in clarknotation, array-values are it's values.
     * If a property must be deleted, the value should be null.
     *
     * Note that this request should either completely succeed, or
     * completely fail.
     *
     * The response is an array with statuscodes for keys, which in turn
     * contain arrays with propertynames. This response can be used
     * to generate a multistatus body.
     *
     * @param string $uri
     * @param array $properties
     * @return array
     */
    public function updateProperties($uri, array $properties) {

        // we'll start by grabbing the node, this will throw the appropriate
        // exceptions if it doesn't.
        $node = $this->tree->getNodeForPath($uri);

        $result = [
            200 => [],
            403 => [],
            424 => [],
        ];
        $remainingProperties = $properties;
        $hasError = false;

        // Running through all properties to make sure none of them are protected
        if (!$hasError) foreach($properties as $propertyName => $value) {
            if(in_array($propertyName, $this->protectedProperties)) {
                $result[403][$propertyName] = null;
                unset($remainingProperties[$propertyName]);
                $hasError = true;
            }
        }

        if (!$hasError) {
            // Allowing plugins to take care of property updating
            $hasError = !$this->emit('updateProperties', [
                &$remainingProperties,
                &$result,
                $node
            ]);
        }

        // If the node is not an instance of Sabre\DAV\IProperties, every
        // property is 403 Forbidden
        if (!$hasError && count($remainingProperties) && !($node instanceof IProperties)) {
            $hasError = true;
            foreach($properties as $propertyName=> $value) {
                $result[403][$propertyName] = null;
            }
            $remainingProperties = [];
        }

        // Only if there were no errors we may attempt to update the resource
        if (!$hasError) {

            if (count($remainingProperties)>0) {

                $updateResult = $node->updateProperties($remainingProperties);

                if ($updateResult===true) {
                    // success
                    foreach($remainingProperties as $propertyName=>$value) {
                        $result[200][$propertyName] = null;
                    }

                } elseif ($updateResult===false) {
                    // The node failed to update the properties for an
                    // unknown reason
                    foreach($remainingProperties as $propertyName=>$value) {
                        $result[403][$propertyName] = null;
                    }

                } elseif (is_array($updateResult)) {

                    // The node has detailed update information
                    // We need to merge the results with the earlier results.
                    foreach($updateResult as $status => $props) {
                        if (is_array($props)) {
                            if (!isset($result[$status]))
                                $result[$status] = [];

                            $result[$status] = array_merge($result[$status], $updateResult[$status]);
                        }
                    }

                } else {
                    throw new Exception('Invalid result from updateProperties');
                }
                $remainingProperties = [];
            }

        }

        foreach($remainingProperties as $propertyName=>$value) {
            // if there are remaining properties, it must mean
            // there's a dependency failure
            $result[424][$propertyName] = null;
        }

        // Removing empty array values
        foreach($result as $status=>$props) {

            if (count($props)===0) unset($result[$status]);

        }
        $result['href'] = $uri;
        return $result;

    }

    /**
     * This method checks the main HTTP preconditions.
     *
     * Currently these are:
     *   * If-Match
     *   * If-None-Match
     *   * If-Modified-Since
     *   * If-Unmodified-Since
     *
     * The method will return true if all preconditions are met
     * The method will return false, or throw an exception if preconditions
     * failed. If false is returned the operation should be aborted, and
     * the appropriate HTTP response headers are already set.
     *
     * Normally this method will throw 412 Precondition Failed for failures
     * related to If-None-Match, If-Match and If-Unmodified Since. It will
     * set the status to 304 Not Modified for If-Modified_since.
     *
     * If the $handleAsGET argument is set to true, it will also return 304
     * Not Modified for failure of the If-None-Match precondition. This is the
     * desired behaviour for HTTP GET and HTTP HEAD requests.
     *
     * @param bool $handleAsGET
     * @return bool
     */
    public function checkPreconditions($handleAsGET = false) {

        $uri = $this->getRequestUri();
        $node = null;
        $lastMod = null;
        $etag = null;

        if ($ifMatch = $this->httpRequest->getHeader('If-Match')) {

            // If-Match contains an entity tag. Only if the entity-tag
            // matches we are allowed to make the request succeed.
            // If the entity-tag is '*' we are only allowed to make the
            // request succeed if a resource exists at that url.
            try {
                $node = $this->tree->getNodeForPath($uri);
            } catch (Exception\NotFound $e) {
                throw new Exception\PreconditionFailed('An If-Match header was specified and the resource did not exist','If-Match');
            }

            // Only need to check entity tags if they are not *
            if ($ifMatch!=='*') {

                // There can be multiple etags
                $ifMatch = explode(',',$ifMatch);
                $haveMatch = false;
                foreach($ifMatch as $ifMatchItem) {

                    // Stripping any extra spaces
                    $ifMatchItem = trim($ifMatchItem,' ');

                    $etag = $node->getETag();
                    if ($etag===$ifMatchItem) {
                        $haveMatch = true;
                    } else {
                        // Evolution has a bug where it sometimes prepends the "
                        // with a \. This is our workaround.
                        if (str_replace('\\"','"', $ifMatchItem) === $etag) {
                            $haveMatch = true;
                        }
                    }

                }
                if (!$haveMatch) {
                     throw new Exception\PreconditionFailed('An If-Match header was specified, but none of the specified the ETags matched.','If-Match');
                }
            }
        }

        if ($ifNoneMatch = $this->httpRequest->getHeader('If-None-Match')) {

            // The If-None-Match header contains an etag.
            // Only if the ETag does not match the current ETag, the request will succeed
            // The header can also contain *, in which case the request
            // will only succeed if the entity does not exist at all.
            $nodeExists = true;
            if (!$node) {
                try {
                    $node = $this->tree->getNodeForPath($uri);
                } catch (Exception\NotFound $e) {
                    $nodeExists = false;
                }
            }
            if ($nodeExists) {
                $haveMatch = false;
                if ($ifNoneMatch==='*') $haveMatch = true;
                else {

                    // There might be multiple etags
                    $ifNoneMatch = explode(',', $ifNoneMatch);
                    $etag = $node->getETag();

                    foreach($ifNoneMatch as $ifNoneMatchItem) {

                        // Stripping any extra spaces
                        $ifNoneMatchItem = trim($ifNoneMatchItem,' ');

                        if ($etag===$ifNoneMatchItem) $haveMatch = true;

                    }

                }

                if ($haveMatch) {
                    if ($handleAsGET) {
                        $this->httpResponse->setStatus(304);
                        return false;
                    } else {
                        throw new Exception\PreconditionFailed('An If-None-Match header was specified, but the ETag matched (or * was specified).','If-None-Match');
                    }
                }
            }

        }

        if (!$ifNoneMatch && ($ifModifiedSince = $this->httpRequest->getHeader('If-Modified-Since'))) {

            // The If-Modified-Since header contains a date. We
            // will only return the entity if it has been changed since
            // that date. If it hasn't been changed, we return a 304
            // header
            // Note that this header only has to be checked if there was no If-None-Match header
            // as per the HTTP spec.
            $date = HTTP\Util::parseHTTPDate($ifModifiedSince);

            if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($uri);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new \DateTime('@' . $lastMod);
                    if ($lastMod <= $date) {
                        $this->httpResponse->setStatus(304);
                        $this->httpResponse->setHeader('Last-Modified', HTTP\Util::toHTTPDate($lastMod));
                        return false;
                    }
                }
            }
        }

        if ($ifUnmodifiedSince = $this->httpRequest->getHeader('If-Unmodified-Since')) {

            // The If-Unmodified-Since will allow allow the request if the
            // entity has not changed since the specified date.
            $date = HTTP\Util::parseHTTPDate($ifUnmodifiedSince);

            // We must only check the date if it's valid
            if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($uri);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new \DateTime('@' . $lastMod);
                    if ($lastMod > $date) {
                        throw new Exception\PreconditionFailed('An If-Unmodified-Since header was specified, but the entity has been changed since the specified date.','If-Unmodified-Since');
                    }
                }
            }

        }

        // Now the hardest, the If: header. The If: header can contain multiple
        // urls, etags and so-called 'state tokens'.
        //
        // Examples of state tokens include lock-tokens (as defined in rfc4918)
        // and sync-tokens (as defined in rfc6578).
        //
        // The only proper way to deal with these, is to emit events, that a
        // Sync and Lock plugin can pick up.
        $ifConditions = $this->getIfConditions();

        foreach($ifConditions as $kk => $ifCondition) {
            foreach($ifCondition['tokens'] as $ii => $token) {
                $ifConditions[$kk]['tokens'][$ii]['validToken'] = false;
            }
        }

        // Plugins are responsible for validating all the tokens.
        // If a plugin deemed a token 'valid', it will set 'validToken' to
        // true.
        $this->emit('validateTokens', [ &$ifConditions ]);

        // Now we're going to analyze the result.

        // Every ifCondition needs to validate to true, so we exit as soon as
        // we have an invalid condition.
        foreach($ifConditions as $ifCondition) {

            $uri = $ifCondition['uri'];
            $tokens = $ifCondition['tokens'];

            // We only need 1 valid token for the condition to succeed.
            foreach($tokens as $token) {

                $tokenValid = $token['validToken'] || !$token['token'];

                $etagValid = false;
                if (!$token['etag']) {
                    $etagValid = true;
                }
                // Checking the etag, only if the token was already deamed
                // valid and there is one.
                if ($token['etag'] && $tokenValid) {

                    // The token was valid, and there was an etag.. We must
                    // grab the current etag and check it.
                    $node = $this->tree->getNodeForPath($uri);
                    $etagValid = $node->getETag() == $token['etag'];

                }


                if (($tokenValid && $etagValid) ^ $token['negate']) {
                    // Both were valid, so we can go to the next condition.
                    continue 2;
                }


            }

            // If we ended here, it means there was no valid etag + token
            // combination found for the current condition. This means we fail!
            throw new Exception\PreconditionFailed('Failed to find a valid token/etag combination for ' . $uri, 'If');

        }

        return true;

    }

    /**
     * This method is created to extract information from the WebDAV HTTP 'If:' header
     *
     * The If header can be quite complex, and has a bunch of features. We're using a regex to extract all relevant information
     * The function will return an array, containing structs with the following keys
     *
     *   * uri   - the uri the condition applies to.
     *   * tokens - The lock token. another 2 dimensional array containing 3 elements
     *
     * Example 1:
     *
     * If: (<opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2>)
     *
     * Would result in:
     *
     * [
     *    [
     *       'uri' => '/request/uri',
     *       'tokens' => [
     *          [
     *              [
     *                  'negate' => false,
     *                  'token'  => 'opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2',
     *                  'etag'   => ""
     *              ]
     *          ]
     *       ],
     *    ]
     * ]
     *
     * Example 2:
     *
     * If: </path/> (Not <opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2> ["Im An ETag"]) (["Another ETag"]) </path2/> (Not ["Path2 ETag"])
     *
     * Would result in:
     *
     * [
     *    [
     *       'uri' => 'path',
     *       'tokens' => [
     *          [
     *              [
     *                  'negate' => true,
     *                  'token'  => 'opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2',
     *                  'etag'   => '"Im An ETag"'
     *              ],
     *              [
     *                  'negate' => false,
     *                  'token'  => '',
     *                  'etag'   => '"Another ETag"'
     *              ]
     *          ]
     *       ],
     *    ],
     *    [
     *       'uri' => 'path2',
     *       'tokens' => [
     *          [
     *              [
     *                  'negate' => true,
     *                  'token'  => '',
     *                  'etag'   => '"Path2 ETag"'
     *              ]
     *          ]
     *       ],
     *    ],
     * ]
     *
     * @return array
     */
    public function getIfConditions() {

        $header = $this->httpRequest->getHeader('If');
        if (!$header) return [];

        $matches = [];

        $regex = '/(?:\<(?P<uri>.*?)\>\s)?\((?P<not>Not\s)?(?:\<(?P<token>[^\>]*)\>)?(?:\s?)(?:\[(?P<etag>[^\]]*)\])?\)/im';
        preg_match_all($regex,$header,$matches,PREG_SET_ORDER);

        $conditions = [];

        foreach($matches as $match) {

            // If there was no uri specified in this match, and there were
            // already conditions parsed, we add the condition to the list of
            // conditions for the previous uri.
            if (!$match['uri'] && count($conditions)) {
                $conditions[count($conditions)-1]['tokens'][] = [
                    'negate' => $match['not']?true:false,
                    'token'  => $match['token'],
                    'etag'   => isset($match['etag'])?$match['etag']:''
                ];
            } else {

                if (!$match['uri']) {
                    $realUri = $this->getRequestUri();
                } else {
                    $realUri = $this->calculateUri($match['uri']);
                }

                $conditions[] = [
                    'uri'   => $realUri,
                    'tokens' => [
                        [
                            'negate' => $match['not']?true:false,
                            'token'  => $match['token'],
                            'etag'   => isset($match['etag'])?$match['etag']:''
                        ]
                    ],

                ];
            }

        }

        return $conditions;

    }

    // }}}
    // {{{ XML Readers & Writers


    /**
     * Generates a WebDAV propfind response body based on a list of nodes.
     *
     * If 'strip404s' is set to true, all 404 responses will be removed.
     *
     * @param array $fileProperties The list with nodes
     * @param bool strip404s
     * @return string
     */
    public function generateMultiStatus(array $fileProperties, $strip404s = false) {

        $dom = new \DOMDocument('1.0','utf-8');
        //$dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($fileProperties as $entry) {

            $href = $entry['href'];
            unset($entry['href']);

            if ($strip404s && isset($entry[404])) {
                unset($entry[404]);
            }

            $response = new Property\Response($href,$entry);
            $response->serialize($this,$multiStatus);

        }

        return $dom->saveXML();

    }

    /**
     * This method parses a PropPatch request
     *
     * PropPatch changes the properties for a resource. This method
     * returns a list of properties.
     *
     * The keys in the returned array contain the property name (e.g.: {DAV:}displayname,
     * and the value contains the property value. If a property is to be removed the value
     * will be null.
     *
     * @param string $body xml body
     * @return array list of properties in need of updating or deletion
     */
    public function parsePropPatchRequest($body) {

        //We'll need to change the DAV namespace declaration to something else in order to make it parsable
        $dom = XMLUtil::loadDOMDocument($body);

        $newProperties = [];

        foreach($dom->firstChild->childNodes as $child) {

            if ($child->nodeType !== XML_ELEMENT_NODE) continue;

            $operation = XMLUtil::toClarkNotation($child);

            if ($operation!=='{DAV:}set' && $operation!=='{DAV:}remove') continue;

            $innerProperties = XMLUtil::parseProperties($child, $this->propertyMap);

            foreach($innerProperties as $propertyName=>$propertyValue) {

                if ($operation==='{DAV:}remove') {
                    $propertyValue = null;
                }

                $newProperties[$propertyName] = $propertyValue;

            }

        }

        return $newProperties;

    }

    /**
     * This method parses the PROPFIND request and returns its information
     *
     * This will either be a list of properties, or an empty array; in which case
     * an {DAV:}allprop was requested.
     *
     * @param string $body
     * @return array
     */
    public function parsePropFindRequest($body) {

        // If the propfind body was empty, it means IE is requesting 'all' properties
        if (!$body) return [];

        $dom = XMLUtil::loadDOMDocument($body);
        $elem = $dom->getElementsByTagNameNS('urn:DAV','propfind')->item(0);
        if (is_null($elem)) throw new Exception\UnsupportedMediaType('We could not find a {DAV:}propfind element in the xml request body');

        return array_keys(XMLUtil::parseProperties($elem));

    }

    // }}}

}

