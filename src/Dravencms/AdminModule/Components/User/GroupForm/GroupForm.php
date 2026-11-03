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
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\User\Entities\Group;
use Dravencms\Model\User\Repository\GroupRepository;
use Dravencms\User\Acl\AclRegistry;
use Dravencms\User\Acl\AclGrantStore;
use Dravencms\Components\BaseForm\Form;
use Nette\Security\User;

/**
 * Description of SearchForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class GroupForm extends BaseControl
{

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var GroupRepository */
    private $groupRepository;

    /** @var AclRegistry */
    private $registry;

    /** @var AclGrantStore */
    private $grantStore;

    /** @var User */
    private $user;

    /** @var Group */
    private $group = null;

    public $onSuccess = [];

    /**
     * GroupForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param AclRegistry $registry
     * @param GroupRepository $groupRepository
     * @param AclGrantStore $grantStore
     * @param User $user
     * @param Group|null $group
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        AclRegistry $registry,
        GroupRepository $groupRepository,
        AclGrantStore $grantStore,
        User $user,
        ?Group $group = null
    ) {
        $this->baseFormFactory = $baseFormFactory;
        $this->groupRepository = $groupRepository;
        $this->registry = $registry;
        $this->grantStore = $grantStore;
        $this->user = $user;
        $this->group = $group;
    }

    /**
     * @return Form
     */
    protected function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name')
            ->setRequired('user.admin.validation.nameRequired');

        $form->addTextArea('description')
            ->setRequired('user.admin.validation.descriptionRequired');

        $form->addText('color')
            ->setRequired('user.admin.validation.colorRequired')
            ->addRule(Form::PATTERN, 'user.admin.validation.colorInvalid', '^[0-9a-f]{3,6}$');

        $choices = [];
        foreach ($this->registry->getPermissions() as $id => $permission) { $choices[$id] = $permission->label; }
        $form->addCheckboxList('aclOperation', null, $choices);

        $form->addCheckbox('isRegister');

        $form->addSubmit('send', 'user.admin.action.save');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];
        if ($this->group) {
            $aclOperationIds = $this->grantStore->getExplicitKeys($this->group);

            $form->setDefaults([
                'name' => $this->group->getName(),
                'description' => $this->group->getDescription(),
                'color' => $this->group->getColor(),
                'aclOperation' => $aclOperationIds,
                'isRegister' => $this->group->isRegister(),
            ]);
        }

        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();
        if (!$this->groupRepository->isNameFree($values->name, $this->group)) {
            $form->addError('user.admin.validation.groupNameTaken');
        }

        try { $this->registry->resolve($form['aclOperation']->getRawValue()); }
        catch (\InvalidArgumentException) { $form->addError('user.acl.invalidPermission'); }

        if (!$this->user->isAllowed('user', 'edit')) {
            $form->addError('user.admin.validation.groupForbidden');
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


        $this->grantStore->assign($group, $values->aclOperation);

        $this->onSuccess();
    }

    public function getPermissionView(): array
    {
        $form = $this->getComponent('form');
        $translator = $form->getTranslator();
        $definitions = $this->registry->getPermissions();
        $explicit = array_values(array_intersect($form['aclOperation']->getValue(), array_keys($definitions)));
        $groups = []; $labels = []; $graph = [];
        $resourceLabels = $this->registry->getResourceLabels();
        foreach ($definitions as $id => $permission) {
            $groups[$permission->resource]['label'] = $resourceLabels[$permission->resource];
            $groups[$permission->resource]['permissions'][] = $permission;
            $labels[$id] = $translator !== null ? $translator->translate($permission->label) : $permission->label;
            $graph[$id] = $permission->requires;
        }
        $requiredBy = [];
        foreach ($this->registry->getRequiredBy($explicit) as $id => $parents) {
            $requiredBy[$id] = array_map(static fn(string $parent): string => $labels[$parent], $parents);
        }
        return ['field' => $form['aclOperation'], 'groups' => $groups, 'explicit' => $explicit, 'effective' => $this->registry->resolve($explicit), 'requiredBy' => $requiredBy, 'labels' => $labels, 'graph' => $graph];
    }

    public function render(): void
    {
        $template = $this->template;
        $template->permissionView = $this->getPermissionView();
        $template->setFile(__DIR__ . '/GroupForm.latte');
        $template->render();
    }
}
