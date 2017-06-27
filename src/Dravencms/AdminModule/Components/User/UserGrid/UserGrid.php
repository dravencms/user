<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */


namespace Dravencms\AdminModule\Components\User\UserGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
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
     * @return \Grido\Grid
     */
    protected function createComponentGrid($name)
    {
        $grid = $this->baseGridFactory->create($this, $name);

        $this->entityManager->getFilters()->enable('soft-deleteable');
        $grid->setModel($this->userRepository->getUsersQueryBuilder($this->namespace));

        $grid->addColumnText('namespace', 'Namespace')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('degree', 'Titul')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('firstName', 'Jméno')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('lastName', 'Příjmení')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        $grid->addColumnText('email', 'Email')
            ->setSortable()
            ->setFilterText()
            ->setSuggestion();

        /**
         * @param User $row
         * @return mixed
         */
        $groupsCol = function ($row) {
            $roles = [];
            foreach ($row->getRoles() AS $usersGroup) {
                $el = Html::el('span', mb_strtoupper($usersGroup->getName()));
                $el->class = 'label label-default';
                $el->style = 'background: #'.$usersGroup->getColor().';';
                $roles[] = $el;
            }
            return implode(', ', $roles);
        };

        $grid->addColumnText('groups', 'Skupiny')
            ->setColumn($groupsCol)
            ->setCustomRender($groupsCol);

        $grid->addColumnBoolean('isActive', 'Active');

        if ($this->presenter->isAllowed('user', 'edit')) {
            $grid->addActionHref('edit', 'Upravit')
                ->setIcon('pencil');
        }

        if ($this->presenter->isAllowed('user', 'delete')) {
            $grid->addActionHref('delete', 'Smazat', 'delete!')
                ->setCustomHref(function($row){
                    return $this->link('delete!', $row->getId());
                })
                ->setIcon('trash-o')
                ->setConfirm(function ($item) {
                    return ["Opravdu chcete smazat %s ?", $item->getFirstName() . ' ' . $item->getLastName()];
                });


            $operations = ['delete' => 'Smazat'];
            $grid->setOperation($operations, [$this, 'gridUsersOperationsHandler'])
                ->setConfirm('delete', 'Opravu chcete smazat %i uživatelů ?');
        }

        $grid->setExport();

        return $grid;
    }


    /**
     * @param $action
     * @param $ids
     */
    public function gridUsersOperationsHandler($action, $ids)
    {
        switch ($action) {
            case 'delete':
                $this->handleDelete($ids);
                break;
        }
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