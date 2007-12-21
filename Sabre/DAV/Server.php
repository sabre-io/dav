<?php
   
    require_once 'Sabre/DAV/Exception.php';
    require_once 'Sabre/DAV/XMLReader.php';

    /**
     * Main DAV server class
     * 
     * @package Sabre
     * @subpackage DAV
     * @version $Id$
     * @copyright Copyright (C) 2007 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/) 
     * @license licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     */
    class Sabre_DAV_Server {

        const DEPTH_INFINITY = -1;

        /**
         * root 
         * 
         * @var mixed
         */
        private $root;

        /**
         * allowDirectoryOverwrite 
         * 
         * @var mixed
         */
        private $allowDirectoryOverwrite = false;

        /**
         * The centralized property manager (if any) 
         * 
         * @var Sabre_DAV_PropertyManager 
         */
        private $propertyManager;

        function __construct(Sabre_DAV_IDirectory $root) {

            $this->root = $root;

        }

        /**
         * Starts the DAV Server 
         *
         * @return void
         */
        function exec() {

            try {

                $this->invoke(strtolower($_SERVER['REQUEST_METHOD']));

            } catch (Sabre_DAV_Exception $e) {

                $this->sendHTTPStatus($e->getHTTPCode());

                //outputting a space to make sure output-buffers end
                throw $e;

            } catch (Exception $e) {

                $this->sendHTTPStatus(500);
                throw $e;

            }

        }

        public function setAllowDirectoryOverwrite($setting) {

            $this->allowDirectoryOverwrite = $setting;

        }

        public function setPropertyManager(Sabre_DAV_PropertyManager $propertyManager) {

            $this->propertyManager = $propertyManager;

        }

        public function getFileObject($path) {

            $pathStr = $path;
            $path = trim($path,'/');

            $current = $this->root;

            if ($path && $path!='.') { 
                $path = explode('/',trim($path));

                foreach($path as $part) {

                    $current = $current->getChild($part);                

                }

            }
            
            $current->setFullpath($pathStr);
            return $current;

        }

        /**
         * Sets the base responding url 
         * 
         * @param string $url 
         * @return void
         */
        public function setBaseUrl($url) {

            $this->baseUrl = $url;    

        } 

        /**
         * Copies a file from 1 to another location 
         * 
         * @param Sabre_DAV_IFile $source Source-file object 
         * @param Sabre_DAV_IFile $destDirectory Destination container
         * @param mixed $destName Destination name
         * @param mixed $depth How 'deep' the copy should go. 
         * @return void
         */
        public function copyFile(Sabre_DAV_IFile $source,Sabre_DAV_IFile $destDirectory,$destName,$depth = null) {

            if (is_null($depth)) $depth = self::DEPTH_INFINITY; //self::DEPTH_INFINITY;

            // If its a directory.. we'll be traversing the tree :(
            if ($source instanceof Sabre_DAV_IDirectory) {

                // create target directory
                $destDirectory->createDirectory($destName);

                // If we exceeded depth we don't have to copy further
                if ($depth == self::DEPTH_INFINITY || $depth>0) {

                    // Connect to newly created directory
                    $newDir = $destDirectory->getChild($destName);

                    // Looping through children and copy recursively
                    foreach($source->getChildren() as $child) {
                   
                        // Subtract 1 from the depth, unless it was infinite
                        $this->copyFile($child,$newDir,$child->getName(),($depth!=self::DEPTH_INFINITY?$depth-1:$depth));

                    }

                }

            } else {

                ob_start();
                $source->get();
                $destDirectory->createFile($destName,ob_get_clean());

            }
            if ($this->propertyManager) {

                $this->propertyManager->storeProperties($destDirectory->getFullPath() . '/' . $destName,$this->propertyManager->getProperties($source->getFullPath()),true);

            }

        }

        // HTTP Method implementations {{{
        
        /**
         * HTTP OPTIONS 
         * 
         * @return void
         */
        protected function options() {

            $this->addHeader('Allows',strtoupper(implode(' ',$this->getAllowedMethods())));
            $this->addHeader('DAV','1');

        }

        /**
         * HTTP PROPFIND 
         * 
         * @return void
         */
        protected function propfind() {

            $xml = new Sabre_DAV_XMLReader(file_get_contents('php://input'));
            $properties = $xml->parsePropfindRequest();
           
            $depth = isset($_SERVER['HTTP_DEPTH'])?$_SERVER['HTTP_DEPTH']:0;
            if ($depth!=0) $depth = 1;

            // The requested path
            $path = $this->getRequestUri();

            // The file object
            $fileObject = $this->getFileObject($path);

            $props = array(
                'name'         => '',
                'type'         => $fileObject instanceof Sabre_DAV_IDirectory?1:0,
                'lastmodified' => $fileObject->getLastModified(),
                'size'         => $fileObject->getSize(),
            );

            if ($this->propertyManager) {
                $newProps = $this->propertyManager->getProperties($path);
                if ($newProps) $props = array_merge($props,$newProps);
            }

            $fileList[] = $props;

            // If the depth was 1, we'll also want the files in the directory
            if ($depth==1 && $fileObject instanceof Sabre_DAV_IDirectory) {

                foreach($fileObject->getChildren() as $child) {
                    $props= array(
                        'name'         => $child->getName(), 
                        'type'         => $child instanceof Sabre_DAV_IDirectory?1:0,
                        'lastmodified' => $child->getLastModified(),
                        'size'         => $child->getSize(),
                    );
                    if ($this->propertyManager) {
                        $props = array_merge($props,$this->propertyManager->getProperties($path . '/' . $child->getName()));
                    }

                    $fileList[] = $props;
                }
                
            }
           
            // This is a multi-status response
            $this->sendHTTPStatus(207);
            $data = $this->generatePropfindResponse($fileList,$properties);
            echo $data;

        }

        /**
         * HTTP delete 
         * 
         * @return void
         */
        protected function delete() {

            $fileObject = $this->getFileObject($this->getRequestUri());
            $fileObject->delete();

        }

        /**
         * HTTP PUT
         *
         * @return void
         */
        protected function put() {

            $requestUri = $this->getRequestUri();
            // We'll catch FileNotFound exceptions, because that means its a new file we're creating
            try {

                $fileObject = $this->getFileObject($requestUri);
                $fileObject->put(file_get_contents('php://input'));

            } catch (Sabre_DAV_FileNotFoundException $e) {

                $parent = dirname($requestUri);
                
                $fileObject = $this->getFileObject($parent);
                $fileObject->createFile(basename($requestUri),file_get_contents('php://input'));

                // 201 = created
                $this->sendHTTPStatus(201);

            }

        }

        /**
         * HTTP GET
         * 
         * @return void
         */
        protected function get() {

            $fileObject = $this->getFileObject($this->getRequestUri());
            $fileObject->get();

        }


        /**
         * HTTP HEAD 
         * 
         * @return void
         */
        protected function head() {

            $fileObject = $this->getFileObject($this->getRequestUri());
            //$fileObject->get();

        }

        /**
         * HTTP MKCOL 
         *
         * @return void
         */
        protected function mkcol() {

            $requestUri = $this->getRequestUri();

            // If there's a body, we'll make it fail
            $requestBody = file_get_contents('php://input');
            
            if ($requestBody) throw new Sabre_DAV_UnsupportedMediaTypeException();

            try {
                $fileObject = $this->getFileObject(dirname($requestUri));

                // If the directory was not found, we're actually supposed to throw 409 Conflict
            } catch (Sabre_DAV_FileNotFoundException $e) {

                throw new Sabre_DAV_ConflictException($e->getMessage());

            }

            // Now we'll check if the file already exists
            try {
                $child = $fileObject->getChild(basename($requestUri));

                // We got so far.. so it already existed. Now for an appropriate error
                if ($child instanceof Sabre_DAV_IDirectory) 

                    // 405 for directories
                    throw new Sabre_DAV_MethodNotAllowedException('Directory already exists');
                else 
                    // 409 for files
                    throw new Sabre_DAV_ConflictException();
                
            } catch (Sabre_DAV_FileNotFoundException $e) {

                // this exception is actually good news
                $fileObject->createDirectory(basename($requestUri));

            }

        }

        protected function copy() {
            
            $fileObject = $this->getFileObject($this->getRequestUri());

            // The HTTP Destination header is a full url with the destination for this copy
            if(!isset($_SERVER['HTTP_DESTINATION'])) throw new Sabre_DAV_BadRequestException();
            $destination = $this->calculateUri($_SERVER['HTTP_DESTINATION']);

            // Now we'll check if the destination already exists
            
            try {

                $destObject = $this->getFileObject($destination);

                // The destination exists, we'll see if we are allowed to overwrite

                if (isset($_SERVER['HTTP_OVERWRITE']) && $_SERVER['HTTP_OVERWRITE']=='T') {

                    // We're clear for overwriting the file. 
                    // If it is a directory, we're going to make it fail anyway to prevent people from shooting themselves in the foot

                    if ($destObject instanceof Sabre_DAV_IDirectory && !$this->allowDirectoryOverwrite) throw new Sabre_DAV_PermissionDeniedException('This webdav server does not allow overwriting existing diretories');

                    $destObject->delete();
                    $destObject = $this->getFileObject(dirname($destination));

                    $this->copyFile($fileObject,$destObject,basename($destination),$this->getDepth());

                    // Sending 204: No Content
                    $this->sendHTTPStatus(204);

                } else {

                    // We are not allowed to overwrite
                    throw new Sabre_DAV_PreconditionFailedException('The destination location already exists');

                }

            } catch (Sabre_DAV_FileNotFoundException $e) {

                // The file didn't exist, so we're all clear for the copy
                
                try { 

                    $destObject = $this->getFileObject(dirname($destination));

                } catch (Sabre_DAV_FileNotFoundException $e) {

                    // The parent was not found, we'll send out a 409 Conflict
                    throw new Sabre_DAV_ConflictException($e->getMessage());

                }

                if (!$destObject instanceof Sabre_DAV_IDirectory) throw new Sabre_DAV_ConflictException('Not a directory!');

                $this->copyFile($fileObject,$destObject,basename($destination),$this->getDepth());

                // Sending a 201 Created
                $this->sendHTTPStatus(201);

            }

        }

        protected function move() {
           
            // TODO: Almost direct copy of the copy method.. perhaps this can be refactored
            
            $fileObject = $this->getFileObject($this->getRequestUri());

            // The HTTP Destination header is a full url with the destination for this copy
            if(!isset($_SERVER['HTTP_DESTINATION'])) throw new Sabre_DAV_BadRequestException();
            $destination = $this->calculateUri($_SERVER['HTTP_DESTINATION']);

            // Now we'll check if the destination already exists
            
            try {

                $destObject = $this->getFileObject($destination);

                // The destination exists, we'll see if we are allowed to overwrite

                if (isset($_SERVER['HTTP_OVERWRITE']) && $_SERVER['HTTP_OVERWRITE']=='T') {

                    // We're clear for overwriting the file. 
                    // If it is a directory, we're going to make it fail anyway to prevent people from shooting themselves in the foot

                    if ($destObject instanceof Sabre_DAV_IDirectory && !$this->allowDirectoryOverwrite) throw new Sabre_DAV_PermissionDeniedException('This webdav server does not allow overwriting existing diretories');

                    $destObject->delete();
                    $destObject = $this->getFileObject(dirname($destination));

                    $this->copyFile($fileObject,$destObject,basename($destination));

                    if ($this->propertyManager) {

                        $this->propertyManager->deleteProperties($destination,array());

                    }

                    $fileObject->delete();

                    // Sending 204: No Content
                    $this->sendHTTPStatus(204);

                } else {

                    // We are not allowed to overwrite
                    throw new Sabre_DAV_PreconditionFailedException('The destination location already exists');

                }

            } catch (Sabre_DAV_FileNotFoundException $e) {

                // The file didn't exist, so we're all clear for the copy
                
                try { 

                    $destObject = $this->getFileObject(dirname($destination));

                } catch (Sabre_DAV_FileNotFoundException $e) {

                    // The parent was not found, we'll send out a 409 Conflict
                    throw new Sabre_DAV_ConflictException($e->getMessage());

                }

                if (!$destObject instanceof Sabre_DAV_IDirectory) throw new Sabre_DAV_ConflictException('Not a directory!');

                $this->copyFile($fileObject,$destObject,basename($destination));
                $fileObject->delete();
                if ($this->propertyManager) {

                    $this->propertyManager->deleteProperties($destination,array());

                }

                // Sending a 201 Created
                $this->sendHTTPStatus(201);

            }

        }

        function proppatch() {

            $uri = $this->getRequestUri();

            // This will give us a 404 in the case the url doesn't exist
            $fileObject = $this->getFileObject($uri);

            // Getting the modifications. This array contains a 'set' property with property updates, 
            // and a 'remove' property with properties that should be removed
            $xml = new Sabre_DAV_XMLReader(file_get_contents('php://input'));
            $changes = $xml->parsePropPatchRequest();

            if ($this->propertyManager) {

                if ($changes['set'])    $this->propertyManager->storeProperties($uri,$changes['set']);
                if ($changes['remove']) $this->propertyManager->deleteProperties($uri,$changes['remove']);

            }

        }

        // }}}
        // {{{ HTTP HELPERS
        
        protected function invoke($method) {

            // Make sure this is a HTTP method we support
            if (in_array($method,$this->getAllowedMethods())) {

                $this->$method();

            } else {

                // Unsupported method
                throw new Sabre_DAV_MethodNotImplementedException();

            }

        }

        protected function getAllowedMethods() {

            return array('options','get','head','post','delete','trace','propfind','copy','mkcol','put','move','proppatch');

        }

        function getHTTPStatus($code) {
            
            $msg = array(
                200 => 'Ok',
                201 => 'Created',
                204 => 'No Content',
                207 => 'Multi-Status',
                400 => 'Bad request',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method not allowed',
                409 => 'Conflict',
                412 => 'Precondition failed',
                415 => 'Unsupported Media Type',
                500 => 'Internal Server Error',
                501 => 'Method not implemented',
           ); 

            return 'HTTP/1.1 ' . $code . ' ' . $msg[$code];

        }

        function sendHTTPStatus($code) {

            header($this->getHTTPStatus($code));

        }

        function getRequestUri() {

            return $this->calculateUri($_SERVER['REQUEST_URI']);

        }

        function calculateUri($uri) {

            if ($uri[0]!='/' && strpos($uri,'://')) {

                $uri = parse_url($uri,PHP_URL_PATH);

            }

            if (strpos($uri,$this->baseUrl)===0) {

                return trim(urldecode(substr($uri,strlen($this->baseUrl))),'/');

            } else {

                throw new Sabre_DAV_PermissionDeniedException('Requested uri (' . $uri . ') is out of base uri (' . $this->baseUrl . ')');

            }

        }

        function addHeader($name,$value) {

            header($name . ': ' . str_replace(array("\n","\r"),array('\n','\r'),$value));

        }

        function getDepth($default = self::DEPTH_INFINITY) {

            // If its not set, we'll grab the default
            $depth = isset($_SERVER['HTTP_DEPTH'])?$_SERVER['HTTP_DEPTH']:$default;

            // Infinity
            if ($depth == 'infinity') $depth = self::DEPTH_INFINITY;
            else {
                // If its an unknown value. we'll grab the default
                if ($depth!=="0" && (int)$depth==0) $depth == $default;
            }

            return $depth;

        }

        /// }}}
        // {{{ XML HTTP Request READER/WRITERS
        
        /**
         * generatePropfindResponse 
         * 
         * @param mixed $list 
         * @return void
         */
        private function generatePropfindResponse($list,$properties) {

            $xw = new XMLWriter();
            $xw->openMemory();
            $xw->setIndent(true);
            $xw->startDocument('1.0','UTF-8');
            $xw->startElementNS('d','multistatus','DAV:');

            foreach($list as $entry) {

                $this->writeProperty($xw,$_SERVER['REQUEST_URI'],$entry,$properties);

            }

            $xw->endElement();
            return $xw->outputMemory();

        }

        /**
         * writeProperty 
         * 
         * @param mixed $xw 
         * @param mixed $baseurl 
         * @param mixed $data 
         * @return void
         */
        private function writeProperty($xw,$baseurl,$data,$properties) {

            $xw->startElement('d:response');
            $xw->startElement('d:href');

            // Base url : /services/dav/mydirectory
            $url = rtrim(urldecode($baseurl),'/');

            // Adding the node in the directory
            if (isset($data['name']) && trim($data['name'],'/')) $url.= '/' . trim((isset($data['name'])?$data['name']:''),'/');

            $url = explode('/',$url);

            foreach($url as $k=>$item) $url[$k] = rawurlencode($item);

            $url = implode('/',$url);

            // Adding the protocol and hostname. We'll also append a slash if this is a collection
            $xw->text('http://' . $_SERVER['HTTP_HOST'] . $url . ($data['type']==1&&$url?'/':''));
            $xw->endElement(); //d:href

            $xw->startElement('d:propstat');
            $xw->startElement('d:prop');

            $notFoundProps = array();

            foreach($properties as $prop) {

                switch($prop) {

                    case 'urn:DAV#getlastmodified' :

                        $xw->startElement('d:getlastmodified');
                        $xw->writeAttribute('xmlns:b','urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/');
                        $xw->writeAttribute('b:dt','dateTime.rfc1123');
                        $modified = isset($data['modified'])?$data['modified']:time();
                        if (!(int)$modified) $modified = strtotime($modified);
                        $xw->text(date(DATE_RFC1123,$modified));
                        $xw->endElement();
                        break;

                    case 'urn:DAV#getcontentlength' :    
                        $xw->startElement('d:getcontentlength');
                        $xw->text('0');
                        $xw->endElement();
                        break;
                    
                    case 'urn:DAV#resourcetype' :
                        $xw->startElement('d:resourcetype');
                        if (isset($data['type'])&&$data['type']==1) $xw->writeElement('d:collection','');
                        $xw->endElement();
                        break;

                    default : 
                        if (isset($data[$prop])) {
                            $propParts = explode('#',$prop,2);
                            $xw->startElement($propParts[1]);
                            $xw->writeAttribute('xmlns',$propParts[0]);
                            $xw->text($data[$prop]);
                            $xw->endElement();
                        } else {
                            $notFoundProps[] = $prop;
                        }
                        break;

                }

            }

            $xw->endElement(); // d:prop
           
            $xw->writeElement('d:status',$this->getHTTPStatus(200));
           
            $xw->endElement(); // :d:propstat

            
            if ($notFoundProps) {

                $xw->startElement('d:propstat');
                $xw->startElement('d:prop');
                foreach($notFoundProps as $prop) {

                    list($ns,$node) = explode('#',$prop,2);
                    $xw->startElement('nf:' . $node);
                    $xw->writeAttribute('xmlns:nf',$ns);
                    $xw->endElement();

                }
                $xw->endElement(); // d:prop
                $xw->writeElement('d:status',$this->getHTTPStatus(404));
                $xw->endElement(); // propstat

            }
            $xw->endElement(); // d:response
        }


        // }}}

    }

?>
