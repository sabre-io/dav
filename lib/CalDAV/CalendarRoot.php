<?php

namespace Sabre\CalDAV;

/**
 * This class exists to provide forward-compatiblity with sabre/dav 2.1.
 *
 * You are encouraged to use CalendarRoot instead of CalendarRootNode, as the
 * latter will be removed in a future version of sabre/dav.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class_alias('Sabre\\CalDAV\\CalendarRootNode', 'Sabre\\CalDAV\\CalendarRoot');
