<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\User;

use Kdyby\Doctrine\EntityManager;
use Dravencms\Model\User\Entities\User as UserEntity;
use Nette;

class DefaultDataCreator
{
    use Nette\SmartObject;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function create(UserEntity $user)
    {
        /*$this->resources($user);
        $this->homePlanet($user);*/
    }

}