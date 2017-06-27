<?php

namespace Dravencms\AdminModule\Components\User\UserForm;

use Dravencms\Model\User\Entities\User;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
interface UserFormFactory
{
    /**
     * @param User|null $user
     * @return UserForm
     */
    public function create(User $user = null);
}