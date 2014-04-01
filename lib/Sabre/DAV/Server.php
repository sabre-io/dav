<?php

/**
 * Main DAV server class
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Server {

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
     * @var Sabre_DAV_Tree
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
     * @var Sabre_HTTP_Response
     */
    public $httpResponse;

    /**
     * httpRequest
     *
     * @var Sabre_HTTP_Request
     */
    public $httpRequest;

    /**
     * The list of plugins
     *
     * @var array
     */
    protected $plugins = array();

    /**
     * This array contains a list of callbacks we should call when certain events are triggered
     *
     * @var array
     */
    protected $eventSubscriptions = array();

    /**
     * This is a default list of namespaces.
     *
     * If you are defining your own custom namespace, add it here to reduce
     * bandwidth and improve legibility of xml bodies.
     *
     * @var array
     */
    public $xmlNamespaces = array(
        'DAV:' => 'd',
        'http://sabredav.org/ns' => 's',
    );

    /**
     * The propertymap can be used to map properties from
     * requests to property classes.
     *
     * @var array
     */
    public $propertyMap = array(
        '{DAV:}resourcetype' => 'Sabre_DAV_Property_ResourceType',
    );

    public $protectedProperties = array(
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

    );

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
     * implementing Sabre_DAV_ICollection.
     *
     * @var array
     */
    public $resourceTypeMapping = array(
        'Sabre_DAV_ICollection' => '{DAV:}collection',
    );

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
     * If a Sabre_DAV_Tree object is passed as an argument, it will
     * use it as the directory tree. If a Sabre_DAV_INode is passed, it
     * will create a Sabre_DAV_ObjectTree and use the node as the root.
     *
     * If nothing is passed, a Sabre_DAV_SimpleCollection is created in
     * a Sabre_DAV_ObjectTree.
     *
     * If an array is passed, we automatically create a root node, and use
     * the nodes in the array as top-level children.
     *
     * @param Sabre_DAV_Tree|Sabre_DAV_INode|array|null $treeOrNode The tree object
     */
    public function __construct($treeOrNode = null) {

        if ($treeOrNode instanceof Sabre_DAV_Tree) {
            $this->tree = $treeOrNode;
        } elseif ($treeOrNode instanceof Sabre_DAV_INode) {
            $this->tree = new Sabre_DAV_ObjectTree($treeOrNode);
        } elseif (is_array($treeOrNode)) {

            // If it's an array, a list of nodes was passed, and we need to
            // create the root node.
            foreach($treeOrNode as $node) {
                if (!($node instanceof Sabre_DAV_INode)) {
                    throw new Sabre_DAV_Exception('Invalid argument passed to constructor. If you\'re passing an array, all the values must implement Sabre_DAV_INode');
                }
            }

            $root = new Sabre_DAV_SimpleCollection('root', $treeOrNode);
            $this->tree = new Sabre_DAV_ObjectTree($root);

        } elseif (is_null($treeOrNode)) {
            $root = new Sabre_DAV_SimpleCollection('root');
            $this->tree = new Sabre_DAV_ObjectTree($root);
        } else {
            throw new Sabre_DAV_Exception('Invalid argument passed to constructor. Argument must either be an instance of Sabre_DAV_Tree, Sabre_DAV_INode, an array or null');
        }
        $this->httpResponse = new Sabre_HTTP_Response();
        $this->httpRequest = new Sabre_HTTP_Request();

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
            $this->httpResponse->defaultHttpVersion = $this->httpRequest->getHTTPVersion();

            $this->invokeMethod($this->httpRequest->getMethod(), $this->getRequestUri());

        } catch (Exception $e) {

            try {
                $this->broadcastEvent('exception', array($e));
            } catch (Exception $ignore) {
            }
            $DOM = new DOMDocument('1.0','utf-8');
            $DOM->formatOutput = true;

            $error = $DOM->createElementNS('DAV:','d:error');
            $error->setAttribute('xmlns:s',self::NS_SABREDAV);
            $DOM->appendChild($error);

            $h = function($v) {

                return htmlspecialchars($v, ENT_NOQUOTES, 'UTF-8');

            };

            $error->appendChild($DOM->createElement('s:exception',$h(get_class($e))));
            $error->appendChild($DOM->createElement('s:message',$h($e->getMessage())));
            if ($this->debugExceptions) {
                $error->appendChild($DOM->createElement('s:file',$h($e->getFile())));
                $error->appendChild($DOM->createElement('s:line',$h($e->getLine())));
                $error->appendChild($DOM->createElement('s:code',$h($e->getCode())));
                $error->appendChild($DOM->createElement('s:stacktrace',$h($e->getTraceAsString())));

            }
            if (self::$exposeVersion) {
                $error->appendChild($DOM->createElement('s:sabredav-version',$h(Sabre_DAV_Version::VERSION)));
            }

            if($e instanceof Sabre_DAV_Exception) {

                $httpCode = $e->getHTTPCode();
                $e->serialize($this,$error);
                $headers = $e->getHTTPHeaders($this);

            } else {

                $httpCode = 500;
                $headers = array();

            }
            $headers['Content-Type'] = 'application/xml; charset=utf-8';

            $this->httpResponse->sendStatus($httpCode);
            $this->httpResponse->setHeaders($headers);
            $this->httpResponse->sendBody($DOM->saveXML());

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
            $decodedUri = Sabre_DAV_URLUtil::decodePath($uri);

            // A simple sanity check:
            if(substr($decodedUri,strlen($decodedUri)-strlen($pathInfo))===$pathInfo) {
                $baseUri = substr($decodedUri,0,strlen($decodedUri)-strlen($pathInfo));
                return rtrim($baseUri,'/') . '/';
            }

            throw new Sabre_DAV_Exception('The REQUEST_URI ('. $uri . ') did not end with the contents of PATH_INFO (' . $pathInfo . '). This server might be misconfigured.');

        }

        // The last fallback is that we're just going to assume the server root.
        return '/';

    }

    /**
     * Adds a plugin to the server
     *
     * For more information, console the documentation of Sabre_DAV_ServerPlugin
     *
     * @param Sabre_DAV_ServerPlugin $plugin
     * @return void
     */
    public function addPlugin(Sabre_DAV_ServerPlugin $plugin) {

        $this->plugins[$plugin->getPluginName()] = $plugin;
        $plugin->initialize($this);

    }

    /**
     * Returns an initialized plugin by it's name.
     *
     * This function returns null if the plugin was not found.
     *
     * @param string $name
     * @return Sabre_DAV_ServerPlugin
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
     * Subscribe to an event.
     *
     * When the event is triggered, we'll call all the specified callbacks.
     * It is possible to control the order of the callbacks through the
     * priority argument.
     *
     * This is for example used to make sure that the authentication plugin
     * is triggered before anything else. If it's not needed to change this
     * number, it is recommended to ommit.
     *
     * @param string $event
     * @param callback $callback
     * @param int $priority
     * @return void
     */
    public function subscribeEvent($event, $callback, $priority = 100) {

        if (!isset($this->eventSubscriptions[$event])) {
            $this->eventSubscriptions[$event] = array();
        }
        while(isset($this->eventSubscriptions[$event][$priority])) $priority++;
        $this->eventSubscriptions[$event][$priority] = $callback;
        ksort($this->eventSubscriptions[$event]);

    }

    /**
     * Broadcasts an event
     *
     * This method will call all subscribers. If one of the subscribers returns false, the process stops.
     *
     * The arguments parameter will be sent to all subscribers
     *
     * @param string $eventName
     * @param array $arguments
     * @return bool
     */
    public function broadcastEvent($eventName,$arguments = array()) {

        if (isset($this->eventSubscriptions[$eventName])) {

            foreach($this->eventSubscriptions[$eventName] as $subscriber) {

                $result = call_user_func_array($subscriber,$arguments);
                if ($result===false) return false;

            }

        }

        return true;

    }

    /**
     * Handles a http request, and execute a method based on its name
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function invokeMethod($method, $uri) {

        $method = strtoupper($method);

        if (!$this->broadcastEvent('beforeMethod',array($method, $uri))) return;

        // Make sure this is a HTTP method we support
        $internalMethods = array(
            'OPTIONS',
            'GET',
            'HEAD',
            'DELETE',
            'PROPFIND',
            'MKCOL',
            'PUT',
            'PROPPATCH',
            'COPY',
            'MOVE',
            'REPORT'
        );

        if (in_array($method,$internalMethods)) {

            call_user_func(array($this,'http' . $method), $uri);

        } else {

            if ($this->broadcastEvent('unknownMethod',array($method, $uri))) {
                // Unsupported method
                throw new Sabre_DAV_Exception_NotImplemented('There was no handler found for this "' . $method . '" method');
            }

        }

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
        $features = array('1','3', 'extended-mkcol');

        foreach($this->plugins as $plugin) $features = array_merge($features,$plugin->getFeatures());

        $this->httpResponse->setHeader('DAV',implode(', ',$features));
        $this->httpResponse->setHeader('MS-Author-Via','DAV');
        $this->httpResponse->setHeader('Accept-Ranges','bytes');
        if (self::$exposeVersion) {
            $this->httpResponse->setHeader('X-Sabre-Version',Sabre_DAV_Version::VERSION);
        }
        $this->httpResponse->setHeader('Content-Length',0);
        $this->httpResponse->sendStatus(200);

    }

    /**
     * HTTP GET
     *
     * This method simply fetches the contents of a uri, like normal
     *
     * @param string $uri
     * @return bool
     */
    protected function httpGet($uri) {

        $node = $this->tree->getNodeForPath($uri,0);

        if (!$this->checkPreconditions(true)) return false;

        if (!$node instanceof Sabre_DAV_IFile) throw new Sabre_DAV_Exception_NotImplemented('GET is only implemented on File objects');
        $body = $node->get();

        // Converting string into stream, if needed.
        if (is_string($body)) {
            $stream = fopen('php://temp','r+');
            fwrite($stream,$body);
            rewind($stream);
            $body = $stream;
        }

        /*
         * TODO: getetag, getlastmodified, getsize should also be used using
         * this method
         */
        $httpHeaders = $this->getHTTPHeaders($uri);

        /* ContentType needs to get a default, because many webservers will otherwise
         * default to text/html, and we don't want this for security reasons.
         */
        if (!isset($httpHeaders['Content-Type'])) {
            $httpHeaders['Content-Type'] = 'application/octet-stream';
        }


        if (isset($httpHeaders['Content-Length'])) {

            $nodeSize = $httpHeaders['Content-Length'];

            // Need to unset Content-Length, because we'll handle that during figuring out the range
            unset($httpHeaders['Content-Length']);

        } else {
            $nodeSize = null;
        }

        $this->httpResponse->setHeaders($httpHeaders);

        $range = $this->getHTTPRange();
        $ifRange = $this->httpRequest->getHeader('If-Range');
        $ignoreRangeHeader = false;

        // If ifRange is set, and range is specified, we first need to check
        // the precondition.
        if ($nodeSize && $range && $ifRange) {

            // if IfRange is parsable as a date we'll treat it as a DateTime
            // otherwise, we must treat it as an etag.
            try {
                $ifRangeDate = new DateTime($ifRange);

                // It's a date. We must check if the entity is modified since
                // the specified date.
                if (!isset($httpHeaders['Last-Modified'])) $ignoreRangeHeader = true;
                else {
                    $modified = new DateTime($httpHeaders['Last-Modified']);
                    if($modified > $ifRangeDate) $ignoreRangeHeader = true;
                }

            } catch (Exception $e) {

                // It's an entity. We can do a simple comparison.
                if (!isset($httpHeaders['ETag'])) $ignoreRangeHeader = true;
                elseif ($httpHeaders['ETag']!==$ifRange) $ignoreRangeHeader = true;
            }
        }

        // We're only going to support HTTP ranges if the backend provided a filesize
        if (!$ignoreRangeHeader && $nodeSize && $range) {

            // Determining the exact byte offsets
            if (!is_null($range[0])) {

                $start = $range[0];
                $end = $range[1]?$range[1]:$nodeSize-1;
                if($start >= $nodeSize)
                    throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('The start offset (' . $range[0] . ') exceeded the size of the entity (' . $nodeSize . ')');

                if($end < $start) throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('The end offset (' . $range[1] . ') is lower than the start offset (' . $range[0] . ')');
                if($end >= $nodeSize) $end = $nodeSize-1;

            } else {

                $start = $nodeSize-$range[1];
                $end  = $nodeSize-1;

                if ($start<0) $start = 0;

            }

            // New read/write stream
            $newStream = fopen('php://temp','r+');

            // stream_copy_to_stream() has a bug/feature: the `whence` argument
            // is interpreted as SEEK_SET (count from absolute offset 0), while
            // for a stream it should be SEEK_CUR (count from current offset).
            // If a stream is nonseekable, the function fails. So we *emulate*
            // the correct behaviour with fseek():
            if ($start > 0) {
                if (($curOffs = ftell($body)) === false) $curOffs = 0;
                fseek($body, $start - $curOffs, SEEK_CUR);
            }
            stream_copy_to_stream($body, $newStream, $end-$start+1);
            rewind($newStream);

            $this->httpResponse->setHeader('Content-Length', $end-$start+1);
            $this->httpResponse->setHeader('Content-Range','bytes ' . $start . '-' . $end . '/' . $nodeSize);
            $this->httpResponse->sendStatus(206);
            $this->httpResponse->sendBody($newStream);


        } else {

            if ($nodeSize) $this->httpResponse->setHeader('Content-Length',$nodeSize);
            $this->httpResponse->sendStatus(200);
            $this->httpResponse->sendBody($body);

        }

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
        if ($node instanceof Sabre_DAV_IFile) {
            $headers = $this->getHTTPHeaders($this->getRequestUri());
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/octet-stream';
            }
            $this->httpResponse->setHeaders($headers);
        }
        $this->httpResponse->sendStatus(200);

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

        if (!$this->broadcastEvent('beforeUnbind',array($uri))) return;

        // Checking If-None-Match and related headers.
        if (!$this->checkPreconditions()) return;

        $this->tree->delete($uri);
        $this->broadcastEvent('afterUnbind',array($uri));

        $this->httpResponse->sendStatus(204);
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

        // $xml = new Sabre_DAV_XMLReader(file_get_contents('php://input'));
        $requestedProperties = $this->parsePropFindRequest($this->httpRequest->getBody(true));

        $depth = $this->getHTTPDepth(1);
        // The only two options for the depth of a propfind is 0 or 1
        if ($depth!=0) $depth = 1;

        $newProperties = $this->getPropertiesForPath($uri,$requestedProperties,$depth);

        // This is a multi-status response
        $this->httpResponse->sendStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->httpResponse->setHeader('Vary','Brief,Prefer');

        // Normally this header is only needed for OPTIONS responses, however..
        // iCal seems to also depend on these being set for PROPFIND. Since
        // this is not harmful, we'll add it.
        $features = array('1','3', 'extended-mkcol');
        foreach($this->plugins as $plugin) $features = array_merge($features,$plugin->getFeatures());
        $this->httpResponse->setHeader('DAV',implode(', ',$features));

        $prefer = $this->getHTTPPrefer();
        $minimal = $prefer['return-minimal'];

        $data = $this->generateMultiStatus($newProperties, $minimal);
        $this->httpResponse->sendBody($data);

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

                $this->httpResponse->sendStatus(204);
                return;

            }

        }

        $this->httpResponse->sendStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');

        $this->httpResponse->sendBody(
            $this->generateMultiStatus(array($result))
        );

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

            throw new Sabre_DAV_Exception_NotImplemented('PUT with Content-Range is not allowed.');
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
                throw new Sabre_DAV_Exception_Forbidden('This server is not compatible with OS/X finder. Consider using a different WebDAV client or webserver.');
            }

            // The body needs to stay intact, so we copy everything to a
            // temporary stream.

            $newBody = fopen('php://temp','r+');
            fwrite($newBody,$firstByte);
            stream_copy_to_stream($body, $newBody);
            rewind($newBody);

            $body = $newBody;

        }

        // Checking If-None-Match and related headers.
        if (!$this->checkPreconditions()) return;

        if ($this->tree->nodeExists($uri)) {

            $node = $this->tree->getNodeForPath($uri);

            // If the node is a collection, we'll deny it
            if (!($node instanceof Sabre_DAV_IFile)) throw new Sabre_DAV_Exception_Conflict('PUT is not allowed on non-files.');
            if (!$this->broadcastEvent('beforeWriteContent',array($uri, $node, &$body))) return false;

            $etag = $node->put($body);

            $this->broadcastEvent('afterWriteContent',array($uri, $node));

            $this->httpResponse->setHeader('Content-Length','0');
            if ($etag) $this->httpResponse->setHeader('ETag',$etag);
            $this->httpResponse->sendStatus(204);

        } else {

            $etag = null;
            // If we got here, the resource didn't exist yet.
            if (!$this->createFile($this->getRequestUri(),$body,$etag)) {
                // For one reason or another the file was not created.
                return;
            }

            $this->httpResponse->setHeader('Content-Length','0');
            if ($etag) $this->httpResponse->setHeader('ETag', $etag);
            $this->httpResponse->sendStatus(201);

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
                throw new Sabre_DAV_Exception_UnsupportedMediaType('The request body for the MKCOL request must have an xml Content-Type');

            }

            $dom = Sabre_DAV_XMLUtil::loadDOMDocument($requestBody);
            if (Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild)!=='{DAV:}mkcol') {

                // We must throw 415 for unsupported mkcol bodies
                throw new Sabre_DAV_Exception_UnsupportedMediaType('The request body for the MKCOL request must be a {DAV:}mkcol request construct.');

            }

            $properties = array();
            foreach($dom->firstChild->childNodes as $childNode) {

                if (Sabre_DAV_XMLUtil::toClarkNotation($childNode)!=='{DAV:}set') continue;
                $properties = array_merge($properties, Sabre_DAV_XMLUtil::parseProperties($childNode, $this->propertyMap));

            }
            if (!isset($properties['{DAV:}resourcetype']))
                throw new Sabre_DAV_Exception_BadRequest('The mkcol request must include a {DAV:}resourcetype property');

            $resourceType = $properties['{DAV:}resourcetype']->getValue();
            unset($properties['{DAV:}resourcetype']);

        } else {

            $properties = array();
            $resourceType = array('{DAV:}collection');

        }

        $result = $this->createCollection($uri, $resourceType, $properties);

        if (is_array($result)) {
            $this->httpResponse->sendStatus(207);
            $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');

            $this->httpResponse->sendBody(
                $this->generateMultiStatus(array($result))
            );

        } else {
            $this->httpResponse->setHeader('Content-Length','0');
            $this->httpResponse->sendStatus(201);
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

        $moveInfo = $this->getCopyAndMoveInfo();

        // If the destination is part of the source tree, we must fail
        if ($moveInfo['destination']==$uri)
            throw new Sabre_DAV_Exception_Forbidden('Source and destination uri are identical.');

        if ($moveInfo['destinationExists']) {

            if (!$this->broadcastEvent('beforeUnbind',array($moveInfo['destination']))) return false;
            $this->tree->delete($moveInfo['destination']);
            $this->broadcastEvent('afterUnbind',array($moveInfo['destination']));

        }

        if (!$this->broadcastEvent('beforeUnbind',array($uri))) return false;
        if (!$this->broadcastEvent('beforeBind',array($moveInfo['destination']))) return false;
        $this->tree->move($uri,$moveInfo['destination']);
        $this->broadcastEvent('afterUnbind',array($uri));
        $this->broadcastEvent('afterBind',array($moveInfo['destination']));

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->setHeader('Content-Length','0');
        $this->httpResponse->sendStatus($moveInfo['destinationExists']?204:201);

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

        $copyInfo = $this->getCopyAndMoveInfo();
        // If the destination is part of the source tree, we must fail
        if ($copyInfo['destination']==$uri)
            throw new Sabre_DAV_Exception_Forbidden('Source and destination uri are identical.');

        if ($copyInfo['destinationExists']) {
            if (!$this->broadcastEvent('beforeUnbind',array($copyInfo['destination']))) return false;
            $this->tree->delete($copyInfo['destination']);

        }
        if (!$this->broadcastEvent('beforeBind',array($copyInfo['destination']))) return false;
        $this->tree->copy($uri,$copyInfo['destination']);
        $this->broadcastEvent('afterBind',array($copyInfo['destination']));

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->setHeader('Content-Length','0');
        $this->httpResponse->sendStatus($copyInfo['destinationExists']?204:201);

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
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

        $reportName = Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild);

        if ($this->broadcastEvent('report',array($reportName,$dom, $uri))) {

            // If broadcastEvent returned true, it means the report was not supported
            throw new Sabre_DAV_Exception_ReportNotSupported();

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

        $methods = array(
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
        );

        // The MKCOL is only allowed on an unmapped uri
        try {
            $this->tree->getNodeForPath($uri);
        } catch (Sabre_DAV_Exception_NotFound $e) {
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

        return $this->calculateUri($this->httpRequest->getUri());

    }

    /**
     * Calculates the uri for a request, making sure that the base uri is stripped out
     *
     * @param string $uri
     * @throws Sabre_DAV_Exception_Forbidden A permission denied exception is thrown whenever there was an attempt to supply a uri outside of the base uri
     * @return string
     */
    public function calculateUri($uri) {

        if ($uri[0]!='/' && strpos($uri,'://')) {

            $uri = parse_url($uri,PHP_URL_PATH);

        }

        $uri = str_replace('//','/',$uri);

        if (strpos($uri,$this->getBaseUri())===0) {

            return trim(Sabre_DAV_URLUtil::decodePath(substr($uri,strlen($this->getBaseUri()))),'/');

        // A special case, if the baseUri was accessed without a trailing
        // slash, we'll accept it as well.
        } elseif ($uri.'/' === $this->getBaseUri()) {

            return '';

        } else {

            throw new Sabre_DAV_Exception_Forbidden('Requested uri (' . $uri . ') is out of base uri (' . $this->getBaseUri() . ')');

        }

    }

    /**
     * Returns the HTTP depth header
     *
     * This method returns the contents of the HTTP depth request header. If the depth header was 'infinity' it will return the Sabre_DAV_Server::DEPTH_INFINITY object
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
     * If the first offset is null, the second offset should be 
