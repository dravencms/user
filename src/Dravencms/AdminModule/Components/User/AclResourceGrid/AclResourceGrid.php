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

namespace Dravencms\AdminModule\Components\User\AclResourceGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Model\User\Repository\AclResourceRepository;
use Kdyby\Doctrine\EntityManager;

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

    /** @var array */
    public $onDelete = [];

    /**
     * AclResourceGrid constructor.
     * @param AclResourceRepository $aclResourceRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     */
    public function __construct(AclResourceRepository $aclResourceRepository, BaseGridFactory $baseGridFactory, EntityManager $entityManager)
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->aclResourceRepository = $aclResourceRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param $name
     * @return \Dravencms\Components\BaseGrid
     */
    public function createComponentGrid($name)
    {
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setModel($this->aclResourceRepository->getAclResourceQueryBuilder());

        $grid->addColumnText('name', 'Název')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();

        $grid->addColumnText('description', 'Popis')
                ->setSortable()
                ->setFilterText()
                ->setSuggestion();

        if ($this->presenter->isAllowed('user', 'edit'))
        {
            $grid->addActionHref('operation', 'Operace')
                ->setIcon('lock');

            $grid->addActionHref('edit', 'Upravit')
                    ->setIcon('pencil');
        }

        if ($this->presenter->isAllowed('user', 'delete'))
        {
            $grid->addActionHref('delete', 'Smazat')
                    ->setCustomHref(function($row){
                        return $this->link('delete!', $row->getId());
                    })
                    ->setDisable(function($row){
                        return count($row->getAclOperations());
                    })
                    ->setIcon('trash-o')
                    ->setConfirm(function($item)
                    {
                        return array("Opravdu chcete smazat %s ?", $item->getName());
                    });


            $operations = array('delete' => 'Smazat');
            $grid->setOperation($operations, [$this, 'gridOperationsHandler'])
                    ->setConfirm('delete', 'Opravu chcete smazat %i oprávění ?');
        }

        $grid->setExport();

        return $grid;
    }

    /**
     * @param $action
     * @param $ids
     */
    public function gridOperationsHandler($action, $ids)
    {
        switch ($action)
        {
            case 'delete':
                $this->handleDelete($ids);
                break;
        }
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function handleDelete($id)
    {
        $aclResources = $this->aclResourceRepository->getById($id);
        foreach ($aclResources AS $aclOperation)
        {
            $this->entityManager->remove($aclOperation);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/AclResourceGrid.latte');
        $template->render();
    }

}
