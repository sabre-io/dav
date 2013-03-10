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
    
    
    /*
     * Used to set opts to "cURL "
     * @param integer $opt curl constant for option
     * @param mixed $val value
     * @returns true
     */
    protected function curlSetOpt($opt,$val){
        $this->curlSettings[$opt]=$val;
        return true;
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
