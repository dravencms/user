<?php declare(strict_types = 1);

namespace Dravencms\AdminModule\Components\User\UserForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Security\PasswordManager;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\GroupRepository;
use Dravencms\Model\User\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Dravencms\Database\EntityManager;
use Dravencms\Components\BaseForm\Form;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
class UserForm extends BaseControl
{

    /** @var User|null */
    private $user = null;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var PasswordManager */
    private $passwordManager;

    /** @var GroupRepository */
    private $groupRepository;

    /** @var \Nette\Security\User */
    private $securityUser;

    /** @var string */
    private $namespace = 'Admin';

    public $onSuccess = [];

    /**
     * UserForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param UserRepository $streetRepository
     * @param EntityManager $entityManager
     * @param PasswordManager $passwordManager
     * @param GroupRepository $groupRepository
     * @param \Nette\Security\User $securityUser
     * @param User|null $user
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        UserRepository $streetRepository,
        EntityManager $entityManager,
        PasswordManager $passwordManager,
        GroupRepository $groupRepository,
        \Nette\Security\User $securityUser,
        ?User $user = null
    ) {
        $this->user = $user;
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $streetRepository;
        $this->entityManager = $entityManager;
        $this->passwordManager = $passwordManager;
        $this->groupRepository = $groupRepository;
        $this->securityUser = $securityUser;

    }

    /**
     * @return Form
     */
    public function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('degree');

        $form->addText('firstName')
            ->setRequired('user.admin.validation.firstNameRequired');

        $form->addText('lastName')
            ->setRequired('user.admin.validation.lastNameRequired');

        $form->addText('email')
            ->setHtmlType('email')
            ->addRule(Form::EMAIL, 'user.admin.validation.emailInvalid')
            ->setRequired('user.admin.validation.emailRequired');

        $form->addPassword('password')->addFilter(\Nette\Utils\Strings::trim(...))
            ->addConditionOn($form["password"], Form::FILLED)
                ->addRule(Form::MIN_LENGTH, 'user.admin.validation.passwordMinLength', 8);

        $form->addPassword('passwordAgain')->addFilter(\Nette\Utils\Strings::trim(...))
            ->addConditionOn($form["password"], Form::FILLED)
                ->setRequired('user.admin.validation.passwordAgainRequired')
                ->addRule(Form::EQUAL, 'user.admin.validation.passwordsMismatch', $form['password']);

        $form->addMultiSelect('groups', null, $this->groupRepository->getPairs());
        
        $form->addCheckbox('isActive');

        $form->addSubmit('send', 'user.admin.action.save');

        $form->onValidate[] = [$this, 'onValidateForm'];
        $form->onSuccess[] = [$this, 'onSuccessForm'];
        if ($this->user) {
            $groups = [];
            foreach ($this->user->getRoles() as $group) {
                $groups[$group->getId()] = $group->getId();
            }

            $form->setDefaults([
                'degree' => $this->user->getDegree(),
                'firstName' => $this->user->getFirstName(),
                'lastName' => $this->user->getLastName(),
                'email' => $this->user->getEmail(),
                'groups' => $groups,
                'isActive' => $this->user->isActive(),
            ]);
        } else {
            $form['password']->setRequired('user.admin.validation.passwordRequired');
        }

        return $form;
    }

    /**
     * @param Form $form
     */
    public function onValidateForm(Form $form): void
    {
        $values = $form->getValues();

        if (!$this->userRepository->isEmailFree($values->email, $this->namespace, $this->user))
        {
            $form->addError('user.admin.validation.emailTaken');
        }

        //Kontrola opraveni
        if (!$this->securityUser->isAllowed('user', 'edit')) {
            $form->addError('user.admin.validation.userForbidden');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function onSuccessForm(Form $form): void
    {
        $this->requirePermission('user', 'edit');
        $values = $form->getValues();


        if ($this->user)
        {
            $user = $this->user;
            $user->setDegree($values->degree);
            $user->setFirstName($values->firstName);
            $user->setLastName($values->lastName);
            $user->setEmail($values->email);
            $user->setIsActive($values->isActive);

            if ($values->password)
            {
                $user->changePassword($values->password, function($password){
                    return $this->passwordManager->hash($password);
                });
            }
        }
        else
        {
            $passwordGenerator = function($password) {
                return $this->passwordManager->hash($password);
            };
            $user = new User($values->firstName, $values->lastName, $values->email, $values->password, $this->namespace, $passwordGenerator, $values->isActive, false, true);
        }

        $groups = new ArrayCollection($this->groupRepository->getById($values->groups));
        $user->setGroups($groups);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->onSuccess();
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/UserForm.latte');
        $template->render();
    }
}