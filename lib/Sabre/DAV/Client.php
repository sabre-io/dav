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

    public $propertyMap = array();

    protected $baseUri;
    protected $userName;
    protected $password;
    protected $proxy;

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

        // If depth was 0, we only return the top item
        if ($depth===0) {
            reset($result);
            $result = current($result);
            return $result[200];
        }

        $newResult = array();
        foreach($result as $href => $statusList) {

            $newResult[$href] = $statusList[200];

        }

        return $newResult;

    }

    /**
     * Updates a list of properties on the server
     *
     * The list of properties must have clark-notation properties for the keys, 
     * and the actual (string) value for the value. If the value is null, an 
     * attempt is made to delete the property. 
     *
     * @todo Must be building the request using the DOM, and does not yet 
     *       support complex properties. 
     * @param string $url 
     * @param array $properties 
     * @return void
     */
    public function propPatch($url, array $properties) {

        $body = '<?xml version="1.0"?>' . "\n";
        $body.= '<d:propertyupdate xmlns:d="DAV:">' . "\n";

        foreach($properties as $propName => $propValue) {

            list(
                $namespace,
                $elementName
            ) = Sabre_DAV_XMLUtil::parseClarkNotation($propName);

            if ($propValue === null) {

                $body.="<d:remove><d:prop>\n";

                if ($namespace === 'DAV:') {
                    $body.='    <d:' . $elementName . ' />' . "\n";
                } else {
                    $body.="    <x:" . $elementName . " xmlns:x=\"" . $namespace . "\"/>\n";
                }

                $body.="</d:prop></d:remove>\n";

            } else {

                $body.="<d:set><d:prop>\n";
                if ($namespace === 'DAV:') {
                    $body.='    <d:' . $elementName . '>';
                } else {
                    $body.="    <x:" . $elementName . " xmlns:x=\"" . $namespace . "\">";
                }
                // Shitty.. i know
                $body.=htmlspecialchars($propValue, ENT_NOQUOTES, 'UTF-8'); 
                if ($namespace === 'DAV:') {
                    $body.='</d:' . $elementName . '>' . "\n";
                } else {
                    $body.="</x:" . $elementName . ">\n";
                }
                $body.="</d:prop></d:set>\n";

            }

        }

        $body.= '</d:propertyupdate>';

        $response = $this->request('PROPPATCH', $url, $body, array(
            'Depth' => $depth,
            'Content-Type' => 'application/xml'
        ));

    }

    /**
     * Performs an HTTP options request
     *
     * This method returns all the features from the 'DAV:' header as an array. 
     * If there was no DAV header, or no contents this method will return an 
     * empty array. 
     * 
     * @return array 
     */
    public function options() {

        $result = $this->request('OPTIONS');
        if (!isset($result['headers']['dav'])) {
            return array();
        }

        $features = explode(',', $result['headers']['dav']);
        foreach($features as &$v) {
            $v = trim($v);
        }
        return $features;

    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * If the specified url is relative, it will be expanded based on the base 
     * url.
     *
     * The returned array contains 3 keys:
     *   * body - the response body
     *   * httpCode - a HTTP code (200, 404, etc)
     *   * headers - a list of response http headers. The header names have 
     *     been lowercased.
     *
     * @param string $method 
     * @param string $url 
     * @param string $body 
     * @param array $headers 
     * @return array 
     */
    protected function request($method, $url = '', $body = null, $headers = array()) {

        $url = $this->getAbsoluteUrl($url);

        $curlSettings = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            // Return headers as part of the response
            CURLOPT_HEADER => true
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

        if ($this->userName) {
            $curlSettings[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_DIGEST;
            $curlSettings[CURLOPT_USERPWD] = $this->userName . ':' . $this->password;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlSettings);

        $response = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);

        $headerBlob = substr($response, 0, $curlInfo['header_size']);
        $response = substr($response, $curlInfo['header_size']);

        // In the case of 100 Continue, or redirects we'll have multiple lists 
        // of headers for each separate HTTP response. We can easily split this 
        // because they are separated by \r\n\r\n
        $headerBlob = explode("\r\n\r\n", trim($headerBlob, "\r\n"));
        
        // We only care about the last set of headers
        $headerBlob = $headerBlob[count($headerBlob)-1];

        // Splitting headers
        $headerBlob = explode("\r\n", $headerBlob);
        
        $headers = array();
        foreach($headerBlob as $header) {
            list($hn, $hv) = explode(':', $header, 2);
            $headers[strtolower(trim($hn))] = trim($hv);
        }

        $response = array(
            'body' => $response,
            'statusCode' => $curlInfo['http_code'],
            'headers' => $headers
        );

        if (curl_errno($curl)) {
            throw new Sabre_DAV_Exception('[CURL] Error while making request: ' . curl_error($curl) . ' (error code: ' . curl_errno($curl) . ')');
        } 

        if ($response['statusCode']>=400) {
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
            $parts = parse_url($this->baseUri);
            return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port'])?':' . $parts['port']:'') . $url;
        }

        // Otherwise...
        return $this->baseUri . $url;

    }

    /**
     * Parses a WebDAV multistatus response body
     * 
     * @param string $body 
     * @return array 
     */
    protected function parseMultiStatus($body) {

        $body = Sabre_DAV_XMLUtil::convertDAVNamespace($body);

        $responseXML = simplexml_load_string($body, null, LIBXML_NOBLANKS | LIBXML_NOCDATA);
        if (!$responseXML===false) {
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
