<?php

namespace Sabre\DAV;

class ClientMock extends Client {

    public $response;

    public $url;
    public $curlSettings;

    protected function curlRequest($curlSettings) {

        // We're just doing this so we don't have to change the unittests that
        // much ;)
        $this->url = $curlSettings[CURLOPT_URL];

        unset($curlSettings[CURLOPT_URL]);

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
