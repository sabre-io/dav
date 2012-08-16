<?php

class Sabre_DAV_ClientMock extends Sabre_DAV_Client {

    public $response;

    public $curlSettings;
	public function __construct(array $settings) {
		$this->curlSettings=array();
		$this->curlSettings+=static::$defaultCurlSettings;
		if(isset($settings["curl"]))$this->curlSettings+=$settings["curl"];
		parent::__construct($settings);
    }
	
	protected function initCurl(&$settings){
		$this->curlSettings = static::$defaultCurlSettings;
		if (isset($settings)){
			$this->curlSettings+=$settings;
		}
	}
	
    protected function curlRequest($curlSettings) {
        $this->curlSettings += $curlSettings;
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
