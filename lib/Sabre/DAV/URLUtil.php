<?php

/**
 * URL utilities 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 */

/**
 * URL utility class
 *
 * This class provides methods to deal with encoding and decoding url (percent encoded) strings.
 *
 * It was not possible to use PHP's built-in methods for this, because some clients don't like
 * encoding of certain characters.
 *
 * Specifically, it was found that GVFS (gnome's webdav client) does not like encoding of ( and
 * ). Since these are reserved, but don't have a reserved meaning in url, these characters are
 * kept as-is.
 */
class Sabre_DAV_URLUtil {

    /**
     * Encodes the path of a url.
     *
     * slashes (/) are treated as path-separators.
     * 
     * @param string $path 
     * @return string 
     */
    static function encodePath($path) {

        $path = explode('/',$path);
        return implode('/',array_map(array('Sabre_DAV_URLUtil','encodePathSegment'), $path));

    }

    /**
     * Encodes a 1 segment of a path
     *
     * Slashes are considered part of the name, and are encoded as %2f
     * 
     * @param string $pathSegment 
     * @return string 
     */
    static function encodePathSegment($pathSegment) {

        $newStr = '';
        for($i=0;$i<strlen($pathSegment);$i++) {
            $c = ord($pathSegment[$i]);

            if(
                
                /* Unreserved chacaters */

                ($c>=0x41 /* A */ && $c<=0x5a /* Z */) ||
                ($c>=0x61 /* a */ && $c<=0x7a /* z */) ||
                ($c>=0x30 /* 0 */ && $c<=0x39 /* 9 */) ||
                $c===0x5f /* _ */ ||
                $c===0x2d /* - */ ||
                $c===0x2e /* . */ ||
                $c===0x7E /* ~ */ ||

                /* Reserved, but no reserved purpose */
                $c===0x28 /* ( */ ||
                $c===0x29 /* ) */

            ) { 
                $newStr.=$pathSegment[$i];
            } else {
                $newStr.='%' . str_pad(dechex($c), 2, '0', STR_PAD_LEFT);
            }
                
        }
        return $newStr;

    }

    /**
     * Decodes a url-encoded path
     *
     * @param string $path 
     * @return string 
     */
    static function decodePath($path) {

        return self::decodePathSegment($path);

    }

    /**
     * Decodes a url-encoded path segment
     *
     * @param string $path 
     * @return string 
     */
    static function decodePathSegment($path) {

        $path = urldecode($path);
        $encoding = mb_detect_encoding($path, array('UTF-8','ISO-8859-1'));
        switch($encoding) {

            case 'UTF-8' : 
                return $path;
            case 'ISO-8859-1' : 
                return utf8_encode($path);
            default : 
                throw new Sabre_DAV_Exception_BadRequest('Invalid url encoding');
        }

    }


}
