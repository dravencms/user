<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\PasswordReset;
use Dravencms\Model\User\Entities\User;
use Dravencms\Database\EntityManager;

/**
 * Class PasswordResetRepository
 * @package Dravencms\Model\User\Repository
 */
class PasswordResetRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|PasswordReset */
    private $passwordResetRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * MenuRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordResetRepository = $entityManager->getRepository(PasswordReset::class);
    }

    /**
     * @param User $user
     * @throws \Exception
     */
    public function cleanPreviousPasswordResetsForUser(User $user): void
    {
        foreach($this->passwordResetRepository->findBy(['user' => $user]) AS $passwordReset)
        {
            $this->entityManager->remove($passwordReset);
            $this->entityManager->flush();
        }
    }

    /**
     * @param string $hash
     * @return PasswordReset|null
     */
    public function getActiveByHash(string $hash): ?PasswordReset
    {
        return $this->passwordResetRepository->findOneBy(['hash' => $hash, 'isUsed' => false]);
    }
}