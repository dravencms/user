<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\PasswordReset;
use Dravencms\Model\User\Entities\User;
use Kdyby\Doctrine\EntityManager;
use Nette;

/**
 * Class PasswordResetRepository
 * @package Dravencms\Model\User\Repository
 */
class PasswordResetRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
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
    public function cleanPreviousPasswordResetsForUser(User $user)
    {
        foreach($this->passwordResetRepository->findBy(['user' => $user]) AS $passwordReset)
        {
            $this->entityManager->remove($passwordReset);
            $this->entityManager->flush();
        }
    }

    /**
     * @param $hash
     * @return mixed|null|PasswordReset
     */
    public function getActiveByHash($hash)
    {
        return $this->passwordResetRepository->findOneBy(['hash' => $hash, 'isUsed' => false]);
    }
}