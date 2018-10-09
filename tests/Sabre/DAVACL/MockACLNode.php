<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;

class MockACLNode extends DAV\Node implements IACL
{
    public $name;
    public $acl;

    public function __construct($name, array $acl = [])
    {
        $this->name = $name;
        $this->acl = $acl;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getOwner()
    {
        return null;
    }

    public function getGroup()
    {
        return null;
    }

    public function getACL()
    {
        return $this->acl;
    }

    public function setACL(array $acl)
    {
        $this->acl = $acl;
    }

    public function getSupportedPrivilegeSet()
    {
        return null;
    }
}
