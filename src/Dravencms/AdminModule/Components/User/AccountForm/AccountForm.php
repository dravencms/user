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

namespace Dravencms\AdminModule\Components\User\AccountForm;

use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Dravencms\Model\User\Repository\UserRepository;
use Dravencms\Database\EntityManager;
use Nette\Application\UI\Control;
use Dravencms\Components\BaseForm\Form;

/**
 * Description of AccountForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class AccountForm extends Control
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var AclOperationRepository */
    private $userRepository;

    /** @var User */
    private $user;

    public $onSuccess = [];

    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        UserRepository $userRepository,
        User $user
    ) {
        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->user = $user;

        if ($this->user)
        {
            $this['form']->setDefaults([
                'firstName' => $this->user->getFirstName(),
                'lastName' => $this->user->getLastName(),
                'email' => $this->user->getEmail()
            ]);
        }
    }


    /**
     * @return Form
     */
    protected function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('firstName')
            ->setRequired('Prosím zadejte jméno.');

        $form->addText('lastName')
            ->setRequired('Prosím zadejte příjmení.');

        $form->addText('email')
            ->setHtmlType('email')
            ->addRule(Form::EMAIL, 'Prosím zadejte email.')
            ->setRequired('Prosím zadejte email.');
        
        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'editFormValidate'];
        $form->onSuccess[] = [$this, 'editFormSucceeded'];
        return $form;
    }

    /**
     * @param Form $form
     */
    public function editFormValidate(Form $form): void
    {
        $values = $form->getValues();
        if (!$this->userRepository->isEmailFree($values->email, $this->user->getNamespace(), $this->user)) {
            $form->addError('Tento email je již zabrán.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();

        $this->user->setFirstName($values->firstName);
        $this->user->setLastName($values->lastName);
        $this->user->setEmail($values->email);

        $this->entityManager->persist($this->user);

        $this->entityManager->flush();

        $this->onSuccess();
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/AccountForm.latte');
        $template->render();
    }
}