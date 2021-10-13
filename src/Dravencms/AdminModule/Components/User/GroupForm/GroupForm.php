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

namespace Dravencms\AdminModule\Components\User\GroupForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseForm;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\User\Entities\Group;
use Dravencms\Model\User\Repository\GroupRepository;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Dravencms\Model\User\Repository\AclResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Dravencms\Database\EntityManager;
use Nette\Application\UI\Form;

/**
 * Description of SearchForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class GroupForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var GroupRepository */
    private $groupRepository;

    /** @var AclOperationRepository */
    private $aclOperationRepository;

    /** @var AclResourceRepository */
    private $aclResourceRepository;

    /** @var Group */
    private $group = null;

    public $onSuccess = [];

    /**
     * GroupForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param EntityManager $entityManager
     * @param AclOperationRepository $aclOperationRepository
     * @param GroupRepository $groupRepository
     * @param AclResourceRepository $aclResourceRepository
     * @param Group|null $group
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        AclOperationRepository $aclOperationRepository,
        GroupRepository $groupRepository,
        AclResourceRepository $aclResourceRepository,
        Group $group = null
    ) {
        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->groupRepository = $groupRepository;
        $this->aclOperationRepository = $aclOperationRepository;
        $this->aclResourceRepository = $aclResourceRepository;
        $this->group = $group;

        if ($this->group)
        {
            $aclOperationIds = [];

            foreach ($this->group->getAclOperations() AS $aclOperation)
            {
                $aclOperationIds[$aclOperation->getId()] = $aclOperation->getId();
            }

            $this['form']->setDefaults([
                'name' => $this->group->getName(),
                'description' => $this->group->getDescription(),
                'color' => $this->group->getColor(),
                'aclOperation' => $aclOperationIds,
                'isRegister' => $this->group->isRegister()
            ]);
        }
    }

    /**
     * @return \Dravencms\Components\BaseForm\BaseForm
     */
    protected function createComponentForm(): BaseForm
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name')
            ->setRequired('Prosím zadejte název.');

        $form->addTextArea('description')
            ->setRequired('Prosím zadejte popis.');

        $form->addText('color')
            ->setRequired('Prosím zadejte barvu.')
            ->addRule(Form::PATTERN, 'Barva musi byt zadana v HEX formatu', '^[0-9a-f]{3,6}$');

        $aclOperationOptions = [];
        foreach ($this->aclResourceRepository->getAll() AS $aclResource)
        {
            $aclOperations = [];
            foreach ($aclResource->getAclOperations() AS $aclOperation)
            {
                $aclOperations[$aclOperation->getId()] = $aclResource->getName().'-'.$aclOperation->getName();
            }

            $aclOperationOptions[$aclResource->getName()] = $aclOperations;
        }

        $form->addMultiSelect('aclOperation', null, $aclOperationOptions);

        $form->addCheckbox('isRegister');

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
        if (!$this->groupRepository->isNameFree($values->name, $this->group)) {
            $form->addError('Tento název skupiny je již zabrán.');
        }

        if (!$this->presenter->isAllowed('user', 'edit')) {
            $form->addError('Nemáte oprávění editovat ACL skupiny.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();

        if ($this->group) {
            $group = $this->group;
            $group->setName($values->name);
            $group->setColor($values->color);
            $group->setDescription($values->description);
            $group->setIsRegister($values->isRegister);
        } else {
            $group = new Group(
                $values->name,
                $values->description,
                $values->color,
                $values->isRegister
            );
        }


        $operations = new ArrayCollection($this->aclOperationRepository->getById($values->aclOperation));

        $group->setAclOperations($operations);


        $this->entityManager->persist($group);

        $this->entityManager->flush();

        $this->onSuccess();
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/GroupForm.latte');
        $template->render();
    }
}
