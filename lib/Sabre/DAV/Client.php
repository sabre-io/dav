<?php

/**
 * SabreDAV DAV client
 *
 * This client wraps around Curl to provide a convenient API to a WebDAV 
 * server.
 * 
 * @package Sabre
 * @subpackage DAVClient
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Client {

    protected $baseUri;
    protected $userName;
    protected $password;
    protected $proxy;

    protected $propertyMap = array();

    /**
     * Constructor
     *
     * Settings are provided through the 'settings' argument. The following 
     * settings are supported:
     *
     *   * baseUri
     *   * userName (optional)
     *   * password (optional)
     *   * proxy (optional)
     * 
     * @param array $settings 
     */
    public function __construct(array $settings) {

        if (!isset($settings['baseUri'])) {
            throw new InvalidArgumentException('A baseUri must be provided');
        }
        
        $validSettings = array(
            'baseUri',
            'userName',
            'password',
            'proxy'
        );

        foreach($validSettings as $validSetting) {
            if (isset($settings[$validSetting])) {
                $this->$validSetting = $settings[$validSetting];
            }
        }

        $this->propertyMap['{DAV:}resourceType'] = 'Sabre_DAV_Property_ResourceType';

    }

    /**
     * Does a PROPFIND request
     *
     * The list of requested properties must be specified as an array, in clark 
     * notation. 
     *
     * The returned array will contain a list of filenames as keys, and 
     * properties as values.
     *
     * The properties array will contain the list of properties. Only properties 
     * that are actually returned from the server (without error) will be 
     * returned, anything else is discarded.
     *
     * Depth should be either 0 or 1. A depth of 1 will cause a request to be 
     * made to the server to also return all child resources.
     *
     * @param string $url 
     * @param array $properties 
     * @param int $depth 
     * @return array 
     */
    public function propFind($url, array $properties, $depth = 0) {

        $body = '<?xml version="1.0"?>' . "\n";
        $body.= '<d:propfind xmlns:d="DAV:">' . "\n";
        $body.= '  <d:prop>' . "\n";

        foreach($properties as $property) {

            list(
                $namespace,
                $elementName
            ) = Sabre_DAV_XMLUtil::parseClarkNotation($property);

            if ($namespace === 'DAV:') {
                $body.='    <d:' . $elementName . ' />' . "\n";
            } else {
                $body.="    <x:" . $elementName . " xmlns:x=\"" . $namespace . "\"/>\n";
            }

        }

        $body.= '  </d:prop>' . "\n";
        $body.= '</d:propfind>';

        $response = $this->request('PROPFIND', $url, $body, array(
            'Depth' => $depth,
            'Content-Type' => 'application/xml'
        ));

        $result = $this->parseMultiStatus($response['body']);

        // Pulling out only the found properties
        $newResult = array();
        foreach($result as $href => $statusList) {

            $newResult[$href] = $statusList[200];

        }

        return $newResult;

    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * If the specified url is relative, it will be expanded based on the base 
     * url.
     *
     * @param string $method 
     * @param string $url 
     * @param string $body 
     * @param array $headers 
     * @return array 
     */
    protected function request($method, $url, $body = null, $headers = array()) {

        $url = $this->getAbsoluteUrl($url);

        $curlSettings = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body
        );

        // Adding HTTP headers
        $nHeaders = array(); 
        foreach($headers as $key=>$value) {

            $nHeaders[] = $key . ': ' . $value;

        }
        $curlSettings[CURLOPT_HTTPHEADER] = $nHeaders;

        if ($this->proxy) {
            $curlSettings[CURLOPT_PROXY] = $this->proxy;
        }

        if ($this->username) {
            $curlSettings[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_DIGEST;
            $curlSettings[CURLOPT_USERPWD] = $this->username . ':' . $this->password;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlSettings);

        $responseBody = curl_exec($curl);

        $curlInfo = curl_getinfo($curl);
        $response = array(
            'body' => $responseBody,
            'statusCode' => $curlInfo['http_code']
        );

        if (curl_errno($curl)) {
            throw new Sabre_DAV_Exception('[CURL] Error while making request: ' . curl_error($curl) . ' (error code: ' . curl_errno($curl) . ')');
        } 

        if ($response['statuscode']>=400) {
            throw new Sabre_DAV_Exception('HTTP error response. (errorcode ' . $response['statusCode'] . ')');
        }

        return $response;

    }

    /**
     * Returns the full url based on the given url (which may be relative). All 
     * urls are expanded based on the base url as given by the server. 
     * 
     * @param string $url 
     * @return string 
     */
    protected function getAbsoluteUrl($url) {

        // If the url starts with http:// or https://, the url is already absolute. 
        if (preg_match('/^http(s?):\/\//', $url)) {
            return $url;
        }

        // If the url starts with a slash, we must calculate the url based off 
        // the root of the base url.
        if (strpos($url,'/') === 0) {
            $parts = parse_url($this->baseUrl);
            return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port'])?':' . $parts['port']:'') . $url;
        }

        // Otherwise...
        return $this->baseUrl . $url;

    }

    /**
     * Parses a WebDAV multistatus response body
     * 
     * @param string $body 
     * @return array 
     */
    protected function parseMultiStatus($body) {

        $responseXML = simplexml_load_string($body, null, LIBXML_NOBLANKS | LIBXML_NOCDATA);

        if (!$responseXML) {
            throw new InvalidArgumentException('The passed data is not valid XML');
        }
         
        $responseXML->registerXPathNamespace('d','DAV:');

        $propResult = array();

        foreach($responseXML->xpath('d:response') as $response) {

            $response->registerXPathNamespace('d','DAV:');
            $href = $response->xpath('d:href');
            $href = (string)$href[0];

            $properties = array();

            foreach($response->xpath('d:propstat') as $propStat) {

                $propStat->registerXPathNamespace('d','DAV:');
                $status = $propStat->xpath('d:status');
                list($httpVersion, $statusCode, $message) = explode(' ', (string)$status[0],3);

                $properties[$statusCode] = Sabre_DAV_XMLUtil::parseProperties(dom_import_simplexml($propStat), $this->propertyMap); 

            }

            $propResult[$href] = $properties;

        }

        return $propResult;

    }

}
