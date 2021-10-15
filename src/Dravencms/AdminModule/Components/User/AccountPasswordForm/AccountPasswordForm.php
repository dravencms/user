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

namespace Dravencms\AdminModule\Components\User\AccountPasswordForm;

use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Security\PasswordManager;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Dravencms\Model\User\Repository\UserRepository;
use Dravencms\Database\EntityManager;
use Nette\Application\UI\Control;
use Dravencms\Components\BaseForm\Form;

/**
 * Description of AccountPasswordForm
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class AccountPasswordForm extends Control
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var AclOperationRepository */
    private $userRepository;

    /** @var PasswordManager */
    private $passwordManager;

    /** @var User */
    private $user;

    public $onSuccess = [];

    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManager $entityManager,
        UserRepository $userRepository,
        PasswordManager $passwordManager,
        User $user
    ) {
        $this->baseFormFactory = $baseFormFactory;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordManager = $passwordManager;
        $this->user = $user;
    }

    /**
     * @return Form
     */
    protected function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addPassword('oldPassword')
            ->setRequired('Zadejte prosím staré heslo.');

        $form->addPassword('newPassword')
            ->setRequired('Zadejte prosím nové heslo.')
            ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespon %d znaků.', 8);

        $form->addPassword('newPasswordAgain')
            ->setRequired('Zadejte prosím nové heslo znovu.')
            ->addConditionOn($form["newPassword"], Form::FILLED)
            ->addRule(Form::EQUAL, "Hesla se neshodují.", $form["newPassword"]);

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
        
        if (!$this->passwordManager->verify($values->oldPassword, $this->user->getPassword()))
        {
            $form->addError('Nesprávné staré heslo.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function editFormSucceeded(Form $form): void
    {
        $values = $form->getValues();

        $this->user->setPassword($values->newPassword, function($password){
            return $this->passwordManager->hash($password);
        });

        $this->entityManager->persist($this->user);

        $this->entityManager->flush();

        $this->onSuccess();
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/AccountPasswordForm.latte');
        $template->render();
    }
}