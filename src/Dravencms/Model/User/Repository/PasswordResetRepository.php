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
        $this->entityManager->createQuery('UPDATE ' . PasswordReset::class . ' p SET p.isUsed = true WHERE p.user = :user AND p.isUsed = false')
            ->setParameter('user', $user)->execute();
    }

    /**
     * @param string $hash
     * @return PasswordReset|null
     */
    public function getActiveByHash(string $hash): ?PasswordReset
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $hash)) { return null; }
        return $this->passwordResetRepository->createQueryBuilder('p')
            ->where('p.hash = :hash AND p.isUsed = false AND p.deletedAt > :now')
            ->setParameter('hash', hash('sha256', $hash))->setParameter('now', new \DateTime())
            ->getQuery()->getOneOrNullResult();
    }
    /** Claim the reset once, and change the password in the same transaction. */
    public function consume(PasswordReset $reset, string $password, \Dravencms\Security\PasswordManager $passwords): bool
    {
        if (\Nette\Utils\Strings::length(\Nette\Utils\Strings::trim($password)) < 8) { return false; }
        return $this->entityManager->getConnection()->transactional(function () use ($reset, $password, $passwords): bool {
            $user = $reset->getUser();
            if (!$user->isActive() || $user->getNamespace() !== 'Admin') { return false; }
            $claimed = $this->entityManager->createQuery('UPDATE ' . PasswordReset::class . ' p SET p.isUsed = true WHERE p.id = :id AND p.hash = :hash AND p.isUsed = false AND p.deletedAt > :now')
                ->setParameter('id', $reset->getId())->setParameter('hash', $reset->getHash())->setParameter('now', new \DateTime())->execute();
            if ($claimed !== 1) { return false; }
            $this->cleanPreviousPasswordResetsForUser($user);
            $reset->setIsUsed(true);
            $user->changePassword($password, $passwords->hash(...));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return true;
        });
    }
}
