<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\CardDAV\Xml\Request\AddressBookQueryReport;
use Sabre\DAV\ICollection;

/**
 * This interface represents a node that may contain address book objects.
 *
 * This is the shared parent for both the Inbox collection and calendars
 * resources.
 *
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IAddressBookObjectContainer extends ICollection
{
    /**
     * Performs an addressbook-query on the contents of this calendar.
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
	 * @see CalendarQueryValidator
     * @return string[]
     */
    public function addressBookQuery(AddressBookQueryReport $report): array;
}
