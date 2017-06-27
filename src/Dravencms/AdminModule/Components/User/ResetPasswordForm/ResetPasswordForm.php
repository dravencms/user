<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\ResetPasswordForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\User\Entities\PasswordReset;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\PasswordResetRepository;
use Dravencms\Model\User\Repository\UserRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\Application;
use Nette\Application\UI\Form;
use Salamek\TemplatedEmail\TemplatedEmail;

class ResetPasswordForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var TemplatedEmail */
    private $templatedEmail;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var PasswordResetRepository */
    private $passwordResetRepository;

    /** @var string */
    private $namespace = 'Front';

    public $onSuccess = [];

    /**
     * ResetPasswordForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param TemplatedEmail $templatedEmail
     * @param UserRepository $userRepository
     * @param EntityManager $entityManager
     * @param Application $application
     * @param PasswordResetRepository $passwordResetRepository
     */
    public function __construct(BaseFormFactory $baseFormFactory, TemplatedEmail $templatedEmail, UserRepository $userRepository, EntityManager $entityManager, Application $application, PasswordResetRepository $passwordResetRepository)
    {
        parent::__construct();
        $this->baseFormFactory = $baseFormFactory;
        $this->templatedEmail = $templatedEmail;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordResetRepository = $passwordResetRepository;

        $this->namespace = $application->getPresenter()->getUser()->getStorage()->getNamespace();
    }

    /**
     * @return \Dravencms\Components\BaseForm
     */
    public function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addText('email')
            ->setRequired('Please enter your email.')
            ->addRule(Form::EMAIL, 'Please enter a valid email');

        $form->addSubmit('send');

        $form->onSuccess[] = [$this, 'onSuccessForm'];
        $form->onValidate[] = [$this, 'onValidateForm'];

        return $form;
    }

    public function onValidateForm(Form $form)
    {
        $values = $form->getValues();
        if (!$this->userRepository->getOneByEmail($values->email, $this->namespace)) {
            $form->addError('Email was not found');
        }
    }

    public function onSuccessForm(Form $form)
    {
        $values = $form->getValues();

        /** @var User $user */
        $user = $this->userRepository->getOneByEmail($values->email, $this->namespace);
        if (!$user) {
            $this->presenter->error();
        }
        // Softdelte any previous password resets
        $this->passwordResetRepository->cleanPreviousPasswordResetsForUser($user);

        // Create new password reset
        $expiry = new \DateTime;
        $expiry->modify('+24 hours');

        $newPasswordReset = new PasswordReset($user, $expiry);

        $this->entityManager->persist($newPasswordReset);
        $this->entityManager->flush();

        $this->templatedEmail->resetPassword([
            'title' => 'Password reset',
            'email' => $user->getEmail(),
            'hash' => $newPasswordReset->getHash()
        ])
            ->addTo($user->getEmail())
            ->setSubject('Password reset')
            ->send();

        $this->onSuccess();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/ResetPasswordForm.latte');
        $template->render();
    }
}