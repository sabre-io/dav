<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\DAV;
use Sabre\VObject\ITip;

/**
 * iMIP handler.
 *
 * This class is responsible for sending out iMIP messages. iMIP is the
 * email-based transport for iTIP. iTIP deals with scheduling operations for
 * iCalendar objects.
 *
 * If you want to customize the email that gets sent out, you can do so by
 * extending this class and overriding the sendMessage method.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class IMipPlugin extends DAV\ServerPlugin {

    /**
     * Email address used in From: header.
     *
     * @var string
     */
    protected $senderEmail;

    /**
     * ITipMessage
     *
     * @var ITip\Message
     */
    protected $itipMessage;
    
    /**
     * Domain validation : not sending a email message
     *
     * @var string
     */
    protected $_domain;

    /**
     * Boundary for differnet mime types in the email
     *
     * @var string
     */
    protected $_boundary;

    /**
     * Creates the email handler.
     *
     * @param string $senderEmail. The 'senderEmail' is the email that shows up
     *                             in the 'From:' address. This should
     *                             generally be some kind of no-reply email
     *                             address you own.
     */
    function __construct($senderEmail, $_domain) {

        $this->senderEmail = $senderEmail;
        $this->_domain = $_domain;
        $this->_boundary = '_2fe289d536817e04cccae0926c50bc85de376453';
        
    }

    /*
     * This initializes the plugin.
     *
     * This function is called by Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param DAV\Server $server
     * @return void
     */
    function initialize(DAV\Server $server) {

        $server->on('schedule', [$this, 'schedule'], 120);

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

        return 'imip';

    }

    /**
     * Event handler for the 'schedule' event.
     *
     * @param ITip\Message $iTipMessage
     * @return void
     */
    function schedule(ITip\Message $iTipMessage) {

        // Not sending any emails if the system considers the update
        // insignificant.
        if (!$iTipMessage->significantChange) {
            if (!$iTipMessage->scheduleStatus) {
                $iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
            }
            return;
        }

        $summary = $iTipMessage->message->VEVENT->SUMMARY;

        if (parse_url($iTipMessage->sender, PHP_URL_SCHEME) !== 'mailto')
            return;

        if (parse_url($iTipMessage->recipient, PHP_URL_SCHEME) !== 'mailto')
            return;

        $sender = substr($iTipMessage->sender, 7);
        $recipient = substr($iTipMessage->recipient, 7);

        // $sender is a valid email address (simple check), if not standard email address is used
        if (preg_match('/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9.-]+.[a-zA-Z]+$/', $sender) == 0)
            $sender = $this->senderEmail;
        // participant is in namespace of $_domain, no email would be send
        if (preg_match('/' . $this->_domain  . '/', $recipient) > 0)
            return;

        if ($iTipMessage->senderName) {
            $sender = $iTipMessage->senderName . ' <' . $sender . '>';
        }
        if ($iTipMessage->recipientName) {
            $recipient = $iTipMessage->recipientName . ' <' . $recipient . '>';
        }

        $subject = 'SabreDAV iTIP message';
        switch (strtoupper($iTipMessage->method)) {
            case 'REPLY' :
                $subject = 'Re: ' . $summary;
                break;
            case 'REQUEST' :
                $subject = $summary;
                break;
            case 'CANCEL' :
                $subject = 'Cancelled: ' . $summary;
                break;
        }

        // new header :: new sender :: new mime type
        $headers = [
            'Reply-To: ' . $sender,
            'From: ' . $sender,
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="----=' . $this->_boundary . '"',
            'Content-class: urn:content-classes:calendarmessage',
        ];
        if (DAV\Server::$exposeVersion) {
            $headers[] = 'X-Sabre-Version: ' . DAV\Version::VERSION;
        }
        // new message :: add simple text/plain content of the invitation
        $this->mail(
            $recipient,
            $subject,
            $this->getMessage($iTipMessage),
            $headers
        );
        $iTipMessage->scheduleStatus = '1.1; Scheduling message is sent via iMip';

    }

    // @codeCoverageIgnoreStart
    // This is deemed untestable in a reasonable manner

    /**
     * This function is responsible for sending the actual email.
     *
     * @param string $to Recipient email address
     * @param string $subject Subject of the email
     * @param string $body iCalendar body
     * @param array $headers List of headers
     * @return void
     */
    protected function mail($to, $subject, $body, array $headers) {

        mail($to, $subject, $body, implode("\r\n", $headers));

    }

    /**
     * This function generate the email content with a simple text abstract of the invitation.
     *
     * @param string $iTipMessage
     * @return string
     */
    function getMessage($iTipMessage) {

        // Text message
        $message = '------=' . $this->_boundary . "\n";   
        $message .= 'Content-Type: text/plain; charset=UTF-8' . "\n";
        $message .= 'Content-Transfer-Encoding: 8bit' . "\n\n";
        
        $message .= 'Meeting with ' . $iTipMessage->senderName . "\n";
        $message .= '"' . $iTipMessage->message->VEVENT->SUMMARY . '"' . "\n\n"; 
        $time = [
            $iTipMessage->message->VEVENT->DTSTART->getDateTime()->format("l\, jS F Y"),
            $iTipMessage->message->VEVENT->DTSTART->getDateTime()->format("g\.i A"),
            $iTipMessage->message->VEVENT->DTEND->getDateTime()->format("l\, jS F Y"),
            $iTipMessage->message->VEVENT->DTEND->getDateTime()->format("g\.i A")
        ]; 
        if ($time[0] == $time[2]) {
            $message .= $time[0] . ' ' . $time[1] . ' - ' . $time[3];
        } else {
            $message .= $time[0] . ' ' . $time[1];
            $message .= ' - ';
            $message .= $time[2] . ' ' . $time[3];
        }
        $message .= "\n";
        
        // not implemented :: location :: description
        $message .= "\n\n";

        // Calendar object
        $message .= '------=' . $this->_boundary . "\n";
        $message .= 'Content-Type: text/calendar; name="meeting.ics"; method=' . $iTipMessage->method . "\n";
        $message .= 'Content-Transfer-Encoding: 8bit' . "\n\n";
        $message .= $iTipMessage->message->serialize() . "\n";

        $message .= '------=' . $this->_boundary . '--'; 

        return $message;

    }

    // @codeCoverageIgnoreEnd

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Email delivery (rfc6037) for CalDAV scheduling',
            'link'        => 'http://sabre.io/dav/scheduling/',
        ];

    }

}
