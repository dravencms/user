<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Latte\User\Filters;


use Dravencms\Security\UserAcl;

/**
 * Class User
 * @package Latte\Filters
 */
class User
{
    private $userAcl;

    public function __construct(UserAcl $userAcl)
    {
        $this->userAcl = $userAcl;
    }


    /**
     * @param $user
     * @return string
     */
    public function formatUserName(\Dravencms\Model\User\Entities\User $user): string
    {
        $parts = [];
        if ($user->getDegree()) {
            $parts[] = $user->getDegree();
        }
        $parts[] = $user->getFirstName();
        $parts[] = $user->getLastName();

        return implode(' ', $parts);
    }

    /**
     * @param string $resource
     * @param string $operation
     * @param string|null $role
     * @return bool
     */
    public function isAllowed(string $resource, string $operation, string $role = null) : bool
    {
        return $this->userAcl->isAllowed($resource, $operation, $role);
    }

    /**
     * @return UserAcl
     */
    public function getUserAclService() : UserAcl
    {
        return $this->userAcl;
    }
}