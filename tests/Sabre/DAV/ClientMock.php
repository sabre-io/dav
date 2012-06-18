<?php

namespace Sabre\DAV;

class ClientMock extends Client {

    public $response;

    public $url;
    public $curlSettings;

    protected function curlRequest($url, $curlSettings) {

        $this->url = $url;
        $this->curlSettings = $curlSettings;
        return $this->response;

    }

    /**
     * Just making this method public
     *
     * @param string $url
     * @return string
     */
    public function getAbsoluteUrl($url) {

        return parent::getAbsoluteUrl($url);

    }

}
