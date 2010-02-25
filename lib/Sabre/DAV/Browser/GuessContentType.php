<?php

class Sabre_DAV_Browser_GuessContentType extends Sabre_DAV_ServerPlugin {

    public $extensionMap = array(
        // images
        'jpg' => 'image/jpg',
        'gif' => 'image/gif',
        'png' => 'image/png',

        // groupware
        'ics' => 'text/calendar',
        'vcf' => 'text/x-vcard',

    );

    public function initialize(Sabre_DAV_Server $server) {

        $server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'),200);

    }

    public function afterGetProperties($path, &$properties) {

        if (array_key_exists('{DAV:}getcontenttype', $properties[404])) {
            
            $fileName = basename($path);
            $contentType = $this->getContentType($fileName);

            if ($contentType) {
                $properties[200]['{DAV:}getcontenttype'] = $contentType;
                unset($properties[404]['{DAV:}getcontenttype']);
            }

        }

    }

    protected function getContentType($fileName) {

        // Just grabbing the extension
        $extension = substr($fileName,strrpos($fileName,'.')+1);
        if (isset($this->extensionMap[$extension]))
            return $this->extensionMap[$extension];

    }

}
