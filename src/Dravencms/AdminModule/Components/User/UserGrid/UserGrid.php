<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */


namespace Dravencms\AdminModule\Components\User\UserGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\UserRepository;
use Nette\Utils\Html;
use Kdyby\Doctrine\EntityManager;

class UserGrid extends BaseControl
{
    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var string */
    private $namespace = '';

    /** @var EntityManager */
    private $entityManager;

    /** @var array */
    public $onDelete = [];

    /**
     * MenuGrid constructor.
     * @param UserRepository $userRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     */
    public function __construct(UserRepository $userRepository, BaseGridFactory $baseGridFactory, EntityManager $entityManager)
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @param $name
     * @return Grid
     */
    protected function createComponentGrid($name)
    {
        /** @var Grid $grid */
        $grid = $this->baseGridFactory->create($this, $name);

        $this->entityManager->getFilters()->enable('soft-deleteable');
        $grid->setDataSource($this->userRepository->getUsersQueryBuilder($this->namespace));

        $grid->addColumnText('namespace', 'Namespace')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('degree', 'Titul')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('firstName', 'Jméno')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('lastName', 'Příjmení')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('email', 'Email')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('groups', 'Skupiny')
            ->setTemplate(__DIR__.'/groups.latte');

        $grid->addColumnBoolean('isActive', 'Active');

        if ($this->presenter->isAllowed('user', 'edit')) {
            $grid->addAction('edit', '')
                ->setTitle('Upravit')
                ->setIcon('pencil')
                ->setClass('btn btn-xs btn-primary');
        }

        if ($this->presenter->isAllowed('user', 'delete')) {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('Smazat')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirm('Do you really want to delete row %s?', 'email');

            $grid->addGroupAction('Smazat')->onSelect[] = [$this, 'gridGroupActionDelete'];
        }

        $grid->addExportCsvFiltered('Csv export (filtered)', 'users_filtered.csv')
            ->setTitle('Csv export (filtered)');

        $grid->addExportCsv('Csv export', 'users_all.csv')
            ->setTitle('Csv export');

        return $grid;
    }

    /**
     * @param array $ids
     */
    public function gridGroupActionDelete(array $ids)
    {
        $this->handleDelete($ids);
    }

    /**
     * @param integer|array $id
     * @isAllowed(user, delete)
     */
    public function handleDelete($id)
    {
        $users = $this->userRepository->getById($id);
        foreach($users AS $user)
        {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/UserGrid.latte');
        $template->render();
    }
}