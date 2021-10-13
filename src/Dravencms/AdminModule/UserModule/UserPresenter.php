<?php declare(strict_types = 1);

namespace Dravencms\AdminModule\UserModule;

/*
 * Copyright (C) 2013 Adam Schubert <adam.schubert@sg1-game.net>.
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

use Dravencms\AdminModule\Components\User\UserForm\UserForm;
use Dravencms\AdminModule\Components\User\UserForm\UserFormFactory;
use Dravencms\AdminModule\Components\User\UserGrid\UserGrid;
use Dravencms\AdminModule\Components\User\UserGrid\UserGridFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\UserRepository;

/**
 * Description of UsersPresenter
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class UserPresenter extends SecuredPresenter
{
    /** @var UserRepository @inject */
    public $userUserRepository;

    /** @var UserGridFactory @inject */
    public $userUserGridFactory;

    /** @var UserFormFactory @inject */
    public $userUserFormFactory;

    /** @var User|null */
    private $userFormEntity = null;
    
    
    /**
     * @isAllowed(user, edit)
     */
    public function actionDefault(): void
    {
        $this->template->h1 = 'Uživatelé';
    }

    /**
     * @param integer $id
     * @isAllowed(user, edit)
     * @throws \Exception
     */
    public function actionEdit(int $id = null): void
    {
        if ($id) {
            $user = $this->userUserRepository->getOneById($id);
            if (!$user) {
                $this->error();
            }

            $this->userFormEntity = $user;
            $this->template->h1 = 'Editace uživatele „' . $user->getFirstName() . ' ' . $user->getLastName() . '“';

        } else {
            $this->template->h1 = 'New user';
        }
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\UserForm\UserForm
     */
    public function createComponentFormUser(): UserForm
    {
        $control = $this->userUserFormFactory->create($this->userFormEntity);
        $control->onSuccess[] = function()
        {
            // If editing my self, log out
            if ($this->getUserEntity() == $this->userFormEntity) {
                $this->getUser()->logout();
                $this->flashMessage('Changes has been made to Your account. You have been signed out.', 'alert-info');
                $this->redirect('Sign:in');
            }
            else
            {
                $this->flashMessage('Changes has been saved.', 'alert-success');
                $this->redirect('User:');
            }
        };
        return $control;
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\UserGrid\UserGrid
     */
    public function createComponentGridUser(): UserGrid
    {
        $control = $this->userUserGridFactory->create();
        $control->setNamespace($this->getNamespace());
        $control->onDelete[] = function()
        {
            $this->flashMessage('User has been successfully deleted.', 'alert-success');
            $this->redirect('User:');
        };
        return $control;
    }
}
