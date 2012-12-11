<?php

namespace Sabre\DAV;

class ClientMock extends Client {

    public $response;

    public $curlSettings;
	public function __construct(array $settings) {
		$this->curlSettings=array();
		$this->curlSettings+=static::$defaultCurlSettings;
		if(isset($settings["curl"]))$this->curlSettings+=$settings["curl"];
		parent::__construct($settings);
    }
	
	protected function initCurl(&$settings=null){
		$this->curlSettings = static::$defaultCurlSettings;
		if (isset($settings)&&is_array($settings)){
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
