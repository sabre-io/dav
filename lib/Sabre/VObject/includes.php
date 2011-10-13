<?php

/**
 * Sabre_VObject includes file
 *
 * Including this file will automatically include all files from the VObject
 * package.
 *
 * This often allows faster loadtimes, as autoload-speed is often quite slow.
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

// Begin includes
include __DIR__ . '/DateTimeParser.php';
include __DIR__ . '/ElementList.php';
include __DIR__ . '/FreeBusyGenerator.php';
include __DIR__ . '/Node.php';
include __DIR__ . '/Parameter.php';
include __DIR__ . '/ParseException.php';
include __DIR__ . '/Reader.php';
include __DIR__ . '/RecurrenceIterator.php';
include __DIR__ . '/Version.php';
include __DIR__ . '/Element.php';
include __DIR__ . '/Property.php';
include __DIR__ . '/Component.php';
include __DIR__ . '/Element/DateTime.php';
include __DIR__ . '/Element/MultiDateTime.php';
// End includes
