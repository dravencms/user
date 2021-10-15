<?php declare(strict_types = 1);

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */


namespace Dravencms\AdminModule\Components\User\UserGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Model\User\Repository\UserRepository;
use Dravencms\Database\EntityManager;
use Nette\Security\User;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

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

    /** @var User */
    private $user;

    /** @var array */
    public $onDelete = [];

    /**
     * UserGrid constructor.
     * @param UserRepository $userRepository
     * @param BaseGridFactory $baseGridFactory
     * @param User $user
     * @param EntityManager $entityManager
     */
    public function __construct(
        UserRepository $userRepository,
        BaseGridFactory $baseGridFactory,
        User $user,
        EntityManager $entityManager)
    {
        $this->baseGridFactory = $baseGridFactory;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    /**
     * @param $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @param string $name
     * @return Grid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    protected function createComponentGrid(string $name): Grid
    {
        /** @var Grid $grid */
        $grid = $this->baseGridFactory->create($this, $name);

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

        if ($this->user->isAllowed('user', 'edit')) {
            $grid->addAction('edit', '')
                ->setTitle('Upravit')
                ->setIcon('pencil')
                ->setClass('btn btn-xs btn-primary');
        }

        if ($this->user->isAllowed('user', 'delete')) {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('Smazat')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirmation(new StringConfirmation('Do you really want to delete row %s?', 'email'));

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
    public function gridGroupActionDelete(array $ids): void
    {
        $this->handleDelete($ids);
    }

    /**
     * @param integer|array $id
     * @isAllowed(user, delete)
     */
    public function handleDelete($id): void
    {
        $users = $this->userRepository->getById($id);
        foreach($users AS $user)
        {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/UserGrid.latte');
        $template->render();
    }
}