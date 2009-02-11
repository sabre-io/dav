<?php

/**
 * HTTP Request information
 *
 * This object can be used to easily access information about an HTTP request.
 * It can additionally be used to create 'mock' requests.
 *
 * @package Sabre
 * @subpackage HTTP 
 * @version $Id: BasicAuth.php 202 2009-01-19 19:38:55Z evertpot $
 * @copyright Copyright (C) 2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_Request {

    /**
     * PHP's $_SERVER data
     * 
     * @var string 
     */
    protected $_SERVER;

    /**
     * The request body, if any.
     *
     * This is stored in the form of a stream resource.
     *
     * @var resource 
     */
    protected $body = null;
    
    /**
     * Sets up the object
     *
     * The serverData array can be used to override usage of PHP's 
     * global _SERVER variable. 
     * 
     * @param array $serverData 
     */
    public function __construct($serverData = null) {

       if ($serverData) $this->_SERVER = $serverData;
       else $this->_SERVER =& $_SERVER;

    }

    /**
     * Returns the value for a specific http header.
     *
     * This method returns null if the header did not exist.
     * 
     * @param string $name 
     * @return string 
     */
    public function getHeader($name) {

        $serverName = 'HTTP_' . strtoupper(str_replace(array('-'),array('_'),$name));
        return isset($this->_SERVER[$serverName])?$this->_SERVER[$serverName]:null;

    }

    /**
     * Returns the HTTP request method
     *
     * This is for example POST or GET 
     *
     * @return string 
     */
    public function getMethod() {

        return $this->_SERVER['REQUEST_METHOD'];

    }

    /**
     * Returns the requested uri
     *
     * @return string 
     */
    public function getUri() {

        return $this->_SERVER['REQUEST_URI'];

    }

    /**
     * Returns the HTTP request body body 
     *
     * This method returns a readable stream resource.
     * If the asString parameter is set to true, a string is sent instead. 
     *
     * @param bool asString
     * @return resource 
     */
    public function getBody($asString = false) {

        if (is_null($this->body)) {
            $this->body = fopen('php://input','r');
        }
        if ($asString) {
            return stream_get_contents($this->body);
        } else {
            return $this->body;
        }

    }

    /**
     * Sets the contents of the HTTP requet body 
     * 
     * This method can either accept a string, or a readable stream resource.
     *
     * @param mixed $body 
     * @return void
     */
    public function setBody($body) {

        if(is_resource($body)) {
            $this->body = $body;
        } else {
            $stream = fopen('php://temp','r+');
            fwrite($stream,$body);
            rewind($stream);
            // String is assumed
            $this->body = $stream;
        }

    }

    /**
     * Returns a specific item from the _SERVER array. 
     *
     * Do not rely on this feature, it is for internal use only.
     *
     * @param string $field 
     * @return string 
     */
    public function getRawServerValue($field) {

        return isset($this->_SERVER[$field])?$this->_SERVER[$field]:null;

    }

}

?>
