<?php

class Sabre_DAV_PartialUpdate_FileMock implements Sabre_DAV_PartialUpdate_IFile {

    protected $data = '';

    function put($str) {

        if (is_resource($str)) {
            $str = stream_get_contents($str);
        }
        $this->data = $str;

    }

    function putRange($str,$start) {

        if (is_resource($str)) {
            $str = stream_get_contents($str);
        }
        $this->data = substr($this->data, 0, $start) . $str . substr($this->data, $start + strlen($str));



    }

    function get() {

        return $this->data;

    }

    function getContentType() {

        return 'text/plain';

    }

    function getSize() {

        return strlen($this->data);

    }

    function getETag() {

        return '"' . $this->data . '"';

    }

    function delete() {

        throw new Sabre_DAV_Exception_MethodNotAllowed();

    }

    function setName($name) {

        throw new Sabre_DAV_Exception_MethodNotAllowed();

    }

    function getName() {

        return 'partial';

    }

    function getLastModified() {

        return null;

    }


}
