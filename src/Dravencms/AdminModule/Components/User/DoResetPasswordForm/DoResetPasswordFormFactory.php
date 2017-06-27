<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\DoResetPasswordForm;


use Dravencms\Model\User\Entities\PasswordReset;

interface DoResetPasswordFormFactory
{
    /**
     * @param PasswordReset $passwordReset
     * @return DoResetPasswordForm
     */
    public function create(PasswordReset $passwordReset);
}