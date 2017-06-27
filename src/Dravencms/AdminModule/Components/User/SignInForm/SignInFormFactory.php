<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\SignInForm;


interface SignInFormFactory
{
    /** @return SignInForm */
    public function create();
}