<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */


namespace Dravencms\AdminModule\Components\User\CompanyGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Model\User\Repository\CompanyRepository;
use Kdyby\Doctrine\EntityManager;

class CompanyGrid extends BaseControl
{
    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var EntityManager */
    private $entityManager;

    /** @var CompanyRepository */
    private $companyRepository;

    /** @var array  */
    public $onDelete = [];

    /**
     * CountryGrid constructor.
     * @param CompanyRepository $companyRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     */
    public function __construct(CompanyRepository $companyRepository, BaseGridFactory $baseGridFactory, EntityManager $entityManager)
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->companyRepository = $companyRepository;
        $this->entityManager = $entityManager;
    }


    /**
     * @param $name
     * @return \Grido\Grid
     */
    protected function createComponentGrid($name)
    {
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setModel($this->companyRepository->getCompanyQueryBuilder());

        $grid->addColumnText('companyIdentifier', 'IČO')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('vatIdentifier', 'DIČ')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('streetNumber.street.name', 'Ulice')
            ->setCustomRender(function($row){
                if ($row->getStreetNumber())
                {
                    return $row->getStreetNumber()->getStreet()->getName();
                }
                return '-';
            })
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('streetNumber.name', 'Číslo ulice')
            ->setCustomRender(function($row){
                if ($row->getStreetNumber())
                {
                    return $row->getStreetNumber()->getName();
                }
                return '-';
            })
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('streetNumber.street.zipCode.name', 'ZIP Code')
            ->setCustomRender(function($row){
                if ($row->getStreetNumber())
                {
                    return $row->getStreetNumber()->getStreet()->getZipCode()->getName();
                }
                return '-';
            })
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('streetNumber.street.zipCode.city.name', 'Město')
            ->setCustomRender(function($row){
                if ($row->getStreetNumber())
                {
                    return $row->getStreetNumber()->getStreet()->getZipCode()->getCity()->getName();
                }
                return '-';
            })
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('usersCount', 'Uživatelů')
            ->setColumn(function ($row) {
                return count($row->getUsers());
            });
        $grid->getColumn('usersCount')->cellPrototype->class[] = 'center';
        if ($this->presenter->isAllowed('user', 'companyEdit')) {
            $grid->addActionHref('edit', 'Upravit')
                ->setIcon('pencil');
        }

        if ($this->presenter->isAllowed('user', 'companyDelete')) {
            $grid->addActionHref('delete', 'Smazat', 'delete!')
                ->setCustomHref(function($row){
                    return $this->link('delete!', $row->getId());
                })
                ->setDisable(function ($row) {
                    return (count($row->getUsers()) > 0);
                })
                ->setIcon('trash-o')
                ->setConfirm(function ($row) {
                    return ['Opravdu chcete smazat Firmu %s ?', $row->name];
                });

            $operations = ['delete' => 'Smazat'];
            $grid->setOperation($operations, [$this, 'gridCompanyOperationsHandler'])
                ->setConfirm('delete', 'Opravu chcete smazat %i zákazníků ?');
        }
        $grid->setExport();

        return $grid;
    }



    /**
     * @param $action
     * @param $ids
     */
    public function gridCompanyOperationsHandler($action, $ids)
    {
        switch ($action) {
            case 'delete':
                $this->handleDelete($ids);
                break;
        }
    }

    /**
     * @param integer $id
     * @isAllowed(user, companyDelete)
     */
    public function handleDelete($id)
    {
        $companies = $this->companyRepository->getById($id);
        foreach ($companies AS $company) {
            $this->entityManager->remove($company);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }


    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/CompanyGrid.latte');
        $template->render();
    }
}