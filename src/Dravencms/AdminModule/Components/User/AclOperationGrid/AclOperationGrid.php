<?php

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

namespace Dravencms\AdminModule\Components\User\AclOperationGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Kdyby\Doctrine\EntityManager;

/**
 * Description of AclOperationGrid
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class AclOperationGrid extends BaseControl
{

    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var AclOperationRepository */
    private $aclOperationRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var AclResource */
    private $aclResource;

    /**
     * @var array
     */
    public $onDelete = [];

    /**
     * AclOperationGrid constructor.
     * @param AclResource $aclResource
     * @param AclOperationRepository $aclOperationRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     */
    public function __construct(AclResource $aclResource, AclOperationRepository $aclOperationRepository, BaseGridFactory $baseGridFactory, EntityManager $entityManager)
    {
        parent::__construct();

        $this->aclResource = $aclResource;
        $this->baseGridFactory = $baseGridFactory;
        $this->aclOperationRepository = $aclOperationRepository;
        $this->entityManager = $entityManager;
    }


    /**
     * @param $name
     * @return Grid
     */
    public function createComponentGrid($name)
    {
        /** @var Grid $grid */
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setDataSource($this->aclOperationRepository->getAclOperationQueryBuilder($this->aclResource));

        $grid->addColumnText('name', 'Název')
                ->setSortable()
                ->setFilterText();

        $grid->addColumnText('description', 'Popis')
                ->setSortable()
                ->setFilterText();

        if ($this->presenter->isAllowed('user', 'edit'))
        {
            $grid->addAction('editOperation', '')
                    ->setIcon('pencil')
                    ->setTitle('Upravit')
                    ->setClass('btn btn-xs btn-primary');
        }

        if ($this->presenter->isAllowed('user', 'delete'))
        {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('Smazat')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirm('Do you really want to delete row %s?', 'name');

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
     */
    public function gridGroupActionDelete(array $ids)
    {
        $this->handleDelete($ids);
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function handleDelete($id)
    {
        $aclOperations = $this->aclOperationRepository->getById($id);
        foreach ($aclOperations AS $aclOperation)
        {
            $this->entityManager->remove($aclOperation);
        }

        $this->entityManager->flush();

        $this->onDelete($this->aclResource);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/AclOperationGrid.latte');
        $template->render();
    }
}
