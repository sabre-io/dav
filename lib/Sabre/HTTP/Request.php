<?php

class Sabre_HTTP_Request {

    protected $_SERVER;
    protected $body = null;
    
    public function __construct($serverData = null) {

       if ($serverData) $this->_SERVER = $serverData;
       else $this->_SERVER =& $_SERVER;

    }

    public function getHeader($name) {

        $serverName = 'HTTP_' . strtoupper(str_replace(array('-'),array('_'),$name));
        return isset($this->_SERVER[$serverName])?$this->_SERVER[$serverName]:null;

    }

    public function getMethod() {

        return $this->_SERVER['REQUEST_METHOD'];

    }

    public function getUri() {

        return $this->_SERVER['REQUEST_URI'];

    }

    public function getBody() {

        if (is_null($this->body)) {
            $this->body = file_get_contents('php://input');
        } else {
            return $this->body;
        }

    }

    public function setBody($body) {

        $this->body = $body;

    }

    public function getRawServerValue($field) {

        return isset($this->_SERVER[$field])?$this->_SERVER[$field]:null;

    }

}

?>
