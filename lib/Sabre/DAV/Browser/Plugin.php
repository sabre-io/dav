<?php

/**
 * Browser Plugin
 *
 * This plugin provides a html representation, so that a WebDAV server may be accessed
 * using a browser.
 *
 * The class intercepts GET requests to collection resources and generates a simple 
 * html index. It's not really pretty though, extend to skin this listing.
 * 
 * @package Sabre
 * @subpackage DAV
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Browser_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * reference to server class 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * enableEditing
     * 
     * @var bool 
     */
    protected $enablePost = true;

    /**
     * Creates the object.
     *
     * By default it will allow file creation and uploads.
     * Specify the first argument as false to disable this
     * 
     * @param bool $enablePost 
     * @return void
     */
    public function __construct($enablePost=true) {

        $this->enablePost = $enablePost; 

    }

    /**
     * Initializes the plugin and subscribes to events 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'httpGetInterceptor'));
        if ($this->enablePost) $this->server->subscribeEvent('unknownMethod',array($this,'httpPOSTHandler'));
    }

    /**
     * This method intercepts GET requests to collections and returns the html 
     * 
     * @param string $method 
     * @return bool 
     */
    public function httpGetInterceptor($method) {

        if ($method!='GET') return true;
        
        $node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
        if ($node instanceof Sabre_DAV_IFile) return true;

        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type','text/html; charset=utf-8');

        $this->server->httpResponse->sendBody(
            $this->generateDirectoryIndex($this->server->getRequestUri())
        );

        return false;
        
    }

    /**
     * Handles POST requests for tree operations
     * 
     * This method is not yet used.
     * 
     * @param string $method 
     * @return bool
     */
    public function httpPOSTHandler($method) {

        if ($method!='POST') return true;
        if (isset($_POST['action'])) switch($_POST['action']) {

            case 'mkcol' :
                if (isset($_POST['name']) && trim($_POST['name'])) {
                    // Using basename() because we won't allow slashes
                    list(, $folderName) = Sabre_DAV_URLUtil::splitPath(trim($_POST['name']));
                    $this->server->createDirectory($this->server->getRequestUri() . '/' . $folderName);
                }
                break;
            case 'put' :
                if ($_FILES) $file = current($_FILES);
                else break;
                $newName = trim($file['name']);
                list(, $newName) = Sabre_DAV_URLUtil::splitPath(trim($file['name']));
                if (isset($_POST['name']) && trim($_POST['name']))
                    $newName = trim($_POST['name']);

                // Making sure we only have a 'basename' component
                list(, $newName) = Sabre_DAV_URLUtil::splitPath($newName);
                    
               
                if (is_uploaded_file($file['tmp_name'])) {
                    $parent = $this->server->tree->getNodeForPath(trim($this->server->getRequestUri(),'/'));
                    $parent->createFile($newName,fopen($file['tmp_name'],'r'));
                }

        }
        $this->server->httpResponse->setHeader('Location',$this->server->httpRequest->getUri());
        return false;

    }

    /**
     * Escapes a string for html. 
     * 
     * @param string $value 
     * @return void
     */
    public function escapeHTML($value) {

        return htmlspecialchars($value,ENT_QUOTES,'UTF-8');

    }

    /**
     * Generates the html directory index for a given url 
     *
     * @param string $path 
     * @return string 
     */
    public function generateDirectoryIndex($path) {

        ob_start();
        echo "<html>
<head>
  <title>Index for " . $this->escapeHTML($path) . "/ - SabreDAV " . Sabre_DAV_Version::VERSION . "</title>
  <style type=\"text/css\"> body { Font-family: arial}</style>
</head>
<body>
  <h1>Index for " . $this->escapeHTML($path) . "/</h1>
  <table>
    <tr><th>Name</th><th>Type</th><th>Size</th><th>Last modified</th></tr>
    <tr><td colspan=\"4\"><hr /></td></tr>";
    
    $files = $this->server->getPropertiesForPath($path,array(
        '{DAV:}resourcetype',
        '{DAV:}getcontenttype',
        '{DAV:}getcontentlength',
        '{DAV:}getlastmodified',
    ),1);

    foreach($files as $k=>$file) {

        // This is the current directory, we can skip it
        if ($file['href']==$path) continue;

        $name = $this->escapeHTML(basename($file['href']));

        if (isset($file[200]['{DAV:}resourcetype'])) {
            $type = $file[200]['{DAV:}resourcetype']->getValue();
            if ($type=='{DAV:}collection') {
                $type = 'Directory';
            } elseif ($type=='') {
                if (isset($file[200]['{DAV:}getcontenttype'])) {
                    $type = $file[200]['{DAV:}getcontenttype'];
                } else {
                    $type = 'Unknown';
                }
            } elseif (is_array($type)) {
                $type = implode(', ', $type);
            }
        }
        $type = $this->escapeHTML($type);
        $size = isset($file[200]['{DAV:}getcontentlength'])?(int)$file[200]['{DAV:}getcontentlength']:'';
        $lastmodified = isset($file[200]['{DAV:}getlastmodified'])?date(DATE_ATOM,$file[200]['{DAV:}getlastmodified']->getTime()):'';

        $fullPath = '/' . trim($this->server->getBaseUri() . ($path?$this->escapeHTML($path) . '/':'') . $name,'/');

        echo "<tr>
<td><a href=\"{$fullPath}\">{$name}</a></td>
<td>{$type}</td>
<td>{$size}</td>
<td>{$lastmodified}</td>
</tr>";

    }

  echo "<tr><td colspan=\"4\"><hr /></td></tr>";

  if ($this->enablePost) {
       echo '<tr><td><form method="post" action="">
            <h3>Create new folder</h3>
            <input type="hidden" name="action" value="mkcol" />
            Name: <input type="text" name="name" /><br />
            <input type="submit" value="create" />
            </form>
            <form method="post" action="" enctype="multipart/form-data">
            <h3>Upload file</h3>
            <input type="hidden" name="action" value="put" />
            Name (optional): <input type="text" name="name" /><br />
            File: <input type="file" name="file" /><br />
            <input type="submit" value="upload" />
            </form>
       </td></tr>';
  }

  echo"</table>
  <address>Generated by SabreDAV " . Sabre_DAV_Version::VERSION ."-". Sabre_DAV_Version::STABILITY . " (c)2007-2010 <a href=\"http://code.google.com/p/sabredav/\">http://code.google.com/p/sabredav/</a></address>
</body>
</html>";

        return ob_get_clean();

    }

}
