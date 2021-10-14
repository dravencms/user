<?php declare(strict_types = 1);
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

namespace Dravencms\AdminModule\Components\User\AclResourceForm;

use Dravencms\Components\BaseForm\BaseForm;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\User\Repository\AclResourceRepository;
use Dravencms\Database\EntityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

/**
 * Description of AclResourceEditForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class AclResourceForm extends Control
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var AclResourceRepository */
    private $aclResourceRepository;

    /** @var AclResource */
    private $aclResource;

    /** @var array */
    public $onSuccess = [];

    /**
     * AclResourceForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param AclResourceRepository $aclResourceRepository
     * @param AclResource|null $aclResource
     */
    public function __construct(BaseFormFactory $baseFormFactory, EntityManager $entityManager, AclResourceRepository $aclResourceRepository, AclResource $aclResource = null)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->aclResourceRepository = $aclResourceRepository;
        $this->aclResource = $aclResource;

        if ($this->aclResource)
        {
            $this['form']->setDefaults(
                [
                    'name' => $this->aclResource->getName(),
                    'description' => $this->aclResource->getDescription()
                ]
            );
        }
    }

    /**
     * @return Form
     */
    protected function createComponentForm(): Form
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

    /**
     * @param Form $form
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();
        if (!$this->aclResourceRepository->isNameFree($values->name, $this->aclResource))
        {
            $form->addError('Name is not free');
        }

        if (!$this->presenter->isAllowed('user', 'edit')) {
            $form->addError('Nemáte oprávění editovat ACL.');
        }
    }

    /**
     * @param Form $form
     * @throws \Nette\Application\AbortException
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();

        if ($this->aclResource)
        {
            $aclResource = $this->aclResource;
            $aclResource->setName($values->name);
            $aclResource->setDescription($values->description);
        }
        else
        {
            $aclResource = new AclResource(
                $values->name, $values->description);
        }


        $this->entityManager->persist($aclResource);

        $this->entityManager->flush();

        $this->onSuccess();
        $this->redirect('this');
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/AclResourceForm.latte');
        $template->render();
    }
}