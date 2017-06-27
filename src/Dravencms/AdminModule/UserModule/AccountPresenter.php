<?php
namespace Dravencms\AdminModule\UserModule;

/*
 * Copyright (C) 2014 sadam.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */
use Dravencms\AdminModule\Components\User\AccountForm\AccountFormFactory;
use Dravencms\AdminModule\Components\User\AccountPasswordForm\AccountPasswordFormFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Model\User\Repository\UserRepository;

/**
 * Description of AccountPresenter
 *
 * @author sadam
 */
class AccountPresenter extends SecuredPresenter
{
    /** @var UserRepository @inject */
    public $userUserRepository;

    /** @var AccountFormFactory @inject */
    public $accountFormFactory;

    /** @var AccountPasswordFormFactory @inject */
    public $accountPasswordFormFactory;

    public function renderDefault()
    {
        $user = $this->getUserEntity();
        $this->template->h1 = 'Uživatel „' . $user->getFirstName() . ' ' . $user->getLastName() . '“';
        $this->template->userInfo = $user;
    }

    /**
     * @return \AdminModule\Components\User\AccountForm
     */
    public function createComponentFormAccount()
    {
        $control = $this->accountFormFactory->create($this->getUserEntity());
        $control->onSuccess[] = function()
        {
            $this->flashMessage('Změny byly uloženy.', 'alert-success');
            $this->redirect('Account:');
        };
        return $control;
    }

    /**
     * @return \AdminModule\Components\User\AccountPasswordForm
     */
    public function createComponentFormAccountPassword()
    {
        $control = $this->accountPasswordFormFactory->create($this->getUserEntity());
        $control->onSuccess[] = function()
        {
            $this->flashMessage('Heslo bylo změněno a byli jste odhlášeni, prosím přihlašte se novým heslem.', 'alert-success');
            $this->getUser()->logout();
            $this->redirect('Account:#password-change');
        };
        return $control;
    }

}
