<?php

use Sabre_CalDAV_SharingPlugin as SharingPlugin;

/**
 * Invite property
 *
 * This property encodes the 'invite' property, as defined by
 * the 'caldav-sharing-02' spec, in the http://calendarserver.org/ns/
 * namespace.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @see https://trac.calendarserver.org/browser/CalendarServer/trunk/doc/Extensions/caldav-sharing-02.txt
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Property_Invite extends Sabre_DAV_Property {

    /**
     * The list of users a calendar has been shared to.
     *
     * @var array
     */
    protected $users;

    /**
     * Creates the property.
     *
     * Users is an array. Each element of the array has the following
     * properties:
     *
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first and lastname for a user.
     *   * status - One of the SharingPlugin::STATUS_* constants.
     *   * readOnly - true or false
     *   * summary - Optional, description of the share
     *
     * @param array $users
     */
    public function __construct(array $users) {

        $this->users = $users;

    }

    /**
     * Serializes the property in a DOMDocument
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $node) {

       $doc = $node->ownerDocument;
       foreach($this->users as $user) {

           $xuser = $doc->createElement('cs:user');

           $href = $doc->createElement('d:href');
           $href->appendChild($doc->createTextNode($user['href']));
           $xuser->appendChild($href);

           if (isset($user['commonName']) && $user['commonName']) {
               $commonName = $doc->createElement('cs:common-name');
               $commonName->appendChild($doc->createTextNode($user['commonName']));
               $xuser->appendChild($commonName);
           }

           switch($user['status']) {

               case SharingPlugin::STATUS_ACCEPTED :
                   $status = $doc->createElement('cs:invite-accepted');
                   $xuser->appendChild($status);
                   break;
               case SharingPlugin::STATUS_DECLINED :
                   $status = $doc->createElement('cs:invite-declined');
                   $xuser->appendChild($status);
                   break;
               case SharingPlugin::STATUS_NORESPONSE :
                   $status = $doc->createElement('cs:invite-noresponse');
                   $xuser->appendChild($status);
                   break;
               case SharingPlugin::STATUS_INVALID :
                   $status = $doc->createElement('cs:invite-invalid');
                   $xuser->appendChild($status);
                   break;

           }

           if ($user['readOnly']) {
                $xuser->appendChild(
                    $doc->createElement('cs:read')
                );
           } else {
                $xuser->appendChild(
                    $doc->createElement('cs:read-write')
                );
           }

           if (isset($user['summary']) && $user['summary']) {
               $summary = $doc->createElement('cs:summary');
               $summary->appendChild($doc->createTextNode($user['summary']));
               $xuser->appendChild($summary);
           }

           $node->appendChild($xuser);

       }

    }

}
