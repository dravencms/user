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

namespace Dravencms\AdminModule\Components\User\GroupGrid;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseGrid\BaseGridFactory;
use Dravencms\Components\BaseGrid\Grid;
use Dravencms\Model\User\Repository\GroupRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Utils\Html;
use Nette\Utils\Strings;

/**
 * Description of GroupGrid
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class GroupGrid extends BaseControl
{
    /** @var BaseGridFactory */
    private $baseGridFactory;

    /** @var GroupRepository */
    private $groupRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var array */
    public $onDelete = [];

    /**
     * GroupGrid constructor.
     * @param GroupRepository $groupRepository
     * @param BaseGridFactory $baseGridFactory
     * @param EntityManager $entityManager
     */
    public function __construct(GroupRepository $groupRepository, BaseGridFactory $baseGridFactory, EntityManager $entityManager)
    {
        parent::__construct();

        $this->baseGridFactory = $baseGridFactory;
        $this->groupRepository = $groupRepository;
        $this->entityManager = $entityManager;
    }

    public function createComponentGrid($name)
    {
        /** @var Grid $grid */
        $grid = $this->baseGridFactory->create($this, $name);

        $grid->setDataSource($this->groupRepository->getGroupQueryBuilder());

        $grid->addColumnText('name', 'Název')
                ->setRenderer(function($row)
                {
                    $el = Html::el('span', Strings::upper($row->getName()));
                    $el->class = 'label label-default';
                    $el->style = 'background: #'.$row->getColor().';';
                    return $el;
                })
                ->setSortable()
                ->setFilterText();

        $grid->addColumnText('description', 'Popis')
                ->setSortable()
                ->setFilterText();


        $grid->addColumnBoolean('isRegister', 'Přidána při registraci');

        if ($this->presenter->isAllowed('user', 'edit'))
        {
            $grid->addAction('edit', '')
                    ->setIcon('pencil')
                    ->setTitle('Upravit')
                    ->setClass('btn btn-xs btn-primary');
        }

        if ($this->presenter->isAllowed('user', 'delete')) {
            $grid->addAction('delete', '', 'delete!')
                ->setIcon('trash')
                ->setTitle('Smazat')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirm('Do you really want to delete row %s?', 'name');

            $grid->addGroupAction('Smazat')->onSelect[] = [$this, 'gridGroupActionDelete'];
        }

        $grid->addExportCsvFiltered('Csv export (filtered)', 'groups_filtered.csv')
            ->setTitle('Csv export (filtered)');

        $grid->addExportCsv('Csv export', 'groups_all.csv')
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
     * @isAllowed(user,delete)
     */
    public function handleDelete($id)
    {
        $groups = $this->groupRepository->getById($id);
        foreach ($groups AS $group) {
            $this->entityManager->remove($group);
        }

        $this->entityManager->flush();

        $this->onDelete();
    }


    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/GroupGrid.latte');
        $template->render();
    }
}