<?php

namespace Sabre\HTTP;

class ResponseMock extends Response {

    public $headers = array();
    public $status = '';
    public $body = '';

    function setHeader($name,$value,$overwrite = true) {

        $this->headers[$name] = $value;

    }

    function sendStatus($code) {

        $this->status = $this->getStatusMessage($code);

    }

    function sendBody($body) {

        $this->body = $body;

    }

}
