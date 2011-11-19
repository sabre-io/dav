<?php

class Sabre_DAV_DebugPlugin extends Sabre_DAV_ServerPlugin {

    protected $server;
    protected $logHandle;

    protected $startTime;

    protected $logLevel = 1;

    protected $contentTypeWhiteList = array(
        '|^text/|',
        '|^application/xml|',
    );

    function __construct($logFile, $logLevel = 1) {

        $logFile = str_replace('%t',time(), $logFile);

        $this->logHandle = fopen($logFile,'a');
        $this->logLevel = $logLevel;
        $this->startTime = time();

    }

    public function getPluginName() {

        return 'debuglogger';

    }

    /**
     * Initializes the plugin 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod', array($this, 'beforeMethod'), 5);
        $this->server->subscribeEvent('unknownMethod', array($this, 'unknownMethod'), 5);
        $this->server->subscribeEvent('report', array($this, 'report'), 5);
        $this->server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'), 5);
        $this->log(2,'Initialized plugin. Request time ' . $this->startTime . ' (' . date(DateTime::RFC2822,$this->startTime) . '). Version: ' . Sabre_DAV_Version::VERSION);

    }

    /**
     * Very first event to be triggered. This allows us to log the HTTP 
     * request. 
     * 
     * @param string $method 
     * @param string $uri 
     * @return void
     */
    public function beforeMethod($method, $uri) {

        $this->log(3,'beforeMethod triggered. Method: ' . $method . ' uri: ' . ($uri?$uri:'(root)'));
        $this->log(2,'Plugins loaded:');
        foreach($this->server->getPlugins() as $pluginName => $plugin) {
            $this->log(2,'  ' . $pluginName . ' (' . get_class($plugin) . ')');
        }
        $this->log(2,'SabreDAV server Base URI: ' . $this->server->getBaseUri()); 
        $this->log(1,$this->server->httpRequest->getMethod() . ' ' . $this->server->httpRequest->getUri() . ' -> ' . $this->server->getRequestUri());
        $this->log(2,'Headers:');
        foreach($this->server->httpRequest->getHeaders() as $key=>$value) {
            $this->log(2,'  '  . $key . ': ' . $value);
        }

        // We're only going to show the request body if it's text-based. The 
        // maximum size will be 10k.
        $contentType = $this->server->httpRequest->getHeader('Content-Type');
        $showBody = false;
        foreach($this->contentTypeWhiteList as $wl) {

            if (preg_match($wl, $contentType)) {
                $showBody = true;
                break;
            }

        }
        if ($showBody && $this->logLevel>=2) {
            // We need to grab the body, and put it in an intermediate stream.
            $newBody = fopen('php://temp','r+');
            $body = $this->server->httpRequest->getBody();

            // Only grabbing the first 10kb
            $strBody = fread($body, 10240);

            $this->log('Request body:');
            $this->log($strBody);

            // Writing the bytes we already read
            fwrite($newBody, $strBody);

            // Writing the remainder of the input body, if there's anything 
            // left.
            stream_copy_to_stream($body, $newBody);
            rewind($newBody);

            $this->server->httpRequest->setBody($newBody, true);

        }
             
    }

    /**
     * This event is triggered when SabreDAV encounters a method that not 
     * handles by the core server. These are often handled by plugins.
     * 
     * @param string $method 
     * @param string $uri 
     * @return void
     */
    public function unknownMethod($method, $uri) {

        $this->log(3,'unknownMethod triggered. Method: ' . $method . ' uri: ' . ($uri?$uri:'(root)'));

    }
    /**
     * This event is triggered when a report was requested.
     * 
     * @param string $reportName
     * @return void
     */
    public function report($reportName) {

        $this->log(3,'Report requested: ' . $reportName);

    }

    /**
     * This event it triggered when PROPFIND is done, or a subsystem
     * requested properties. 
     * 
     * @param array $requestedProperties 
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, $requestedProperties, $returnedProperties) {

        $this->log(3,'Properties requested for uri (' . $path . '):');
        $this->log(3,print_r($requestedProperties,true));

    }

    /**
     * Appends a message to the log 
     * 
     * @param string $message 
     * @return void
     */
    public function log($logLevel, $message) {

        if ($logLevel <= $this->logLevel) {
            fwrite($this->logHandle,$message . "\n");
        }

    }

}
