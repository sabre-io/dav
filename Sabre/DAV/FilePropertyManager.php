<?php

    require_once 'Sabre/DAV/PropertyManager.php';

    class Sabre_DAV_FilePropertyManager extends Sabre_DAV_PropertyManager {

        private $basePath;

        function __construct($basePath = null) {

            if (is_null($basePath)) $basePath = ini_get('session.save_path') . '/SabreDav_';
            $this->basePath = $basePath;

        }

        function storeProperties($location,$properties,$clear = false) {

            if (!$properties && !$clear) return true;
            
            if (!$properties) $properties = array();
            if (!$clear) {

                $properties = array_merge($this->getProperties($location),$properties);

            }
            
            return file_put_contents($this->getPath($location),serialize($properties));

        }

        function getProperties($location) {

            $path = $this->getPath($location);
            if (file_exists($path)) $props = unserialize(file_get_contents($path));
            else $props = array();
            return $props;

        }

        function deleteProperties($location,$properties) {

            $props = $this->getProperties($location);
            if (!$props) return true;

            foreach($properties as $prop) if (isset($props[$prop])) unset($props[$prop]);

            if ($props) {
                file_put_contents($this->getPath($location),serialize($props));
            } else {
                unlink($this->getPath($location));
            }
            return true;

        }

        private function getPath($location) {
            
            return $this->basePath . md5($location) . '.props';

        }

    }

?>
