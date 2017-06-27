<?php

namespace Dravencms\AdminModule\Components\User\CompanyForm;

use Dravencms\Components\BaseForm\BaseFormFactory;
use Dravencms\Model\User\Entities\Company;
use Dravencms\Model\Location\Entities\Street;
use Dravencms\Model\Location\Entities\ZipCode;
use Dravencms\Model\Location\Repository\CityRepository;
use Dravencms\Model\User\Repository\CompanyRepository;
use Dravencms\Model\Location\Repository\StreetNumberRepository;
use Dravencms\Model\Location\Repository\StreetRepository;
use Dravencms\Model\User\Repository\UserRepository;
use Dravencms\Model\Location\Repository\ZipCodeRepository;
use Kdyby\Doctrine\EntityManager;
use Dravencms\Latte\User\Filters\User AS UserFilter;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
class CompanyForm extends Control
{
    /** @var Company|null */
    private $company = null;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var CompanyRepository */
    private $companyRepository;

    /** @var CityRepository */
    private $cityRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var StreetRepository */
    private $streetRepository;

    /** @var ZipCodeRepository */
    private $zipCodeRepository;

    /** @var StreetNumberRepository */
    private $streetNumberRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var UserFilter */
    private $userFilter;

    /** @var array */
    public $onSuccess = [];

    /**
     * CompanyForm constructor.
     * @param BaseFormFactory $baseFormFactory
     * @param CompanyRepository $companyRepository
     * @param CityRepository $cityRepository
     * @param UserRepository $userRepository
     * @param StreetRepository $streetRepository
     * @param ZipCodeRepository $zipCodeRepository
     * @param StreetNumberRepository $streetNumberRepository
     * @param EntityManager $entityManager
     * @param UserFilter $userFilter
     * @param Company|null $company
     */
    public function __construct(
        BaseFormFactory $baseFormFactory,
        CompanyRepository $companyRepository,
        CityRepository $cityRepository,
        UserRepository $userRepository,
        StreetRepository $streetRepository,
        ZipCodeRepository $zipCodeRepository,
        StreetNumberRepository $streetNumberRepository,
        EntityManager $entityManager,
        UserFilter $userFilter,
        Company $company = null
    ) {
        parent::__construct();
        $this->company = $company;
        $this->baseFormFactory = $baseFormFactory;
        $this->companyRepository = $companyRepository;
        $this->cityRepository = $cityRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->userFilter = $userFilter;
        $this->streetRepository = $streetRepository;
        $this->zipCodeRepository = $zipCodeRepository;
        $this->streetNumberRepository = $streetNumberRepository;

        if ($this->company) {

            $this['form']['street']->setItems($this->getStreetPairsForZipCode($this->company->getStreetNumber()->getStreet()->getZipCode()));
            $this['form']['streetNumber']->setItems($this->getStreetNumberPairsForStreet($this->company->getStreetNumber()->getStreet()));

            $this['form']->setDefaults([
                'name' => $this->company->getName(),
                'companyIdentifier' => $this->company->getCompanyIdentifier(),
                'vatIdentifier' => $this->company->getVatIdentifier(),
                'phone' => $this->company->getPhone(),
                'email' => $this->company->getEmail(),
                'www' => $this->company->getWww(),
                'description' => $this->company->getDescription(),
                'streetNumber' => $this->company->getStreetNumber()->getId(),
                'street' => $this->company->getStreetNumber()->getStreet()->getId(),
                'zipCode' => $this->company->getStreetNumber()->getStreet()->getZipCode()->getId(),
                'user' => ($this->company->getUser() ? $this->company->getUser()->getId() : null)
            ]);
        }
    }

    private function getStreetPairsForZipCode(ZipCode $zipCode)
    {
        $streetsPairs = [];
        foreach ($zipCode->getStreets() AS $street) {
            $streetsPairs[$street->getId()] = $street->getName();
        }

        return $streetsPairs;
    }

    private function getStreetNumberPairsForStreet(Street $street)
    {
        $streetsNumberPairs = [];
        foreach ($street->getStreetNumbers() AS $streetNumber) {
            $streetsNumberPairs[$streetNumber->getId()] = $streetNumber->getName();
        }
        return $streetsNumberPairs;
    }

    /**
     * @param $id
     */
    public function handleZipCodeChange($id)
    {
        $zipCode = $this->zipCodeRepository->getOneById($id);

        $this['form']['street']->setItems($this->getStreetPairsForZipCode($zipCode));
        $this->redrawControl('wrapper');
        $this->redrawControl('streetSnippet');
    }

    /**
     * @param $id
     */
    public function handleStreetChange($id)
    {
        $street = $this->streetRepository->getOneById($id);

        $this['form']['streetNumber']->setItems($this->getStreetNumberPairsForStreet($street));
        $this->redrawControl('wrapper');
        $this->redrawControl('streetNumberSnippet');
    }


    /**
     * @return \Dravencms\Components\BaseForm
     */
    public function createComponentForm()
    {
        $form = $this->baseFormFactory->create();


        $form->addText('name')
            ->setRequired('Prosím zadejte jméno.');

        $form->addText('email');
        $form->addText('phone');
        $form->addText('www');

        $form->addText('companyIdentifier')
            ->setRequired('Prosím zadejte IČO.');

        $form->addText('vatIdentifier');

        $form->addSelect('street')
            ->setPrompt('');

        $form->addSelect('streetNumber')
            ->setPrompt('');

        $zipCities = [];
        foreach ($this->cityRepository->getAll() AS $city) {
            $zips = [];
            foreach ($city->getZipCodes() AS $zipCode) {
                $zips[$zipCode->getId()] = $zipCode->getName();
            }
            $zipCities[$city->getName()] = $zips;
        }

        $form->addSelect('zipCode', null, $zipCities)
            ->setPrompt('');

        $companyUsers = [];
        /*if ($this->company) {
            $usersSource = $this->company->getUsers();
        } else {*/
            $usersSource = $this->userRepository->getAll('Front');
        //}

        foreach ($usersSource AS $companyUser) {
            $companyUsers[$companyUser->getId()] = $this->userFilter->formatUserName($companyUser);
        }

        $form->addSelect('user', null, $companyUsers)
            ->setPrompt('');

        $form->addTextArea('description');

        $form->addSubmit('send');

        $form->onValidate[] = [$this, 'onValidateForm'];
        $form->onSuccess[] = [$this, 'onSuccessForm'];
        return $form;
    }

    /**
     * @param Form $form
     */
    public function onValidateForm(Form $form)
    {
        $values = $form->getValues();

        $zipCode = $this->zipCodeRepository->getOneById($values->zipCode);

        if (!$this->companyRepository->isCompanyNameFree($values->name, $zipCode->getCity()->getCountry(), $this->company)) {
            $form->addError('Firma s timto jmenem jiz existuje.');
        }

        if (!$this->companyRepository->isCompanyIdentifierNameFree($values->companyIdentifier, $zipCode->getCity()->getCountry(), $this->company)) {
            $form->addError('Firma s timto jmenem jiz existuje.');
        }

        if (!$this->presenter->isAllowed('user', 'companyEdit')) {
            $form->addError('Nemáte oprávění editovat firmu');
        }
    }

    /**
     * @param Form $form
     */
    public function onSuccessForm(Form $form)
    {
        $values = $form->getValues();
        $values->streetNumber = $form->getHttpData()['streetNumber'];

        $streetNumber = $this->streetNumberRepository->getOneById($values->streetNumber);

        if ($values->user)
        {
            $user = $this->userRepository->getOneById($values->user);
        }
        else
        {
            $user = null;
        }


        if ($this->company) {
            $company = $this->company;
            $company->setCompanyIdentifier($values->companyIdentifier);
            $company->setVatIdentifier($values->vatIdentifier);
            $company->setName($values->name);
            $company->setEmail($values->email);
            $company->setPhone($values->phone);
            $company->setWww($values->www);
            $company->setStreetNumber($streetNumber);
            $company->setDescription($values->description);
            $company->setUser($user);
        } else {
            $company = new Company($values->companyIdentifier, $values->vatIdentifier, $values->name, $values->email, $values->phone, $values->www, $streetNumber, $values->description, $user);
        }

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $this->onSuccess();
    }

    public function render()
    {
        $template = $this->template;
        $template->panelHeading = ($this->company ? 'Editation of ' . $this->company->getName() . ' company' : 'New Company');
        $template->setFile(__DIR__ . '/CompanyForm.latte');
        $template->render();
    }
}