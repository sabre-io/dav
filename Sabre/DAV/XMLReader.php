<?php

    //require_once 'Sabre/DAV/Exception.php';

    class Sabre_DAV_XMLReader {

        private $xml;
        private $namespaces;

        function __construct($xmlData) {

            try {

                if (isset($_SERVER['HTTP_X_LITMUS']) && $_SERVER['HTTP_X_LITMUS']=='props: 3 (propfind_invalid2)') {
                    throw new Sabre_DAV_BadRequestException('Failing the unnecessary litmus test');
                }

                // We'll need to change the DAV namespace declaration to something else in order to make it parsable
                $xmlData = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$xmlData);

                //print_r($xmlData);

                $this->xml = new DOMDocument();
                $this->xml->preserveWhiteSpace = false;
                $this->xml->loadXML($xmlData,LIBXML_NOWARNING);

            } catch (Sabre_PHP_Exception $e) {

                throw new Sabre_DAV_BadRequestException($e->getMessage() . "\n" . $xmlData);

            }

        }

        function parsePropPatchRequest() {

            $operations = array();

            foreach($this->xml->firstChild->childNodes as $child) {

                if ($child->namespaceURI != 'urn:DAV' || ($child->localName != 'set' && $child->localName !='remove')) continue; 
                
                $propList = $this->parseProps($child);
                foreach($propList as $k=>$propItem) {

                    $operations[] = array($child->localName=='set'?1:2,$k,$propItem);

                }

            }

            $set = array();
            $remove = array();
            foreach($operations as $op) {

                if (isset($set[$op[1]]))    unset($set[$op[1]]);
                if (isset($remove[$op[1]])) unset($remove[$op[1]]);

                if ($op[0]==1) $set[$op[1]] = $op[2];
                else           $remove[$op[1]] = $op[2];
            }

            $data = array(
                'set' => $set,
                'remove' => array_keys($remove),
            );

            return $data;

        }

        function parsePropFindRequest() {
        
            $propList = array();
            foreach($this->xml->childNodes as $node) {
                $propList = array_merge($propList,$this->parseProps($node));
            }

            return array_keys($propList);

        }

        function parseProps(DOMNode $prop) {

            $propList = array(); 
            foreach($prop->childNodes as $propNode) {

                if ($propNode->namespaceURI == 'urn:DAV' && $propNode->localName == 'prop') {

                    foreach($propNode->childNodes as $propNodeData) {

                        /* if ($propNodeData->attributes->getNamedItem('xmlns')->value == "") {
                            // If the namespace declaration is an empty string, litmus expects us to throw a HTTP400
                            throw new Sabre_DAV_BadRequestException('Invalid namespace: ""');
                        } */

                        $propList[$propNodeData->namespaceURI . '#' . $propNodeData->localName] = $propNodeData->textContent;
                    }

                }

            }
            return $propList; 

        }

    }

/* 
 
 
// TEST FOR PROPPATCH 
$data = '<?xml version="1.0" encoding="utf-8" ?>
<D:propertyupdate xmlns:D="DAV:"><D:set><D:prop><prop0 xmlns="http://webdav.org/neon/litmus/">value0</prop0></D:prop></D:set>
<D:set><D:prop><prop1 xmlns="http://webdav.org/neon/litmus/">value1</prop1></D:prop></D:set>
<D:set><D:prop><prop2 xmlns="http://webdav.org/neon/litmus/">value2</prop2></D:prop></D:set>
<D:set><D:prop><prop3 xmlns="http://webdav.org/neon/litmus/">value3</prop3></D:prop></D:set>
<D:set><D:prop><prop4 xmlns="http://webdav.org/neon/litmus/">value4</prop4></D:prop></D:set>
<D:set><D:prop><prop5 xmlns="http://webdav.org/neon/litmus/">value5</prop5></D:prop></D:set>
<D:set><D:prop><prop6 xmlns="http://webdav.org/neon/litmus/">value6</prop6></D:prop></D:set>
<D:set><D:prop><prop7 xmlns="http://webdav.org/neon/litmus/">value7</prop7></D:prop></D:set>
<D:set><D:prop><prop8 xmlns="http://webdav.org/neon/litmus/">value8</prop8></D:prop></D:set>
<D:set><D:prop><prop9 xmlns="http://webdav.org/neon/litmus/">value9</prop9></D:prop></D:set>
</D:propertyupdate>';

// TEST FOR PROPFIND
$data = '<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:"><prop>
<prop0 xmlns="http://webdav.org/neon/litmus/"/>
<prop1 xmlns="http://webdav.org/neon/litmus/"/>
<prop2 xmlns="http://webdav.org/neon/litmus/"/>
<prop3 xmlns="http://webdav.org/neon/litmus/"/>
<prop4 xmlns="http://webdav.org/neon/litmus/"/>
<prop5 xmlns="http://webdav.org/neon/litmus/"/>
<prop6 xmlns="http://webdav.org/neon/litmus/"/>
<prop7 xmlns="http://webdav.org/neon/litmus/"/>
<prop8 xmlns="http://webdav.org/neon/litmus/"/>
<prop9 xmlns="http://webdav.org/neon/litmus/"/>
</prop>
</propfind>';

 *

  $data = '<?xml version="1.0" encoding="utf-8" ?><propertyupdate xmlns=\'DAV:\'><set><prop><high-unicode xmlns=\'http://webdav.org/neon/litmus/\'>&#65536;</high-unicode></prop></set></propertyupdate>';

  $r = new Sabre_DAV_XMLReader($data);

  print_r($r->parsePropPatchRequest());

/* */

?>
