<?php

/**
 * This is a default exception wrapper for PHP errors.
 * This allows you to deal with PHP errors as exceptions (using try..catch blocks etc..)
 * 
 * @uses Exception
 * @package Sabre
 * @subpackage PHP
 * @version $Id$
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_PHP_Exception extends Exception {

    /**
     * __construct 
     * 
     * @param string $message 
     * @param string $code 
     * @param string $file 
     * @param int $line 
     * @return void
     */
    function __construct($message,$code=false,$file=false,$line=false) {

        parent::__construct($message,$code);
        $this->file = $file;
        $this->line = $line;

    }

    /**
     * Register this class as an error handler 
     * 
     * @return void
     */
    static function register() {

        set_error_handler(array('Sabre_PHP_Exception','handleError'));

    }

    static function unregister() {

        restore_error_handler();

    }

    /**
     * handleError 
     * 
     * @param string $code 
     * @param string $message 
     * @param string $file 
     * @param int $line 
     * @return void
     */
    static function handleError($code,$message,$file,$line) {

        if (!$code) return;
        throw new self($message,$code,$file,$line);

    }

}

