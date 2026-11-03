<?php declare(strict_types = 1);

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */


namespace Dravencms\AdminModule\Components\User\UserGrid;


use Dravencms\User\Attributes\IsAllowed;
use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Model\User\Repository\UserRepository;
use Dravencms\Database\EntityManager;
use Nette\Security\User;
use Contributte\Datagrid\Column\Action\Confirmation\StringConfirmation;

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
     * @throws \Contributte\Datagrid\Exception\DatagridException
     */
    protected function createComponentGrid(string $name): Grid
    {
        /** @var Grid $grid */
        $grid = $this->baseGridFactory->create($this, $name);
        $grid->setWritePermission('user', 'edit');

        $grid->setDataSource($this->userRepository->getUsersQueryBuilder($this->namespace));

        $grid->addColumnText('namespace', 'user.admin.field.namespace')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('degree', 'user.admin.field.degree')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('firstName', 'user.admin.field.firstName')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('lastName', 'user.admin.field.lastName')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('email', 'user.admin.field.email')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('groups', 'user.admin.field.groups')
            ->setTemplate(__DIR__.'/groups.latte');

        $grid->addColumnBoolean('isActive', 'user.admin.field.active');

        if ($this->user->isAllowed('user', 'edit')) {
            $grid->addAction('edit', '')
                ->setTitle('user.admin.action.edit')
                ->setIcon('pencil')
                ->setClass('btn btn-sm btn-primary');
        }

        if ($this->user->isAllowed('user', 'delete')) {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('user.admin.action.delete')
                ->setClass('btn btn-sm btn-danger ajax')
                ->setConfirmation(new StringConfirmation('user.admin.confirm.delete', 'email'));

            $grid->addGroupAction('user.admin.action.delete')->onSelect[] = [$this, 'gridGroupActionDelete'];
        }

        $grid->addExportCsvFiltered('user.admin.action.exportFiltered', 'users_filtered.csv')
            ->setTitle('user.admin.action.exportFiltered');

        $grid->addExportCsv('user.admin.action.exportAll', 'users_all.csv')
            ->setTitle('user.admin.action.exportAll');

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
     */
    #[\Dravencms\User\Attributes\IsAllowed('user', 'delete')]
    #[\Dravencms\Security\Mutation]
    public function handleDelete($id): void
    {
        $this->requirePermission('user', 'delete');
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