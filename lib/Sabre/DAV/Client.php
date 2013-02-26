<?php

namespace Sabre\DAV;

/**
 * SabreDAV DAV client
 *
 * This client wraps around Curl to provide a convenient API to a WebDAV
 * server.
 *
 * NOTE: This class is experimental, it's api will likely change in the future.
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Client {

    /**
     * The propertyMap is a key-value array.
     *
     * If you use the propertyMap, any {DAV:}multistatus responses with the
     * properties listed in this array, will automatically be mapped to a
     * respective class.
     *
     * The {DAV:}resourcetype property is automatically added. This maps to
     * Sabre\DAV\Property\ResourceType
     *
     * @var array
     */
    public $propertyMap = array();

    protected $baseUri;
    protected $userName;
    protected $password;
    protected $proxy;
    protected $trustedCertificates;

    /**
     * Basic authentication
     */
    const AUTH_BASIC = 1;

    /**
     * Digest authentication
     */
    const AUTH_DIGEST = 2;

    /**
     * The authentication type we're using.
     *
     * This is a bitmask of AUTH_BASIC and AUTH_DIGEST.
     *
     * If DIGEST is used, the client makes 1 extra request per request, to get
     * the authentication tokens.
     *
     * @var int
     */
    protected $authType;

    /**
     * Indicates if SSL verification is enabled or not.
     *
     * @var boolean
     */
    private $verifyPeer;

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
            throw new \InvalidArgumentException('A baseUri must be provided');
        }

        $validSettings = array(
            'baseUri',
            'userName',
            'password',
            'proxy',
        );

        foreach($validSettings as $validSetting) {
            if (isset($settings[$validSetting])) {
                $this->$validSetting = $settings[$validSetting];
            }
        }

        if (isset($settings['authType'])) {
            $this->authType = $settings['authType'];
        } else {
            $this->authType = self::AUTH_BASIC | self::AUTH_DIGEST;
        }

        $this->propertyMap['{DAV:}resourcetype'] = 'Sabre\\DAV\\Property\\ResourceType';

    }

    /**
     * Add trusted root certificates to the webdav client.
     *
     * The parameter certificates should be a absolute path to a file
     * which contains all trusted certificates
     *
     * @param string $certificates
     */
    public function addTrustedCertificates($certificates) {
        $this->trustedCertificates = $certificates;
    }

    /**
     * Enables/disables SSL peer verification
     *
     * @param boolean $value
     */
    public function setVerifyPeer($value) {
        $this->verifyPeer = $value;
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

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElementNS('DAV:', 'd:propfind');
        $prop = $dom->createElement('d:prop');

        foreach($properties as $property) {

            list(
                $namespace,
                $elementName
            ) = XMLUtil::parseClarkNotation($property);

            if ($namespace === 'DAV:') {
                $element = $dom->createElement('d:'.$elementName);
            } else {
                $element = $dom->createElementNS($namespace, 'x:'.$elementName);
            }

            $prop->appendChild( $element );
        }

        $dom->appendChild($root)->appendChild( $prop );
        $body = $dom->saveXML();

        $response = $this->request('PROPFIND', $url, $body, array(
            'Depth' => $depth,
            'Content-Type' => 'application/xml'
        ));

        $result = $this->parseMultiStatus($response['body']);

        // If depth was 0, we only return the top item
        if ($depth===0) {
            reset($result);
            $result = current($result);
            return isset($result[200])?$result[200]:array();
        }

        $newResult = array();
        foreach($result as $href => $statusList) {

            $newResult[$href] = isset($statusList[200])?$statusList[200]:array();

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
     * @param string $url
     * @param array $properties
     * @return void
     */
    public function propPatch($url, array $properties) {

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElementNS('DAV:', 'd:propertyupdate');

        foreach($properties as $propName => $propValue) {

            list(
                $namespace,
                $elementName
            ) = XMLUtil::parseClarkNotation($propName);

            if ($propValue === null) {

                $remove = $dom->createElement('d:remove');
                $prop = $dom->createElement('d:prop');

                if ($namespace === 'DAV:') {
                    $element = $dom->createElement('d:'.$elementName);
                } else {
                    $element = $dom->createElementNS($namespace, 'x:'.$elementName);
                }

                $root->appendChild( $remove )->appendChild( $prop )->appendChild( $element );

            } else {

                $set = $dom->createElement('d:set');
                $prop = $dom->createElement('d:prop');

                if ($namespace === 'DAV:') {
                    $element = $dom->createElement('d:'.$elementName);
                } else {
                    $element = $dom->createElementNS($namespace, 'x:'.$elementName);
                }

                if ( $propValue instanceof Property ) {
                    $propValue->serialize( new Server, $element );
                } else {
                    $element->nodeValue = htmlspecialchars($propValue, ENT_NOQUOTES, 'UTF-8');
                }

                $root->appendChild( $set )->appendChild( $prop )->appendChild( $element );

            }

        }

        $dom->appendChild($root);
        $body = $dom->saveXML();

        $this->request('PROPPATCH', $url, $body, array(
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
    public function request($method, $url = '', $body = null, $headers = array()) {

        $url = $this->getAbsoluteUrl($url);

        $curlSettings = array(
            CURLOPT_RETURNTRANSFER => true,
            // Return headers as part of the response
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => $body,
            // Automatically follow redirects
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        );

        if($this->verifyPeer !== null) {
            $curlSettings[CURLOPT_SSL_VERIFYPEER] = $this->verifyPeer;
        }

        if($this->trustedCertificates) {
            $curlSettings[CURLOPT_CAINFO] = $this->trustedCertificates;
        }

        switch ($method) {
            case 'HEAD' :

                // do not read body with HEAD requests (this is necessary because cURL does not ignore the body with HEAD
                // requests when the Content-Length header is given - which in turn is perfectly valid according to HTTP
                // specs...) cURL does unfortunately return an error in this case ("transfer closed transfer closed with
                // ... bytes remaining to read") this can be circumvented by explicitly telling cURL to ignore the
                // response body
                $curlSettings[CURLOPT_NOBODY] = true;
                $curlSettings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                break;

            default:
                $curlSettings[CURLOPT_CUSTOMREQUEST] = $method;
                break;

        }

        // Adding HTTP headers
        $nHeaders = array();
        foreach($headers as $key=>$value) {

            $nHeaders[] = $key . ': ' . $value;

        }
        $curlSettings[CURLOPT_HTTPHEADER] = $nHeaders;

        if ($this->proxy) {
            $curlSettings[CURLOPT_PROXY] = $this->proxy;
        }

        if ($this->userName && $this->authType) {
            $curlType = 0;
            if ($this->authType & self::AUTH_BASIC) {
                $curlType |= CURLAUTH_BASIC;
            }
            if ($this->authType & self::AUTH_DIGEST) {
                $curlType |= CURLAUTH_DIGEST;
            }
            $curlSettings[CURLOPT_HTTPAUTH] = $curlType;
            $curlSettings[CURLOPT_USERPWD] = $this->userName . ':' . $this->password;
        }

        list(
            $response,
            $curlInfo,
            $curlErrNo,
            $curlError
        ) = $this->curlRequest($url, $curlSettings);

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
            $parts = explode(':', $header, 2);
            if (count($parts)==2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        $response = array(
            'body' => $response,
            'statusCode' => $curlInfo['http_code'],
            'headers' => $headers
        );

        if ($curlErrNo) {
            throw new Exception('[CURL] Error while making request: ' . $curlError . ' (error code: ' . $curlErrNo . ')');
        }

        if ($response['statusCode']>=400) {
            switch ($response['statusCode']) {
                case 400 :
                    throw new Exception\BadRequest('Bad request');
                case 401 :
                    throw new Exception\NotAuthenticated('Not authenticated');
                case 402 :
                    throw new Exception\PaymentRequired('Payment required');
                case 403 :
                    throw new Exception\Forbidden('Forbidden');
                case 404:
                    throw new Exception\NotFound('Resource not found.');
                case 405 :
                    throw new Exception\MethodNotAllowed('Method not allowed');
                case 409 :
                    throw new Exception\Conflict('Conflict');
                case 412 :
                    throw new Exception\PreconditionFailed('Precondition failed');
                case 416 :
                    throw new Exception\RequestedRangeNotSatisfiable('Requested Range Not Satisfiable');
                case 500 :
                    throw new Exception('Internal server error');
                case 501 :
                    throw new Exception\NotImplemented('Not Implemented');
                case 507 :
                    throw new Exception\InsufficientStorage('Insufficient storage');
                default:
                    throw new Exception('HTTP error response. (errorcode ' . $response['statusCode'] . ')');
            }
        }

        return $response;

    }

    /**
     * Wrapper for all curl functions.
     *
     * The only reason this was split out in a separate method, is so it
     * becomes easier to unittest.
     *
     * @param string $url
     * @param array $settings
     * @return array
     */
    // @codeCoverageIgnoreStart
    protected function curlRequest($url, $settings) {

        $curl = curl_init($url);
        curl_setopt_array($curl, $settings);

        return array(
            curl_exec($curl),
            curl_getinfo($curl),
            curl_errno($curl),
            curl_error($curl)
        );

    }
    // @codeCoverageIgnoreEnd

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
     * This method returns an array with the following structure
     *
     * array(
     *   'url/to/resource' => array(
     *     '200' => array(
     *        '{DAV:}property1' => 'value1',
     *        '{DAV:}property2' => 'value2',
     *     ),
     *     '404' => array(
     *        '{DAV:}property1' => null,
     *        '{DAV:}property2' => null,
     *     ),
     *   )
     *   'url/to/resource2' => array(
     *      .. etc ..
     *   )
     * )
     *
     *
     * @param string $body xml body
     * @return array
     */
    public function parseMultiStatus($body) {

        try {
            $dom = XMLUtil::loadDOMDocument($body);
        } catch (Exception\BadRequest $e) {
            throw new \InvalidArgumentException('The body passed to parseMultiStatus could not be parsed. Is it really xml?');
        }

        $responses = Property\ResponseList::unserialize(
            $dom->documentElement,
            $this->propertyMap
        );

        $result = array();

        foreach($responses->getResponses() as $response) {

            $result[$response->getHref()] = $response->getResponseProperties();

        }

        return $result;

    }

}
