<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\Gender;
use Dravencms\Database\EntityManager;

class GenderRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|Gender */
    private $genderRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * GenderRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->genderRepository = $entityManager->getRepository(Gender::class);
    }

}