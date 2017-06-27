<?php
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
use Dravencms\AdminModule\Components\User\AclOperationForm\AclOperationFormFactory;
use Dravencms\AdminModule\Components\User\AclOperationGrid\AclOperationGridFactory;
use Dravencms\AdminModule\Components\User\AclResourceForm\AclResourceFormFactory;
use Dravencms\AdminModule\Components\User\AclResourceGrid\AclResourceGridFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Dravencms\Model\User\Repository\AclResourceRepository;

/**
 * Description of AclPresenter
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class AclPresenter extends SecuredPresenter
{
    /** @var AclResourceGridFactory @inject */
    public $aclResourceGridFactory;

    /** @var AclOperationGridFactory @inject */
    public $aclOperationGridFactory;

    /** @var AclResourceFormFactory @inject */
    public $aclResourceEditFormFactory;

    /** @var AclOperationFormFactory @inject */
    public $aclOperationEditFormFactory;

    /** @var AclResourceRepository @inject */
    public $aclResourceRepository;

    /** @var AclOperationRepository @inject */
    public $aclOperationRepository;

    /** @var AclResource */
    protected $aclResource = null;

    /** @persistent */
    public $aclResourceId;

    /** @var AclOperation|null */
    private $aclOperation = null;

    /**
     * @isAllowed(user,edit)
     */
    public function actionDefault()
    {
        $this->template->h1 = 'ACL';
    }

    /**
     * @param integer|null $id
     * @isAllowed(user,edit)
     */
    public function actionEdit($id = null)
    {
        if ($id) {
            $aclResource = $this->aclResourceRepository->getOneById($id);
            if (!$aclResource) {
                $this->error('ACL resource not found!');
            }

            $this->template->h1 = 'Editace resource „' . $aclResource->getName() . '“';

            $this->aclResource = $aclResource;
        } else {
            $this->template->h1 = 'Nový resource';
        }
    }

    /**
     * @param integer|null $id
     * @isAllowed(user,edit)
     */
    public function actionEditOperation($id = null)
    {
        $aclResource = $this->aclResourceRepository->getOneById($this->aclResourceId);
        if (!$aclResource)
        {
            $this->error('Resource not found!');
        }

        $this->aclResource = $aclResource;

        if ($id) {
            $aclOperation = $this->aclOperationRepository->getOneById($id);
            if (!$aclOperation) {
                $this->error('ACL Operation not found!');
            }

            $this->aclOperation = $aclOperation;

            $this->template->h1 = 'Editace operation „' . $aclOperation->getName() . '“';
        } else {
            $this->template->h1 = 'Nový operation';
        }
    }

    /**
     * @param integer|null $id
     * @isAllowed(user,edit)
     */
    public function actionOperation($id)
    {

        $aclResource = $this->aclResourceRepository->getOneById($id);
        if (!$aclResource)
        {
            $this->error('Resource not found!');
        }

        $this->template->h1 = 'ACL '.$aclResource->getName();

        $this->aclResource = $aclResource;
        $this->aclResourceId = $aclResource->getId();
    }

    /**
     * @return \AdminModule\Components\User\AclResourceGrid
     */
    public function createComponentGridResource()
    {
        $control = $this->aclResourceGridFactory->create();
        $control->onDelete[] = function()
        {
            $this->flashMessage('Resource has been successfully deleted', 'alert-success');
            $this->redirect('Acl:');
        };
        return $control;
    }

    /**
     * @return \AdminModule\Components\User\AclResourceForm
     */
    public function createComponentFormResource()
    {
        $control = $this->aclResourceEditFormFactory->create($this->aclResource);
        $control->onSuccess[] = function()
        {
            $this->flashMessage('Resource has been successfully saved', 'alert-success');
            $this->redirect('Acl:');
        };
        return $control;
    }

    /**
     * @return \AdminModule\Components\User\AclOperationGrid
     */
    public function createComponentGridOperation()
    {
        $control = $this->aclOperationGridFactory->create($this->aclResource);
        $control->onDelete[] = function($aclResource)
        {
            $this->flashMessage('Operation has been successfully deleted', 'alert-success');
            $this->redirect('Acl:operation', $aclResource->getId());
        };
        return $control;
    }

    /**
     * @return \AdminModule\Components\User\AclOperationForm
     */
    public function createComponentFormOperation()
    {
        $control = $this->aclOperationEditFormFactory->create($this->aclResource, $this->aclOperation);
        $control->onSuccess[] = function($aclResource)
        {
            $this->flashMessage('Operation has been successfully saved', 'alert-success');
            $this->redirect('Acl:operation', $aclResource->getId());
        };
        return $control;
    }
}
