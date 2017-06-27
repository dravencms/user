<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\DoResetPasswordForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Security\PasswordManager;
use Dravencms\Model\User\Entities\PasswordReset;
use Dravencms\Model\User\Repository\UserRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\Application;
use Nette\Application\UI\Form;

class DoResetPasswordForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var PasswordReset */
    private $passwordReset;

    /** @var PasswordManager */
    private $passwordManager;

    /** @var string */
    private $namespace = 'Front';

    public $onSuccess = [];

    /**
     * DoResetPasswordForm constructor.
     * @param PasswordReset $passwordReset
     * @param BaseFormFactory $baseFormFactory
     * @param UserRepository $userRepository
     * @param EntityManager $entityManager
     * @param Application $application
     * @param PasswordManager $passwordManager
     */
    public function __construct(
        PasswordReset $passwordReset,
        BaseFormFactory $baseFormFactory,
        UserRepository $userRepository,
        EntityManager $entityManager,
        Application $application,
        PasswordManager $passwordManager
    ) {
        parent::__construct();
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordReset = $passwordReset;
        $this->passwordManager = $passwordManager;

        $this->namespace = $application->getPresenter()->getUser()->getStorage()->getNamespace();
    }

    /**
     * @return \Dravencms\Components\BaseForm
     */
    public function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addPassword('password');

        $form->addPassword('passwordAgain')
            ->setRequired(true)
            ->addRule(Form::EQUAL, 'Hesla nejsou shodná.', $form['password']);

        $form->addSubmit('send');

        $form->onSuccess[] = [$this, 'onSuccessForm'];
        $form->onValidate[] = [$this, 'onValidateForm'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function onValidateForm(Form $form)
    {
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function onSuccessForm(Form $form)
    {
        $values = $form->getValues();

        $this->passwordReset->setIsUsed(true);

        $user = $this->passwordReset->getUser();
        $user->changePassword($values->password, function($password){
            return $this->passwordManager->hash($password);
        });

        $this->entityManager->persist($this->passwordReset);
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        $this->onSuccess();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/DoResetPasswordForm.latte');
        $template->render();
    }
}