<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Latte\User\Filters;


/**
 * Class User
 * @package Latte\Filters
 */
class User
{
    /**
     * @param $user
     * @return mixed
     */
    public function formatUserName(\Dravencms\Model\User\Entities\User $user)
    {
        $parts = [];
        if ($user->getDegree()) {
            $parts[] = $user->getDegree();
        }
        $parts[] = $user->getFirstName();
        $parts[] = $user->getLastName();

        return implode(' ', $parts);
    }
}