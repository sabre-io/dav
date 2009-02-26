<?php

class Sabre_DAV_Property_LockDiscovery extends Sabre_DAV_Property {

    public $locks;
    public $revealLockToken;

    function __construct($locks,$revealLockToken = false) {

        $this->locks = $locks;
        $this->revealLockToken = $revealLockToken;

    }

    function serialize(DOMElement $prop) {

        $doc = $prop->ownerDocument;

        foreach($this->locks as $lock) {

            $activeLock = $doc->createElementNS('DAV:','d:activelock');
            $prop->appendChild($activeLock);

            $lockScope = $doc->createElementNS('DAV:','d:lockscope');
            $activeLock->appendChild($lockScope);

            $lockScope->appendChild($doc->createElementNS('DAV:','d:' . ($lock->scope==Sabre_DAV_Lock::EXCLUSIVE?'exclusive':'shared')));

            $lockType = $doc->createElementNS('DAV:','d:locktype');
            $activeLock->appendChild($lockType);

            $lockType->appendChild($doc->createElementNS('DAV:','d:write'));

            $activeLock->appendChild($doc->createElementNS('DAV:','d:depth',($lock->depth == Sabre_DAV_Server::DEPTH_INFINITY?'infinity':$lock->depth)));
            $activeLock->appendChild($doc->createElementNS('DAV:','d:timeout','Second-' . $lock->timeout));

            if ($this->revealLockToken) {
                $lockToken = $doc->createElementNS('DAV:','d:locktoken');
                $activeLock->appendChild($lockToken);
                $lockToken->appendChild($doc->createElementNS('DAV:','d:href','opaquelocktoken:' . $lock->token));
            }
           
            $activeLock->appendChild($doc->createElementNS('DAV:','d:owner',$lock->owner));

        }

    }

}

?>
