<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\ResetPasswordForm;


interface ResetPasswordFormFactory
{
    /** @return ResetPasswordForm */
    public function create();
}