<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;

class MockPrincipal extends DAV\Node implements IPrincipal
{
    public $name;
    public $principalUrl;
    public $groupMembership = [];
    public $groupMemberSet = [];

    public function __construct($name, $principalUrl, array $groupMembership = [], array $groupMemberSet = [])
    {
        $this->name = $name;
        $this->principalUrl = $principalUrl;
        $this->groupMembership = $groupMembership;
        $this->groupMemberSet = $groupMemberSet;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDisplayName()
    {
        return $this->getName();
    }

    public function getAlternateUriSet()
    {
        return [];
    }

    public function getPrincipalUrl()
    {
        return $this->principalUrl;
    }

    public function getGroupMemberSet()
    {
        return $this->groupMemberSet;
    }

    public function getGroupMemberShip()
    {
        return $this->groupMembership;
    }

    public function setGroupMemberSet(array $groupMemberSet)
    {
        $this->groupMemberSet = $groupMemberSet;
    }
}
