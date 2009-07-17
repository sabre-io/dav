<?php

/**
 * Main DAV server class
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Server {

    /**
     * Inifinity is used for some request supporting the HTTP Depth header and indicates that the operation should traverse the entire tree
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

    const PROP_SET = 1;
    const PROP_REMOVE = 2;


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
    protected $baseUri = '/';

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
     * Class constructor 
     * 
     * @param Sabre_DAV_Tree $tree The tree object 
     * @return void
     */
    public function __construct(Sabre_DAV_Tree $tree) {

        $this->tree = $tree;
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

            $this->invoke();

        } catch (Exception $e) {

            $DOM = new DOMDocument('1.0','utf-8');
            $DOM->formatOutput = true;

            $error = $DOM->createElementNS('DAV:','d:error');
            $error->setAttribute('xmlns:s','http://www.rooftopsolutions.nl/NS/sabredav');
            $DOM->appendChild($error);

            $error->appendChild($DOM->createElement('s:exception',get_class($e)));
            $error->appendChild($DOM->createElement('s:message',$e->getMessage()));
            $error->appendChild($DOM->createElement('s:file',$e->getFile()));
            $error->appendChild($DOM->createElement('s:line',$e->getLine()));
            $error->appendChild($DOM->createElement('s:code',$e->getCode()));

            if($e instanceof Sabre_DAV_Exception) {
                $httpCode = $e->getHTTPCode();
                $e->serialize($error);
            } else {
                $httpCode = 500;
            }
            
            $this->httpResponse->sendStatus($httpCode);
            $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
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

        $this->baseUri = $uri;    

    }

    /**
     * Returns the base responding uri
     * 
     * @return string 
     */
    public function getBaseUri() {

        return $this->baseUri;

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

        $this->plugins[] = $plugin;
        $plugin->initialize($this);

    }

    /**
     * Subscribe to an event.
     *
     * When the event is triggered, we'll call all the specified callbacks.
     * 
     * @param string $event 
     * @param callback $callback 
     * @return void
     */
    public function subscribeEvent($event, $callback, $priority = 100) {

        $supportedEvents = array(
            'beforeMethod',
            'report',
            'unknownMethod',
            'unknownProperties',
        );

        if (!in_array($event,$supportedEvents)) throw new Sabre_DAV_Exception('Unknown event-type: ' . $event);

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
                if (!$result) return false;

            }

        }

        return true;

    }

    // {{{ HTTP Method implementations
    
    /**
     * HTTP OPTIONS 
     * 
     * @return void
     */
    protected function httpOptions() {

        $methods = $this->getAllowedMethods();

        // We're also checking if any of the plugins register any new methods
        foreach($this->plugins as $plugin) $methods = array_merge($methods,$plugin->getHTTPMethods());
        array_unique($methods);

        $this->httpResponse->setHeader('Allow',strtoupper(implode(' ',$methods)));
        $features = array('1','3');

        foreach($this->plugins as $plugin) $features = array_merge($features,$plugin->getFeatures());
        
        $this->httpResponse->setHeader('DAV',implode(', ',$features));
        $this->httpResponse->setHeader('MS-Author-Via','DAV');
        $this->httpResponse->setHeader('Accept-Ranges','bytes');
        $this->httpResponse->sendStatus(200);

    }

    /**
     * HTTP GET
     *
     * This method simply fetches the contents of a uri, like normal
     * 
     * @return void
     */
    protected function httpGet() {

        $node = $this->tree->getNodeForPath($this->getRequestUri(),0);

        if (!($node instanceof Sabre_DAV_IFile)) throw new Sabre_DAV_Exception_NotImplemented('GET is only implemented on File objects');
        $body = $node->get();

        // Converting string into stream, if needed.
        if (is_string($body)) {
            $stream = fopen('php://temp','r+');
            fwrite($stream,$body);
            rewind($stream);
            $body = $stream;
        }

        if (!$contentType = $node->getContentType())
            $contentType = 'application/octet-stream';

        $this->httpResponse->setHeader('Content-Type', $contentType);

        if($lastModified = $node->getLastModified())
            $this->httpResponse->setHeader('Last-Modified', date(DateTime::RFC1123, $lastModified));
       

        if ($etag = $node->getETag()) 
            $this->httpResponse->setHeader('ETag',$etag);


        $nodeSize = $node->getSize();

        // We're only going to support HTTP ranges if the backend provided a filesize
        if ($nodeSize && $range = $this->getHTTPRange()) {

            // Determining the exact byte offsets
            if (!is_null($range[0])) {

                $start = $range[0];
                $end = $range[1]?$range[1]:$nodeSize-1;
                if($start > $nodeSize) 
                    throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('The start offset (' . $range[0] . ') exceeded the size of the entity (' . $nodeSize . ')');

                if($end < $start) throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('The end offset (' . $range[1] . ') is lower than the start offset (' . $range[0] . ')');
                if($end > $nodeSize) $end = $nodeSize-1;

            } else {

                $start = $nodeSize-$range[1];
                $end  = $nodeSize-1;

                if ($start<0) $start = 0;

            }

            // New read/write stream
            $newStream = fopen('php://temp','r+');

            stream_copy_to_stream($body, $newStream, $end-$start+1, $start);
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
     * @return void
     */
    protected function httpHead() {

        $node = $this->tree->getNodeForPath($this->getRequestUri());
        if ($size = $node->getSize()) 
            $this->httpResponse->setHeader('Content-Length',$size);

        if ($etag = $node->getETag()) {

            $this->httpResponse->setHeader('ETag',$etag);

        }

        if (!$contentType = $node->getContentType())
            $contentType = 'application/octet-stream';

        $this->httpResponse->setHeader('Content-Type', $contentType);
        $this->httpResponse->setHeader('Last-Modified', date(DateTime::RFC1123, $node->getLastModified()));
        $this->httpResponse->sendStatus(200);

    }

    /**
     * HTTP Delete 
     *
     * The HTTP delete method, deletes a given uri
     *
     * @return void
     */
    protected function httpDelete() {

        // Asking for nodeinfo to make sure the node exists
        $node = $this->tree->getNodeForPath($this->getRequestUri());
        $node->delete();
        $this->httpResponse->sendStatus(204);

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
     * @return void
     */
    protected function httpPropfind() {

        // $xml = new Sabre_DAV_XMLReader(file_get_contents('php://input'));
        $properties = $this->parsePropfindRequest($this->httpRequest->getBody(true));

        $depth = $this->getHTTPDepth(1);
        // The only two options for the depth of a propfind is 0 or 1 
        if ($depth!=0) $depth = 1;

        // The requested path
        $path = $this->getRequestUri();
        
        $newProperties = $this->getPropertiesForPath($path,$properties,$depth);


        // This is a multi-status response
        $this->httpResponse->sendStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $data = $this->generatePropfindResponse($newProperties,$properties);
        $this->httpResponse->sendBody($data);

    }

    /**
     * WebDAV PROPPATCH
     *
     * This method is called to update properties on a Node. The request is an XML body with all the mutations.
     * In this XML body it is specified which properties should be set/updated and/or deleted
     *
     * @return void
     */
    protected function httpPropPatch() {

        $mutations = $this->parsePropPatchRequest($this->httpRequest->getBody(true));

        $node = $this->tree->getNodeForPath($this->getRequestUri());
        
        if ($node instanceof Sabre_DAV_IProperties) {

            $result = $node->updateProperties($mutations);

        } else {

            $result = array();
            foreach($mutations as $mutations) {
                $result[] = array($mutations[1],403);
            }

        }

        $this->httpResponse->sendStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->httpResponse->sendBody(
            $this->generatePropPatchResponse($this->getRequestUri(),$result)
        );

    }

    /**
     * HTTP PUT method 
     * 
     * This HTTP method updates a file, or creates a new one.
     *
     * If a new resource was created, a 201 Created status code should be returned. If an existing resource is updated, it's a 200 Ok
     *
     * @return void
     */
    protected function httpPut() {

        // First we'll do a check to see if the resource already exists
        try {
            $node = $this->tree->getNodeForPath($this->getRequestUri());
            
            // We got this far, this means the node already exists.
            // This also means we should check for the If-None-Match header
            if ($this->httpRequest->getHeader('If-None-Match')) {

                throw new Sabre_DAV_Exception_PreconditionFailed('The resource already exists, and an If-None-Match header was supplied');

            }
            
            // If the node is a collection, we'll deny it
            if ($node instanceof Sabre_DAV_IDirectory) throw new Sabre_DAV_Exception_Conflict('PUTs on directories are not allowed'); 
            $node->put($this->httpRequest->getBody());
            $this->httpResponse->sendStatus(200);

        } catch (Sabre_DAV_Exception_FileNotFound $e) {

            // If we got here, the resource didn't exist yet.

            // Validating the lock on the parent collection
            $parent = $this->tree->getNodeForPath(dirname($this->getRequestUri()));

            // This means the resource doesn't exist yet, and we're creating a new one
            $parent->createFile(basename($this->getRequestUri()),$this->httpRequest->getBody());
            $this->httpResponse->sendStatus(201);

        }

    }


    /**
     * WebDAV MKCOL
     *
     * The MKCOL method is used to create a new collection (directory) on the server
     *
     * @return void
     */
    protected function httpMkcol() {

        // If there's a body, we're supposed to send an HTTP 415 Unsupported Media Type exception
        $requestBody = $this->httpRequest->getBody(true);
        if ($requestBody) throw new Sabre_DAV_Exception_UnsupportedMediaType();

        // We'll check if the parent exists, and if it's a collection. If this is not the case, we need to throw a conflict exception
        
        try {
            if ($parent = $this->tree->getNodeForPath(dirname($this->getRequestUri()))) {
                if (!$parent instanceof Sabre_DAV_IDirectory) {
                    throw new Sabre_DAV_Exception_Conflict('Parent node is not a directory');
                }
            }
        } catch (Sabre_DAV_Exception_FileNotFound $e) {

            // This means the parent node doesn't exist, and we need to throw a 409 Conflict
            throw new Sabre_DAV_Exception_Conflict('Parent node does not exist');

        }

        try {
            $node = $this->tree->getNodeForPath($this->getRequestUri());

            // If we got here.. it means there's already a node on that url, and we need to throw a 405
            throw new Sabre_DAV_Exception_MethodNotAllowed('The directory you tried to create already exists');

        } catch (Sabre_DAV_Exception_FileNotFound $e) {
            // This is correct
        }
        $parent->createDirectory(basename($this->getRequestUri()));
        $this->httpResponse->sendStatus(201);

    }

    /**
     * WebDAV HTTP MOVE method
     *
     * This method moves one uri to a different uri. A lot of the actual request processing is done in getCopyMoveInfo
     * 
     * @return void
     */
    protected function httpMove() {

        $moveInfo = $this->getCopyAndMoveInfo();

        $this->tree->move($moveInfo['source'],$moveInfo['destination']);

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->sendStatus($moveInfo['destinationExists']?204:201);

    }

    /**
     * WebDAV HTTP COPY method
     *
     * This method copies one uri to a different uri, and works much like the MOVE request
     * A lot of the actual request processing is done in getCopyMoveInfo
     * 
     * @return void
     */
    protected function httpCopy() {

        $copyInfo = $this->getCopyAndMoveInfo();

        $this->tree->copy($copyInfo['source'],$copyInfo['destination']);

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->sendStatus($copyInfo['destinationExists']?204:201);

    }



    /**
     * HTTP REPORT method implementation
     *
     * Although the REPORT method is not part of the standard WebDAV spec (it's from rfc3253)
     * It's used in a lot of extensions, so it made sense to implement it into the core.
     * 
     * @return void
     */
    protected function httpReport() {

        $body = $this->httpRequest->getBody(true);
        //We'll need to change the DAV namespace declaration to something else in order to make it parsable
        $body = preg_replace("/xmlns(:[A-Za-z0-9_]*)?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$body);

        $errorsetting =  libxml_use_internal_errors(true);
        libxml_clear_errors();
        $dom = new DOMDocument();
        $dom->loadXML($body);
        $dom->preserveWhiteSpace = false;
     
        $namespaceUri = $dom->firstChild->namespaceURI;
        if ($namespaceUri=='urn:DAV') $namespaceUri = 'DAV:';

        $reportName = '{' . $namespaceUri . '}' . $dom->firstChild->localName;

        if ($this->broadcastEvent('report',array($reportName,$dom))) {

            // If broadcastEvent returned true, it means the report was not supported
            throw new Sabre_DAV_Exception_ReportNotImplemented();

        }

    }

    // }}}
    // {{{ HTTP/WebDAV protocol helpers 

    /**
     * Handles a http request, and execute a method based on its name 
     * 
     * @return void
     */
    protected function invoke() {

        $method = strtolower($this->httpRequest->getMethod()); 

        if (!$this->broadcastEvent('beforeMethod',array(strtoupper($method)))) return;

        // Make sure this is a HTTP method we support
        if (in_array($method,$this->getAllowedMethods())) {

            call_user_func(array($this,'http' . $method));

        } else {

            if ($this->broadcastEvent('unknownMethod',array(strtoupper($method)))) {
                // Unsupported method
                throw new Sabre_DAV_Exception_NotImplemented();
            }

        }

    }

    /**
     * Returns an array with all the supported HTTP methods 
     * 
     * @return array 
     */
    protected function getAllowedMethods() {

        $methods = array('options','get','head','delete','trace','propfind','mkcol','put','proppatch','copy','move','report');
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
     * @throws Sabre_DAV_Exception_PermissionDenied A permission denied exception is thrown whenever there was an attempt to supply a uri outside of the base uri
     * @return string
     */
    public function calculateUri($uri) {

        if ($uri[0]!='/' && strpos($uri,'://')) {

            $uri = parse_url($uri,PHP_URL_PATH);

        }

        $uri = str_replace('//','/',$uri);

        if (strpos($uri,$this->baseUri)===0) {

            return trim(urldecode(substr($uri,strlen($this->baseUri))),'/');

        } else {

            throw new Sabre_DAV_Exception_PermissionDenied('Requested uri (' . $uri . ') is out of base uri (' . $this->baseUri . ')');

        }

    }

    /**
     * Returns the HTTP depth header
     *
     * This method returns the contents of the HTTP depth request header. If the depth header was 'infinity' it will return the Sabre_DAV_Server::DEPTH_INFINITY object
     * It is possible to supply a default depth value, which is used when the depth header has invalid content, or is completely non-existant
     * 
     * @param mixed $default 
     * @return int 
     */
    public function getHTTPDepth($default = self::DEPTH_INFINITY) {

        // If its not set, we'll grab the default
        $depth = $this->httpRequest->getHeader('Depth');
        if (is_null($depth)) $depth = $default;

        // Infinity
        if ($depth == 'infinity') $depth = self::DEPTH_INFINITY;
        else {
            // If its an unknown value. we'll grab the default
            if ($depth!=="0" && (int)$depth==0) $depth == $default;
        }

        return $depth;

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
     * return $mixed
     */
    public function getHTTPRange() {

        $range = $this->httpRequest->getHeader('range');
        if (is_null($range)) return null; 

        // Matching "Range: bytes=1234-5678: both numbers are optional

        if (!preg_match('/^bytes=([0-9]*)-([0-9]*)$/i',$range,$matches)) return null;

        if ($matches[1]==='' && $matches[2]==='') return null;

        return array(
            $matches[1]!==''?$matches[1]:null,
            $matches[2]!==''?$matches[2]:null,
        );

    }




    /**
     * Returns information about Copy and Move requests
     * 
     * This function is created to help getting information about the source and the destination for the 
     * WebDAV MOVE and COPY HTTP request. It also validates a lot of information and throws proper exceptions 
     * 
     * The returned value is an array with the following keys:
     *   * source - Source path
     *   * destination - Destination path
     *   * destinationExists - Wether or not the destination is an existing url (and should therefore be overwritten)
     *
     * @return array 
     */
    protected function getCopyAndMoveInfo() {

        $source = $this->getRequestUri();

        // Collecting the relevant HTTP headers
        if (!$this->httpRequest->getHeader('Destination')) throw new Sabre_DAV_Exception_BadRequest('The destination header was not supplied');
        $destination = $this->calculateUri($this->httpRequest->getHeader('Destination'));
        $overwrite = $this->httpRequest->getHeader('Overwrite');
        if (!$overwrite) $overwrite = 'T';
        if (strtoupper($overwrite)=='T') $overwrite = true;
        elseif (strtoupper($overwrite)=='F') $overwrite = false;

        // We need to throw a bad request exception, if the header was invalid
        else throw new Sabre_DAV_Exception_BadRequest('The HTTP Overwrite header should be either T or F');

        // Collection information on relevant existing nodes
        $sourceNode = $this->tree->getNodeForPath($source);

        try {
            $destinationParent = $this->tree->getNodeForPath(dirname($destination));
            if (!($destinationParent instanceof Sabre_DAV_IDirectory)) throw new Sabre_DAV_Exception_UnsupportedMediaType('The destination node is not a collection');
        } catch (Sabre_DAV_Exception_FileNotFound $e) {

            // If the destination parent node is not found, we throw a 409
            throw new Sabre_DAV_Exception_Conflict('The destination node is not found');
        }

        try {

            $destinationNode = $this->tree->getNodeForPath($destination);
            
            // If this succeeded, it means the destination already exists
            // we'll need to throw precondition failed in case overwrite is false
            if (!$overwrite) throw new Sabre_DAV_Exception_PreconditionFailed('The destination node already exists, and the overwrite header is set to false');

        } catch (Sabre_DAV_Exception_FileNotFound $e) {

            // Destination didn't exist, we're all good
            $destinationNode = false;

        }

        // These are the three relevant properties we need to return
        return array(
            'source'            => $source,
            'destination'       => $destination,
            'destinationExists' => $destinationNode==true,
        );

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
     * @param array $properties 
     * @param int $depth 
     * @return array
     */
    public function getPropertiesForPath($path,$properties = array(),$depth = 0) {

        if ($depth!=0) $depth = 1;

        $returnPropertyList = array();
        
        $parentNode = $this->tree->getNodeForPath($path);
        $nodes = array(
            $path => $parentNode
        );
        if ($depth==1) {
            foreach($parentNode->getChildren() as $childNode)
                $nodes[$path . '/' . $childNode->getName()] = $childNode;
        }            

        foreach($nodes as $myPath=>$node) {

            $newProperties = array();
            if ($node instanceof Sabre_DAV_IProperties) 
                $newProperties = $node->getProperties($properties);

            $unknownProperties = array();

            // If the properties array was empty, it means 'everything' was requested.
            
            if (!$properties) {
                $properties = array(
                    '{DAV:}getlastmodified',
                    '{DAV:}getcontentlength',
                    '{DAV:}resourcetype',
                    '{DAV:}quota-used-bytes',
                    '{DAV:}quota-available-bytes',
                    '{DAV:}getetag',
                    '{DAV:}getcontenttype',
                );
            }

            // It's important we add this guy, because other systems depend on it
            if (!in_array('{DAV:}resourcetype',$properties)) $properties[] = '{DAV:}resourcetype';

            foreach($properties as $prop) {
                
                if (isset($newProperties[$prop])) continue;

                switch($prop) {
                    case '{DAV:}getlastmodified'       : if ($node->getLastModified()) $newProperties[$prop] = new Sabre_DAV_Property_GetLastModified($node->getLastModified()); break;
                    case '{DAV:}getcontentlength'      : if ($node instanceof Sabre_DAV_IFile) $newProperties[$prop] = (int)$node->getSize(); break;
                    case '{DAV:}resourcetype'          : $newProperties[$prop] = new Sabre_DAV_Property_ResourceType($node instanceof Sabre_DAV_IDirectory?self::NODE_DIRECTORY:self::NODE_FILE); break;
                    case '{DAV:}quota-used-bytes'      : 
                        if ($node instanceof Sabre_DAV_IQuota) {
                            $quotaInfo = $node->getQuotaInfo();
                            $newProperties[$prop] = $quotaInfo[0];
                        }
                        break;
                    case '{DAV:}quota-available-bytes' : 
                        if ($node instanceof Sabre_DAV_IQuota) {
                            $quotaInfo = $node->getQuotaInfo();
                            $newProperties[$prop] = $quotaInfo[1];
                        }
                        break;
                    case '{DAV:}getetag'               : if ($node instanceof Sabre_DAV_IFile && $etag = $node->getETag())  $newProperties[$prop] = $etag; break;
                    case '{DAV:}getcontenttype'        : if ($node instanceof Sabre_DAV_IFile && $ct = $node->getContentType())  $newProperties[$prop] = $ct; break;

                }

                if (!isset($newProperties[$prop])) $unknownProperties[] = $prop;

            }

            if ($unknownProperties) {
                $this->broadcastEvent('unknownProperties',array($myPath,$unknownProperties,&$newProperties));

            }

            $newProperties['href'] = trim(substr($myPath,strlen($path)),'/'); 

            //if (!$properties || in_array('{http://www.apple.com/webdav_fs/props/}appledoubleheader',$properties)) $newProps['{http://www.apple.com/webdav_fs/props/}appledoubleheader'] = base64_encode(str_repeat(' ',82)); 
            $returnPropertyList[] = $newProperties;

        }
        
        return $returnPropertyList;

    }

    // }}} 
    // {{{ XML Readers & Writers  
    
    
    /**
     * Generates a WebDAV propfind response body based on a list of nodes 
     * 
     * @param array $list The list with nodes
     * @param array $properties The properties that should be returned
     * @return string 
     */
    public function generatePropfindResponse($list,$properties) {

        $dom = new DOMDocument('1.0','utf-8');
        //$dom->formatOutput = true;
        $multiStatus = $dom->createElementNS('DAV:','d:multistatus');
        $dom->appendChild($multiStatus);

        foreach($list as $entry) {

            $this->writeProperties($multiStatus,$this->httpRequest->getUri(),$entry, $properties);

        }

        return $dom->saveXML();

    }

    /**
     * Generates the xml for a single item in a propfind response.
     *
     * This method is called by generatePropfindResponse
     * 
     * @param XMLWriter $xw 
     * @param string $baseurl 
     * @param array $data
     * @param array $properties
     * @return void
     */
    private function writeProperties(DOMNode $multistatus,$baseurl,$data, $properties) {

        $document = $multistatus->ownerDocument;
        
        $xresponse = $document->createElementNS('DAV:','d:response');
        $multistatus->appendChild($xresponse); 

        /* Figuring out the url */
        // Base url : /services/dav/mydirectory
        $url = rtrim(urldecode($baseurl),'/');

        // Adding the node in the directory
        if (isset($data['href']) && trim($data['href'],'/')) $url.= '/' . trim((isset($data['href'])?$data['href']:''),'/');

        $url = explode('/',$url);

        foreach($url as $k=>$item) $url[$k] = rawurlencode($item);

        $url = implode('/',$url);

        if ($data['{DAV:}resourcetype']->getValue()=='{DAV:}collection') $url .='/';

        $xresponse->appendChild($document->createElementNS('DAV:','d:href',$url));
        
        $xpropstat = $document->createElementNS('DAV:','d:propstat');
        $xresponse->appendChild($xpropstat);

        $xprop = $document->createElementNS('DAV:','d:prop');
        $xpropstat->appendChild($xprop);

        // We have to collect the properties we don't know
        $notFound = array();

        if (!$properties) $properties = array_keys($data);

        $nsList = array(
            'DAV:' => 'd',
        );

        foreach($properties as $property) {

            // We can skip href
            if ($property=='href') continue;

            if(!isset($data[$property])) {
                $notFound[] = $property;
                continue;
            }

            $value = $data[$property];

            $propName = null;
            preg_match('/^{([^}]*)}(.*)$/',$property,$propName);
        
            // special case for empty namespaces
            if ($propName[1]=='') {

                $currentProperty = $document->createElement($propName[2]);
                $xprop->appendChild($currentProperty);
                $currentProperty->setAttribute('xmlns','');

            } else {

                if (!isset($nsList[$propName[1]])) {
                    $nsList[$propName[1]] = 'x' . count($nsList);
                }
                $currentProperty = $document->createElementNS($propName[1],$nsList[$propName[1]].':' . $propName[2]);
                $xprop->appendChild($currentProperty);

            }

            if (is_scalar($value)) {
                $currentProperty->nodeValue = $value;
            } elseif ($value instanceof Sabre_DAV_Property) {
                $value->serialize($currentProperty);
            } else {
                throw new Sabre_DAV_Exception('Unknown property value type: ' . gettype($value) . ' for property: ' . $property);
            }

        }

        $xpropstat->appendChild($document->createElementNS('DAV:','d:status',$this->httpResponse->getStatusMessage(200)));

        if ($notFound) {

            $xpropstat = $document->createElementNS('DAV:','d:propstat');
            $xresponse->appendChild($xpropstat);

            $xprop = $document->createElementNS('DAV:','d:prop');
            $xpropstat->appendChild($xprop);

            foreach($notFound as $property) {

                $tag = null;
                preg_match('/^{([^}]*)}(.*)$/',$property,$propName);
                if (!isset($nsList[$propName[1]])) {

                    $nsList[$propName[1]] = 'x' . count($nsList);

                }
                $currentProperty = $document->createElementNS($propName[1],$nsList[$propName[1]].':' . $propName[2]);

                $xprop->appendChild($currentProperty);

            }
            $xpropstat->appendChild($document->createElementNS('DAV:','d:status',$this->httpResponse->getStatusMessage(404)));

        }
    }


    /**
     * This method parses a PropPatch request 
     * 
     * @param string $body xml body
     * @return array list of properties in need of updating or deletion
     */
    protected function parsePropPatchRequest($body) {

        //We'll need to change the DAV namespace declaration to something else in order to make it parsable
        $body = preg_replace("/xmlns(:[A-Za-z0-9_]*)?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$body);

        $errorsetting =  libxml_use_internal_errors(true);
        libxml_clear_errors();
        $dom = new DOMDocument();
        $dom->loadXML($body,LIBXML_NOWARNING | LIBXML_NOERROR);
        $dom->preserveWhiteSpace = false;

        
        if ($error = libxml_get_last_error()) {
            switch ($error->code) {
                // Error 100 is a non-absolute namespace, which WebDAV allows
                case 100 :
                    break;
                default :    
                    throw new Sabre_DAV_Exception_BadRequest('The request body was not a valid proppatch request: ' . print_r($error,true));

            }
        }
        
        $operations = array();

        foreach($dom->firstChild->childNodes as $child) {

            if ($child->namespaceURI != 'urn:DAV' || ($child->localName != 'set' && $child->localName !='remove')) continue; 
            
            $propList = $this->parseProps($child);
            foreach($propList as $k=>$propItem) {

                $operations[] = array($child->localName=='set'?self::PROP_SET:self::PROP_REMOVE,$k,$propItem);

            }

        }

        return $operations;

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
        if (!$body) return array();

        $errorsetting =  libxml_use_internal_errors(true);
        libxml_clear_errors();
        $dom = new DOMDocument();
        $body = preg_replace("/xmlns(:[A-Za-z0-9_]*)?=(\"|\')DAV:(\\2)/","xmlns\\1=\"urn:DAV\"",$body);
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($body,LIBXML_NOERROR);
        if($error = libxml_get_last_error()) {
            switch($error->code) {
                // Error 100 is a non-absolute namespace, which WebDAV allows
                case 100 :
                    break;
                default :
                    throw new Sabre_DAV_Exception_BadRequest('The request body was not a valid propfind request' . print_r($error,true));
            }
        }
        libxml_use_internal_errors($errorsetting); 
        $elem = $dom->getElementsByTagNameNS('urn:DAV','propfind')->item(0);
        return array_keys($this->parseProps($elem)); 

    }

    /**
     * Part of parsePropFindRequest 
     * 
     * @param DOMNode $prop 
     * @return array 
     */
    protected function parseProps(DOMNode $prop) {

        $propList = array(); 
        foreach($prop->childNodes as $propNode) {

            if ($propNode->namespaceURI == 'urn:DAV' && $propNode->localName == 'prop') {

                foreach($propNode->childNodes as $propNodeData) {

                    /* If there are no elements in here, we actually get 1 text node, this special case is dedicated to netdrive */
                    if ($propNodeData->nodeType != XML_ELEMENT_NODE) continue;

                    if ($propNodeData->namespaceURI=='urn:DAV') $ns = 'DAV:'; else $ns = $propNodeData->namespaceURI;
                    $propList['{' . $ns . '}' . $propNodeData->localName] = $propNodeData->textContent;
                }

            }

        }
        return $propList; 

    }

    /**
     * Generates the response for a succesful PROPPATCH request 
     * 
     * @param string $href 
     * @param array $mutations 
     * @return string
     */
    protected function generatePropPatchResponse($href,$mutations) {

        $xw = new XMLWriter();
        $xw->openMemory();
        $xw->setIndent(true);
        $xw->startDocument('1.0','utf-8');
        $xw->startElementNS('d','multistatus','DAV:');
            $xw->startElement('d:response');
                $xw->writeElement('d:href',$href);
                foreach($mutations as $mutation) {

                    $xw->startElement('d:propstat');
                        $xw->startElement('d:prop');
                            $matches = null;
                            preg_match('/^{([^}]*)}(.*)$/',$mutation[0],$matches);
                            $xw->writeElementNS('X',$matches[2],$matches[1],null);
                        $xw->endElement(); // d:prop
                        $xw->writeElement('d:status',$this->httpResponse->getStatusMessage($mutation[1]));
                    $xw->endElement(); // d:propstat

                }
            $xw->endElement(); // d:response
        $xw->endElement(); // d:multistatus
        return $xw->outputMemory();

    }

    // }}}

}

