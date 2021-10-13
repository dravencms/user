<?php declare(strict_types = 1);
namespace Dravencms\AdminModule\UserModule;

/*
 * Copyright (C) 2013 Adam Schubert <adam.schubert@winternet.cz>.
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

use Dravencms\AdminModule\Components\User\GroupForm\GroupForm;
use Dravencms\AdminModule\Components\User\GroupForm\GroupFormFactory;
use Dravencms\AdminModule\Components\User\GroupGrid\GroupGrid;
use Dravencms\AdminModule\Components\User\GroupGrid\GroupGridFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Model\User\Entities\Group;
use Dravencms\Model\User\Repository\GroupRepository;

/**
 * Description of RolePresenter
 *
 * @author Adam Schubert <adam.schubert@winternet.cz>
 */
class GroupPresenter extends SecuredPresenter
{
    /** @var GroupRepository @inject */
    public $userGroupRepository;

    /** @var GroupFormFactory @inject */
    public $groupFormFactory;

    /** @var GroupGridFactory @inject */
    public $groupGridFactory;
    
    /** @var Group|null */
    private $userGroupFormEntity = null;

    /**
     * @isAllowed(user,edit)
     */
    public function actionDefault(): void
    {
        $this->template->h1 = 'Skupiny';
    }

    /**
     * @param integer $id
     * @isAllowed(user,edit)
     */
    public function actionEdit(int $id): void
    {
        if ($id) {
            $group = $this->userGroupRepository->getOneById($id);
            if (!$group) {
                $this->error();
            }

            $this->userGroupFormEntity = $group;

            $this->template->h1 = 'Editace skupiny „' . $group->getName() . '“';
        } else {
            $this->template->h1 = 'Nová skupina';
        }
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\GroupGrid\GroupGrid
     */
    public function createComponentGridGroup(): GroupGrid
    {
        $control = $this->groupGridFactory->create();
        $control->onDelete[] = function(){
            $this->flashMessage('Group has been successfully deleted', 'alert-success');
            $this->redirect('Group:');
        };
        return $control;
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\GroupForm\GroupForm
     */
    public function createComponentFormGroup(): GroupForm
    {
        $control = $this->groupFormFactory->create($this->userGroupFormEntity);
        $control->onSuccess[] = function(){
            $this->flashMessage('Group has been successfully saved', 'alert-success');
            $this->redirect('Group:');
        };

        return $control;
    }

}
