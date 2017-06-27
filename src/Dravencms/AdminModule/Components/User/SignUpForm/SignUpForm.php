<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\SignUpForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Security\PasswordManager;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\UserRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\Application;
use Nette\Application\UI\Form;

class SignUpForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var PasswordManager */
    private $passwordManager;

    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $namespace = 'Front';

    public $onSuccess = [];

    /**
     * SignUpForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param UserRepository $streetRepository
     * @param PasswordManager $passwordManager
     * @param EntityManager $entityManager
     * @param Application $application
     */
    public function __construct(BaseFormFactory $baseFormFactory, UserRepository $streetRepository, PasswordManager $passwordManager, EntityManager $entityManager, Application $application)
    {
        parent::__construct();
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $streetRepository;
        $this->passwordManager = $passwordManager;
        $this->entityManager = $entityManager;

        $this->namespace = $application->getPresenter()->getUser()->getStorage()->getNamespace();
    }

    /**
     * @return \Dravencms\Components\BaseForm
     */
    public function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('firstName')
            ->setRequired('Prosím zadejte jméno.');

        $form->addText('lastName')
            ->setRequired('Prosím zadejte příjmení.');

        $form->addText('email')
            ->setType('email')
            ->addRule(Form::EMAIL, 'Prosím zadejte email.')
            ->setRequired('Prosím zadejte email.');

        $form->addPassword('password');

        $form->addPassword('passwordAgain')
            ->setRequired(true)
            ->addRule(Form::EQUAL, 'Hesla nejsou shodná.', $form['password']);

        $form->addReCaptcha();

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'signUpFormValidate'];
        $form->onSuccess[] = [$this, 'signUpFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     */
    public function signUpFormValidate(Form $form)
    {
        $values = $form->getValues();

        if ($this->userRepository->getOneByEmail($values->email, $this->namespace)) {
            $form->addError('Tento email již je zabrán, zvolte prosím jiný.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function signUpFormSucceeded(Form $form)
    {
        $values = $form->getValues();

        $newUser = new User($values->firstName, $values->lastName, $values->email, $values->password, $this->namespace, false, false, true, function ($password) {
            return $this->passwordManager->hash($password);
        });

        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        $this->onSuccess();
    }


    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/SignUpForm.latte');
        $template->render();
    }
}