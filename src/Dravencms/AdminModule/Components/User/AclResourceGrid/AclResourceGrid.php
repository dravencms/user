<?php declare(strict_types = 1);

/*
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace Dravencms\AdminModule\Components\User\AclResourceGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Model\User\Repository\AclResourceRepository;
use Dravencms\Database\EntityManager;
use Nette\Security\User;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

/**
 * Description of AclResourceGrid
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class AclResourceGrid extends BaseControl
{

    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var AclResourceRepository */
    private $aclResourceRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var User */
    private $user;

    /** @var array */
    public $onDelete = [];

    /**
     * AclResourceGrid constructor.
     * @param AclResourceRepository $aclResourceRepository
     * @param BaseGridFactory $baseGridFactory
     * @param User $user
     * @param EntityManager $entityManager
     */
    public function __construct(
        AclResourceRepository $aclResourceRepository,
        BaseGridFactory $baseGridFactory,
        User $user,
        EntityManager $entityManager)
    {
        $this->baseGridFactory = $baseGridFactory;
        $this->aclResourceRepository = $aclResourceRepository;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    /**
     * @param string $name
     * @return Grid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentGrid(string $name): Grid
    {
        /** @var Grid $grid */
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setDataSource($this->aclResourceRepository->getAclResourceQueryBuilder());

        $grid->addColumnText('name', 'Název')
                ->setSortable()
                ->setFilterText();

        $grid->addColumnText('description', 'Popis')
                ->setSortable()
                ->setFilterText();

        if ($this->user->isAllowed('user', 'edit'))
        {
            $grid->addAction('operation', 'Operace')
                ->setIcon('lock')
                ->setTitle('Operace')
                ->setClass('btn btn-xs btn-default');

            $grid->addAction('edit', '')
                    ->setIcon('pencil')
                    ->setTitle('Upravit')
                    ->setClass('btn btn-xs btn-primary');
        }

        if ($this->user->isAllowed('user', 'delete'))
        {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('Smazat')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirmation(new StringConfirmation('Do you really want to delete row %s?', 'name'));

            $grid->addGroupAction('Smazat')->onSelect[] = [$this, 'gridGroupActionDelete'];
        }

        $grid->addExportCsvFiltered('Csv export (filtered)', 'acl_resource_filtered.csv')
            ->setTitle('Csv export (filtered)');

        $grid->addExportCsv('Csv export', 'acl_resource_all.csv')
            ->setTitle('Csv export');

        return $grid;
    }

    /**
     * @param array $ids
     * @throws \Exception
     */
    public function gridGroupActionDelete(array $ids): void
    {
        $this->handleDelete($ids);
    }

    /**
     * @param $id
     * @throws \Exception
     * @isAllowed(user, delete)
     */
    public function handleDelete($id): void
    {
        $aclResources = $this->aclResourceRepository->getById($id);
        foreach ($aclResources AS $aclOperation)
        {
            $this->entityManager->remove($aclOperation);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }

    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/AclResourceGrid.latte');
        $template->render();
    }

}
