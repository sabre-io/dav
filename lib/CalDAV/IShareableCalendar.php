<?php

namespace Sabre\CalDAV;

use Sabre\DAV\Sharing\IShareableNode;

/**
 * This interface represents a Calendar that can be shared with other users.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IShareableCalendar extends IShareableNode {

    /**
     * Marks this calendar as published.
     *
     * Publishing a calendar should automatically create a read-only, public,
     * subscribable calendar.
     *
     * @param bool $value
     * @return void
     */
    function setPublishStatus($value);

}
