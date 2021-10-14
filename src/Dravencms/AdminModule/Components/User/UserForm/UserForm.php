<?php declare(strict_types = 1);

namespace Dravencms\AdminModule\Components\User\UserForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseForm;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Security\PasswordManager;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\GroupRepository;
use Dravencms\Model\User\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Dravencms\Database\EntityManager;
use Nette\Application\Application;
use Nette\Application\UI\Form;

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

    /** @var string */
    private $namespace = 'Front';

    public $onSuccess = [];

    /**
     * UserForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param UserRepository $streetRepository
     * @param EntityManager $entityManager
     * @param Application $application
     * @param PasswordManager $passwordManager
     * @param GroupRepository $groupRepository
     * @param User|null $user
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        UserRepository $streetRepository,
        EntityManager $entityManager,
        Application $application,
        PasswordManager $passwordManager,
        GroupRepository $groupRepository,
        User $user = null
    ) {
        $this->user = $user;
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $streetRepository;
        $this->entityManager = $entityManager;
        $this->passwordManager = $passwordManager;
        $this->groupRepository = $groupRepository;

        $this->namespace = $application->getPresenter()->getUser()->getStorage()->getNamespace();

        if ($this->user)
        {
            $groups = [];

            foreach($this->user->getRoles() AS $group)
            {
                $groups[$group->getId()] = $group->getId();
            }


            $this['form']->setDefaults([
                'degree' => $this->user->getDegree(),
                'firstName' => $this->user->getFirstName(),
                'lastName' => $this->user->getLastName(),
                'email' => $this->user->getEmail(),
                'groups' => $groups,
                'isActive' => $this->user->isActive()
            ]);
        }
        else
        {
            $this['form']['password']->setRequired('Zadejte prosím heslo');
        }
    }

    /**
     * @return Form
     */
    public function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('degree');

        $form->addText('firstName')
            ->setRequired('Prosím zadejte jméno.');

        $form->addText('lastName')
            ->setRequired('Prosím zadejte příjmení.');

        $form->addText('email')
            ->setType('email')
            ->addRule(Form::EMAIL, 'Prosím zadejte email.')
            ->setRequired('Prosím zadejte email.');

        $form->addPassword('password')
            ->addConditionOn($form["password"], Form::FILLED)
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespon %d znaků.', 8);

        $form->addPassword('passwordAgain')
            ->addConditionOn($form["password"], Form::FILLED)
                ->setRequired(true)
                ->addRule(Form::EQUAL, 'Hesla nejsou shodná.', $form['password']);

        $form->addMultiSelect('groups', null, $this->groupRepository->getPairs());
        
        $form->addCheckbox('isActive');

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'onValidateForm'];
        $form->onSuccess[] = [$this, 'onSuccessForm'];
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
            $form->addError('Tento email již je zabrán, zvolte prosím jiný.');
        }

        //Kontrola opraveni
        if (!$this->presenter->isAllowed('user', 'edit')) {
            $form->addError('Nemáte oprávění editovat uživatele.');
        }
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    public function onSuccessForm(Form $form): void
    {
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