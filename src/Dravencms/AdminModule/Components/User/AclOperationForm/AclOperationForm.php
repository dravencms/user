<?php
/*
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
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

namespace Dravencms\AdminModule\Components\User\AclOperationForm;

use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

/**
 * Description of AclResourceEditForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class AclOperationForm extends Control
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var AclOperationRepository */
    private $aclOperationRepository;

    /** @var AclResource */
    private $aclResource;

    /** @var AclOperation */
    private $aclOperation;

    /** @var array */
    public $onSuccess = [];


    public function __construct(AclResource $aclResource, BaseFormFactory $baseFormFactory, EntityManager $entityManager, AclOperationRepository $aclOperationRepository, AclOperation $aclOperation = null)
    {
        parent::__construct();

        $this->aclResource = $aclResource;

        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->aclOperationRepository = $aclOperationRepository;
        $this->aclOperation = $aclOperation;


        if ($this->aclOperation)
        {
            $this['form']->setDefaults(
                [
                    'name' => $this->aclOperation->getName(),
                    'description' => $this->aclOperation->getDescription()
                ]
            );
        }
    }

    protected function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name')
            ->setRequired('Prosím zadejte název.');

        $form->addTextArea('description')
            ->setRequired('Prosím zadejte název.');

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        return $form;
    }

    public function editFormValidate(Form $form)
    {
        $values = $form->getValues();
        if (!$this->aclOperationRepository->isNameFree($values->name, $this->aclResource, $this->aclOperation)) {
            $form->addError('Tento název je již zabrán.');
        }

        if (!$this->presenter->isAllowed('user', 'edit')) {
            $form->addError('Nemáte oprávění editovat ACL.');
        }
    }

    public function editFormSucceeded(Form $form)
    {
        $values = $form->getValues();

        if ($this->aclOperation)
        {
            $aclOperation = $this->aclOperation;
            $aclOperation->setName($values->name);
            $aclOperation->setDescription($values->description);
        }
        else
        {
            $aclOperation = new AclOperation($this->aclResource, $values->name, $values->description);
        }


        $this->entityManager->persist($aclOperation);

        $this->entityManager->flush();

        $this->onSuccess($this->aclResource);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/AclOperationForm.latte');
        $template->render();
    }
}