<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\UserGrid;


interface UserGridFactory
{
    /**
     * @return UserGrid
     */
    public function create(): UserGrid;
}