<?php

class Sabre_DAV_Redirect_Plugin {

    protected $server;

    public function initialize(Sabre_DAV_Server $server) {

        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'));
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));
        $this->server = $server;

    }

    public function beforeMethod($method) {

        $uri = $this->server->getRequestUri();
        if (!$this->server->tree->nodeExists($uri))
            return;

        $node = $this->server->tree->getNodeForPath($uri);
        if (!($node instanceof Sabre_DAV_Redirect_IRedirectNode)) 
            return;

        $applyToRedirectRef = $this->httpRequest->getHeader('Apply-To-Redirect-Ref');
        if ($applyToRedirectRef!=='T') {

            $target = $node->getRedirectTarget();
            $this->server->httpResponse->sendStatus(302);
            $this->server->httpResponse->setHeader('Location',$location);
            // TODO: $this->httpResponse->setHeader('Redirect-Ref','');
            return false;

        } else {
            // We need to operate on the redirect reference itself
            
            // The spec explicitly states to throw 403 when GET or 
            // PUT is called.
            if ($method==='PUT' || $method==='GET') {
                throw new Sabre_DAV_Exception_Forbidden('Cannot PUT or GET on redirect references');
            }

            //TODO
        } 

    }

    public function getMethods($uri) {

        list($parentUri) = Sabre_DAV_URLUtil::splitPath($uri);

        // If the node exists and it's a redirect-node we allow updating 
        if ($this->server->tree->nodeExists($uri)) {
            $node = $this->server->tree->getNodeForPath($uri);

            if ($node instanceof Sabre_DAV_Redirect_IRedirectNode) {
                return array('UPDATEREDIRECTREF');
            }

        // If the node does not exist and it's parent is a redirectparent we
        // allow creation
        } elseif ($this->server->tree->nodeExists($parentUri)) {

            $parentNode = $this->server->tree->getNodeForPath($parentUri);
            if ($parentNode instanceof Sabre_DAV_Redirect_IRedirectParent) {
                return array('MKREDIRECTREF');
            }
        }
        return array();

    }

    public function unknownMethod($method) {

        $uri = $this->server->getRequestUri();
        if ($method === 'MKREDIRECTREF') {
            $this->httpMkRedirectRef($uri);
            return false;
        }

    }

    public function httpMkRedirectRef($uri) {

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($this->httpRequest->getBody(true));
        $refTargetN = $dom->getElementsByTagNameNS('urn:DAV','href');
        $redirectLifeTimeN = $dom->getElementsByTagNameNS('urn:DAV','redirect-lifetime');

        if (!$refTargetN) throw new Sabre_DAV_Exception_BadRequest('The {DAV:}href element must be specified');
        $refTarget = $refTargetN->textValue;

        $redirectLifeTime = 'temporary';
        if ($redirectLifeTimeN) foreach($redirectLifeTimeN->children() as $child) {

            $n = Sabre_DAV_XMLUtil::toClarkNotation($child);
            if ($n==='{DAV:}temporary') {
                $redirectLifeTime = 'temporary';
                break;
            }
            if ($n==='{DAV:}permanent') {
                $redirectLifeTime = 'permanent';
            }

        }
        
        list($parentUri, $newName) = Sabre_DAV_URLUtil::splitPath($uri);

        // validating the location of the node 
        if (!$this->server->tree->nodeExists($parent)) {
            throw new Sabre_DAV_Exception_Conflict('Parent node does not exist');
        }

        $parentNode = $this->server->tree->getNodeForPath($parent);
        if (!($parent instanceof Sabre_DAV_Redirect_IRedirectParent)) {
            throw new Sabre_DAV_Exception_MethodNotAllowed('The parent uri does not allow creation of references.');
        }

        if ($parent->childExists($newName)) {
            throw new Sabre_DAV_Exception_MethodNotAllowed('The resource you tried to create already exists');
        }

        // TODO validate reftarget
        $parent->createRedirect($newName, $redirectLifeTime, $refTarget);
        
        $this->server->httpResponse->sendStatus(201);
 

    }

}

?>
