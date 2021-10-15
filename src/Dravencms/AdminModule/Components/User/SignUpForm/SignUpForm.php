<?php declare(strict_types = 1);

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\SignUpForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Security\PasswordManager;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\UserRepository;
use Dravencms\Database\EntityManager;
use Dravencms\Components\BaseForm\Form;

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
    private $namespace = 'Admin';

    public $onSuccess = [];

    /**
     * SignUpForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param UserRepository $streetRepository
     * @param PasswordManager $passwordManager
     * @param EntityManager $entityManager
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        UserRepository $streetRepository,
        PasswordManager $passwordManager,
        EntityManager $entityManager
    )
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $streetRepository;
        $this->passwordManager = $passwordManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Form
     */
    public function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('firstName')
            ->setRequired('Prosím zadejte jméno.');

        $form->addText('lastName')
            ->setRequired('Prosím zadejte příjmení.');

        $form->addText('email')
            ->setHtmlType('email')
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
    public function signUpFormValidate(Form $form): void
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
    public function signUpFormSucceeded(Form $form): void
    {
        $values = $form->getValues();

        $passwordGenerator = function($password) {
            return $this->passwordManager->hash($password);
        };

        $newUser = new User($values->firstName, $values->lastName, $values->email, $values->password, $this->namespace, $passwordGenerator,false, false, true);

        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        $this->onSuccess();
    }


    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/SignUpForm.latte');
        $template->render();
    }
}